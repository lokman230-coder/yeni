<?php

declare(strict_types=1);

namespace App\Modules\Payment\Drivers;

use App\Core\Config;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Logger\Logger;
use App\Services\Settings\SettingsManager;

/**
 * PayTR iframe entegrasyonu.
 *
 * Dokümantasyon: https://dev.paytr.com/iframe-api/
 * Test modu için config: paytr.test_mode=true
 */
final class PayTrDriver implements PaymentGatewayInterface
{
    public function id(): string { return 'paytr'; }
    public function label(): string { return 'PayTR Kredi Kartı'; }

    public function createCheckout(array $order, array $customer): array
    {
        $merchantId   = (string) SettingsManager::get('paytr.merchant_id', '', 'PAYTR_MERCHANT_ID');
        $merchantKey  = (string) SettingsManager::get('paytr.merchant_key', '', 'PAYTR_MERCHANT_KEY');
        $merchantSalt = (string) SettingsManager::get('paytr.merchant_salt', '', 'PAYTR_MERCHANT_SALT');
        $testMode     = (int) (SettingsManager::get('paytr.test_mode', true, 'PAYTR_TEST_MODE') ? 1 : 0);

        if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
            return ['success' => false, 'error' => 'PayTR bilgileri (.env) ayarlanmamış.'];
        }

        $merchantOid = 'AHO' . str_pad((string) $order['id'], 8, '0', STR_PAD_LEFT);
        $email = $customer['email'];
        $paymentAmount = (int) round(((float) $order['total']) * 100); // kuruş
        $currency = strtoupper((string) $order['currency']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $userName    = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Musteri';
        $userAddress = $customer['address'] ?? 'Adres yok';
        $userPhone   = $customer['phone'] ?? '5555555555';

        $userBasket = base64_encode(json_encode([[$order['order_number'], (string) $order['total'], 1]]));

        $noInstallment  = 0;
        $maxInstallment = 0;
        $timeoutLimit   = 30;
        $debugOn        = $testMode;

        $successUrl = (string) env('APP_URL') . '/odeme/paytr/basarili';
        $failUrl    = (string) env('APP_URL') . '/odeme/paytr/basarisiz';

        $hashStr = $merchantId . $ip . $merchantOid . $email . $paymentAmount .
                   $userBasket . $noInstallment . $maxInstallment . $currency . $testMode;
        $token = base64_encode(hash_hmac('sha256', $hashStr . $merchantSalt, $merchantKey, true));

        $postFields = [
            'merchant_id'      => $merchantId,
            'user_ip'          => $ip,
            'merchant_oid'     => $merchantOid,
            'email'            => $email,
            'payment_amount'   => $paymentAmount,
            'paytr_token'      => $token,
            'user_basket'      => $userBasket,
            'debug_on'         => $debugOn,
            'no_installment'   => $noInstallment,
            'max_installment'  => $maxInstallment,
            'user_name'        => $userName,
            'user_address'     => $userAddress,
            'user_phone'       => $userPhone,
            'merchant_ok_url'  => $successUrl,
            'merchant_fail_url'=> $failUrl,
            'timeout_limit'    => $timeoutLimit,
            'currency'         => $currency,
            'test_mode'        => $testMode,
        ];

        Logger::info('PayTR checkout request', ['order' => $order['order_number']]);

        $ch = curl_init('https://www.paytr.com/odeme/api/get-token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            return ['success' => false, 'error' => 'PayTR bağlantı hatası: ' . $curlErr];
        }

        $decoded = json_decode((string) $result, true) ?: [];
        if (($decoded['status'] ?? '') !== 'success') {
            return ['success' => false, 'error' => 'PayTR: ' . ($decoded['reason'] ?? 'Bilinmeyen hata')];
        }

        return [
            'success'      => true,
            'iframe_token' => $decoded['token'],
            'iframe_url'   => 'https://www.paytr.com/odeme/guvenli/' . $decoded['token'],
        ];
    }

    public function handleCallback(array $payload): array
    {
        $merchantKey  = (string) SettingsManager::get('paytr.merchant_key', '', 'PAYTR_MERCHANT_KEY');
        $merchantSalt = (string) SettingsManager::get('paytr.merchant_salt', '', 'PAYTR_MERCHANT_SALT');

        $post = $payload;
        $hash = base64_encode(hash_hmac(
            'sha256',
            ($post['merchant_oid'] ?? '') . $merchantSalt . ($post['status'] ?? '') . ($post['total_amount'] ?? ''),
            $merchantKey,
            true
        ));

        if (!hash_equals($hash, (string) ($post['hash'] ?? ''))) {
            Logger::warning('PayTR callback hash mismatch', ['oid' => $post['merchant_oid'] ?? '']);
            return ['success' => false, 'message' => 'Invalid hash'];
        }

        return [
            'success'        => ($post['status'] ?? '') === 'success',
            'transaction_id' => $post['merchant_oid'] ?? '',
            'amount'         => (int) ($post['total_amount'] ?? 0) / 100,
            'message'        => $post['failed_reason_msg'] ?? '',
        ];
    }
}
