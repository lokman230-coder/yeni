<?php

declare(strict_types=1);

namespace App\Modules\Payment\Drivers;

use App\Core\Config;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Logger\ApiLogger;
use App\Services\Logger\Logger;
use App\Services\Settings\SettingsManager;

/**
 * iyzico Checkout Form entegrasyonu.
 *
 * Doküman: https://docs.iyzico.com/urunler/odeme-formu/hosted-checkout
 * Base URL: sandbox → https://sandbox-api.iyzipay.com, prod → https://api.iyzipay.com
 *
 * Bu driver "checkout form initialize" endpoint'ini çağırır; iyzico'dan gelen
 * paymentPageUrl'e kullanıcıyı yönlendirir. 3D + taksit + kart saklama iyzico
 * tarafında hallolur, callback ile sonuç bildirilir.
 */
final class IyzicoDriver implements PaymentGatewayInterface
{
    public function id(): string { return 'iyzico'; }
    public function label(): string { return 'iyzico Kredi Kartı / Taksit'; }

    private function baseUrl(): string
    {
        $sandbox = (bool) SettingsManager::get('iyzico.sandbox', true, 'IYZICO_SANDBOX');
        return $sandbox
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';
    }

    public function createCheckout(array $order, array $customer): array
    {
        $apiKey    = (string) SettingsManager::get('iyzico.api_key', '', 'IYZICO_API_KEY');
        $secretKey = (string) SettingsManager::get('iyzico.secret_key', '', 'IYZICO_SECRET_KEY');

        if ($apiKey === '' || $secretKey === '') {
            return ['success' => false, 'error' => 'iyzico API bilgileri (.env) ayarlanmamış: IYZICO_API_KEY, IYZICO_SECRET_KEY.'];
        }

        $conversationId = 'AHO-' . $order['id'] . '-' . bin2hex(random_bytes(4));
        $price = number_format((float) $order['total'], 2, '.', '');
        $currency = strtoupper((string) $order['currency']);
        $callbackUrl = (string) env('APP_URL') . '/odeme/iyzico/callback';

        $fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Musteri';
        $address  = (string) ($customer['address'] ?? 'Adres yok');
        $city     = (string) ($customer['city'] ?? 'Istanbul');
        $country  = (string) ($customer['country'] ?? 'Turkey');
        $phone    = (string) ($customer['phone'] ?? '+905555555555');
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '85.34.78.112';
        $identity = (string) ($customer['tc_no'] ?? '11111111111');

        $body = [
            'locale'          => 'tr',
            'conversationId'  => $conversationId,
            'price'           => $price,
            'paidPrice'       => $price,
            'currency'        => $currency,
            'basketId'        => (string) $order['order_number'],
            'paymentGroup'    => 'PRODUCT',
            'callbackUrl'     => $callbackUrl,
            'enabledInstallments' => [2, 3, 6, 9, 12],
            'buyer' => [
                'id'                  => 'AHO-CUS-' . ($customer['id'] ?? '0'),
                'name'                => (string) ($customer['first_name'] ?? 'Musteri'),
                'surname'             => (string) ($customer['last_name'] ?? 'Ahost'),
                'gsmNumber'           => $phone,
                'email'               => (string) ($customer['email'] ?? 'noemail@ahost.web.tr'),
                'identityNumber'      => $identity,
                'registrationAddress' => $address,
                'ip'                  => $ip,
                'city'                => $city,
                'country'             => $country,
                'zipCode'             => (string) ($customer['zip'] ?? '34000'),
            ],
            'shippingAddress' => [
                'contactName' => $fullName,
                'city'        => $city,
                'country'     => $country,
                'address'     => $address,
                'zipCode'     => (string) ($customer['zip'] ?? '34000'),
            ],
            'billingAddress' => [
                'contactName' => $fullName,
                'city'        => $city,
                'country'     => $country,
                'address'     => $address,
                'zipCode'     => (string) ($customer['zip'] ?? '34000'),
            ],
            'basketItems' => [[
                'id'        => (string) $order['id'],
                'name'      => 'Sipariş ' . $order['order_number'],
                'category1' => 'Hosting',
                'itemType'  => 'VIRTUAL',
                'price'     => $price,
            ]],
        ];

        $uri = '/payment/iyzipos/checkoutform/initialize/auth/ecom';
        $resp = $this->request('POST', $uri, $body, $apiKey, $secretKey);

        if (!$resp['ok']) {
            return ['success' => false, 'error' => 'iyzico bağlantı hatası: ' . ($resp['error'] ?? '')];
        }
        $data = $resp['data'];
        if (($data['status'] ?? '') !== 'success') {
            return ['success' => false, 'error' => 'iyzico: ' . ($data['errorMessage'] ?? 'bilinmeyen hata')];
        }

        return [
            'success'      => true,
            'redirect_url' => $data['paymentPageUrl'] ?? null,
            'token'        => $data['token'] ?? null,
            'conversation_id' => $conversationId,
        ];
    }

    public function handleCallback(array $payload): array
    {
        $token = (string) ($payload['token'] ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'Missing token'];
        }
        $apiKey    = (string) SettingsManager::get('iyzico.api_key', '', 'IYZICO_API_KEY');
        $secretKey = (string) SettingsManager::get('iyzico.secret_key', '', 'IYZICO_SECRET_KEY');

        $body = [
            'locale'         => 'tr',
            'conversationId' => (string) ($payload['conversationId'] ?? ''),
            'token'          => $token,
        ];
        $uri = '/payment/iyzipos/checkoutform/auth/ecom/detail';
        $resp = $this->request('POST', $uri, $body, $apiKey, $secretKey);

        if (!$resp['ok']) {
            Logger::error('iyzico callback fetch failed', ['err' => $resp['error'] ?? '']);
            return ['success' => false, 'message' => 'Detail fetch failed'];
        }
        $data = $resp['data'];
        $status = (string) ($data['paymentStatus'] ?? $data['status'] ?? '');
        $success = $status === 'SUCCESS' || $status === 'success';

        return [
            'success'        => $success,
            'transaction_id' => (string) ($data['paymentId'] ?? $token),
            'message'        => $data['errorMessage'] ?? ($success ? 'OK' : 'Failed'),
            'basket_id'      => (string) ($data['basketId'] ?? ''),
            'raw'            => $data,
        ];
    }

    /**
     * iyzico Authorization header (PKI + HMAC-SHA256).
     * Doküman: https://docs.iyzico.com/servisler/genel-bilgiler/pki-string
     */
    private function request(string $method, string $uri, array $body, string $apiKey, string $secretKey): array
    {
        $url = $this->baseUrl() . $uri;
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $randomKey = (string) time() . bin2hex(random_bytes(4));
        $payload   = $randomKey . $uri . $json;
        $signature = hash_hmac('sha256', $payload, $secretKey);
        $authStr   = 'apiKey:' . $apiKey . '&randomKey:' . $randomKey . '&signature:' . $signature;
        $authorization = 'IYZWSv2 ' . base64_encode($authStr);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'x-iyzi-rnd: ' . $randomKey,
                'Authorization: ' . $authorization,
            ],
        ]);
        $started = microtime(true);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        ApiLogger::log('iyzico', $uri, $method, $body, is_string($raw) ? $raw : '', $code, $ms);

        if ($raw === false || $raw === null) {
            return ['ok' => false, 'error' => $err ?: ('HTTP ' . $code)];
        }
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Invalid JSON response'];
        }
        return ['ok' => true, 'data' => $data, 'http' => $code];
    }
}
