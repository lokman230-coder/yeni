<?php

declare(strict_types=1);

namespace App\Modules\Payment\Drivers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Logger\ApiLogger;
use App\Services\Logger\Logger;
use App\Services\Settings\SettingsManager;

/**
 * Papara Merchant API entegrasyonu.
 *
 * Doküman: https://merchant-api.papara.com/redoc
 * Test:   https://merchant-api-test.papara.com
 * Prod:   https://merchant-api.papara.com
 *
 * Akış:
 *   1) POST /payments  → paymentUrl (kullanıcı Papara sayfasına yönlenir)
 *   2) Kullanıcı ödeme yapar → NotificationUrl'ye JSON POST gelir
 *   3) Doğrulama için GET /payments?id={paymentId}
 */
final class PaparaDriver implements PaymentGatewayInterface
{
    public function id(): string { return 'papara'; }
    public function label(): string { return 'Papara Cüzdan / Kart'; }

    private function baseUrl(): string
    {
        return (bool) SettingsManager::get('papara.sandbox', true, 'PAPARA_SANDBOX')
            ? 'https://merchant-api-test.papara.com'
            : 'https://merchant-api.papara.com';
    }

    public function createCheckout(array $order, array $customer): array
    {
        $apiKey = (string) SettingsManager::get('papara.api_key', '', 'PAPARA_API_KEY');
        if ($apiKey === '') {
            return ['success' => false, 'error' => 'Papara API bilgisi (.env) ayarlanmamış: PAPARA_API_KEY.'];
        }

        $body = [
            'amount'              => round((float) $order['total'], 2),
            'referenceId'         => (string) $order['order_number'],
            'orderDescription'    => 'Ahost Bilişim sipariş #' . $order['order_number'],
            'notificationUrl'     => (string) env('APP_URL') . '/odeme/papara/callback',
            'failNotificationUrl' => (string) env('APP_URL') . '/odeme/papara/callback',
            'redirectUrl'         => (string) env('APP_URL') . '/odeme/papara/return',
            'currency'            => $this->currencyCode((string) $order['currency']),
        ];

        // TC Kimlik zorunlu değil ama varsa gönder
        if (!empty($customer['tc_no'])) {
            $body['turkishNationalId'] = (string) $customer['tc_no'];
        }

        $resp = $this->request('POST', '/payments', $body, $apiKey);
        if (!$resp['ok']) {
            return ['success' => false, 'error' => 'Papara bağlantı hatası: ' . ($resp['error'] ?? '')];
        }
        $data = $resp['data']['data'] ?? [];
        $paymentUrl = $data['paymentUrl'] ?? null;
        if (!$paymentUrl) {
            $msg = $resp['data']['result']['message'] ?? 'Papara ödeme URL alınamadı';
            return ['success' => false, 'error' => $msg];
        }
        return [
            'success'      => true,
            'redirect_url' => $paymentUrl,
            'payment_id'   => $data['id'] ?? null,
        ];
    }

    public function handleCallback(array $payload): array
    {
        // Papara doğrulaması: gelen id'yi GET /payments?id= ile sorgula
        $id = (string) ($payload['data']['id'] ?? $payload['id'] ?? '');
        if ($id === '') {
            return ['success' => false, 'message' => 'Missing payment id'];
        }
        $apiKey = (string) SettingsManager::get('papara.api_key', '', 'PAPARA_API_KEY');
        $resp = $this->request('GET', '/payments?id=' . urlencode($id), null, $apiKey);
        if (!$resp['ok']) {
            Logger::error('Papara callback fetch failed', ['err' => $resp['error'] ?? '']);
            return ['success' => false, 'message' => 'Verify failed'];
        }
        $data = $resp['data']['data'] ?? [];
        // status: 0 pending, 1 completed, 2 expired, 3 refunded, 4 declined
        $status = (int) ($data['status'] ?? -1);
        $success = $status === 1;

        return [
            'success'        => $success,
            'transaction_id' => (string) ($data['id'] ?? $id),
            'message'        => 'status=' . $status,
            'basket_id'      => (string) ($data['referenceId'] ?? ''),
            'raw'            => $data,
        ];
    }

    private function currencyCode(string $iso): int
    {
        // Papara: 0=TRY, 1=USD, 2=EUR, 3=GBP
        return match (strtoupper($iso)) {
            'USD' => 1, 'EUR' => 2, 'GBP' => 3, default => 0,
        };
    }

    private function request(string $method, string $uri, ?array $body, string $apiKey): array
    {
        $url = $this->baseUrl() . $uri;
        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'ApiKey: ' . $apiKey,
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $started = microtime(true);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        ApiLogger::log('papara', $uri, $method, $body ?? [], is_string($raw) ? $raw : '', $code, $ms);

        if ($raw === false || $raw === null) {
            return ['ok' => false, 'error' => $err ?: ('HTTP ' . $code)];
        }
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Invalid JSON'];
        }
        return ['ok' => true, 'data' => $data, 'http' => $code];
    }
}
