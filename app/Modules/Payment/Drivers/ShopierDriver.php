<?php

declare(strict_types=1);

namespace App\Modules\Payment\Drivers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Logger\Logger;
use App\Services\Settings\SettingsManager;

/**
 * Shopier payment gateway.
 *
 * Current Shopier API access is PAT based. The legacy api_pay4 payment form
 * still expects API_key + HMAC signature. For installs that only have a PAT,
 * the PAT is used as both the API_key and signing secret so the admin panel no
 * longer asks for unavailable API key/secret fields.
 */
final class ShopierDriver implements PaymentGatewayInterface
{
    private const ENDPOINT = 'https://www.shopier.com/ShowProduct/api_pay4.php';

    public function id(): string    { return 'shopier'; }
    public function label(): string { return 'Shopier'; }

    public function createCheckout(array $order, array $customer): array
    {
        [$apiKey, $apiSecret] = self::credentials();
        if ($apiKey === '' || $apiSecret === '') {
            return ['success' => false, 'error' => 'Shopier PAT ayarlanmamis. Admin > Ayarlar > Odeme > Shopier alanina PAT girin.'];
        }

        $randomNr = (string) random_int(100000, 999999);
        $callbackUrl = rtrim((string) env('APP_URL', ''), '/') . '/odeme/shopier/callback';

        $fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        [$fname, $lname] = self::splitName($fullName);

        $args = [
            'API_key' => $apiKey,
            'website_index' => '1',
            'platform_order_id' => (string) $order['order_number'],
            'product_name' => 'Siparis ' . $order['order_number'],
            'product_type' => '1',
            'buyer_name' => $fname,
            'buyer_surname' => $lname,
            'buyer_email' => (string) ($customer['email'] ?? ''),
            'buyer_account_age' => '5',
            'buyer_id_nr' => (string) ($customer['id'] ?? '0'),
            'buyer_phone' => (string) ($customer['phone'] ?? '5555555555'),
            'billing_address' => (string) ($customer['address'] ?? 'Adres'),
            'billing_city' => (string) ($customer['city'] ?? 'Istanbul'),
            'billing_country' => 'Turkey',
            'billing_postcode' => (string) ($customer['zip'] ?? '34000'),
            'shipping_address' => (string) ($customer['address'] ?? 'Adres'),
            'shipping_city' => (string) ($customer['city'] ?? 'Istanbul'),
            'shipping_country' => 'Turkey',
            'shipping_postcode' => (string) ($customer['zip'] ?? '34000'),
            'total_order_value' => number_format((float) $order['total'], 2, '.', ''),
            'currency' => self::currencyCode((string) $order['currency']),
            'platform' => '0',
            'is_in_frame' => '1',
            'current_language' => '1',
            'modul_version' => '1.0.5',
            'random_nr' => $randomNr,
            'callback_url' => $callbackUrl,
        ];

        $data = $randomNr . $args['platform_order_id'] . $args['total_order_value'] . $args['currency'];
        $args['signature'] = base64_encode(hash_hmac('SHA256', $data, $apiSecret, true));

        $html = '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Shopier odeme sayfasina yonlendiriliyor...</title></head><body>'
            . '<form id="shopierForm" method="post" action="' . htmlspecialchars(self::ENDPOINT, ENT_HTML5) . '">';
        foreach ($args as $k => $v) {
            $html .= '<input type="hidden" name="' . htmlspecialchars((string) $k, ENT_HTML5) . '" value="' . htmlspecialchars((string) $v, ENT_HTML5) . '">';
        }
        $html .= '</form><div style="font-family:system-ui,sans-serif;text-align:center;padding:60px">'
            . '<div style="font-size:32px">...</div><h2>Shopier odeme sayfasina yonlendiriliyorsunuz...</h2>'
            . '<p style="color:#6b7280">Otomatik yonlendirme baslamadiysa asagidaki butona tiklayin.</p>'
            . '<button onclick="document.getElementById(\'shopierForm\').submit()" style="padding:12px 24px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">Devam Et</button>'
            . '</div><script>document.getElementById("shopierForm").submit();</script></body></html>';

        return [
            'success' => true,
            'html_form' => $html,
            'redirect_url' => null,
        ];
    }

    public function handleCallback(array $payload): array
    {
        [, $apiSecret] = self::credentials();
        $status = strtolower((string) ($payload['status'] ?? ''));
        $orderNumber = (string) ($payload['platform_order_id'] ?? '');
        $paymentId = (string) ($payload['payment_id'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');
        $randomNr = (string) ($payload['random_nr'] ?? '');

        if ($apiSecret === '' || $signature === '' || $randomNr === '' || $orderNumber === '') {
            return ['success' => false, 'message' => 'Eksik callback parametreleri'];
        }

        $data = $randomNr . $orderNumber;
        $expected = hash_hmac('SHA256', $data, $apiSecret, true);
        $received = base64_decode($signature, true);

        if ($received === false || !hash_equals($expected, $received)) {
            Logger::warning('Shopier signature mismatch', ['order' => $orderNumber]);
            return ['success' => false, 'message' => 'Invalid signature'];
        }

        return [
            'success' => $status === 'success',
            'transaction_id' => $paymentId,
            'basket_id' => $orderNumber,
            'message' => $status,
            'raw' => $payload,
        ];
    }

    private static function credentials(): array
    {
        $pat = (string) SettingsManager::get('shopier.pat', '', 'SHOPIER_PAT');
        $apiKey = (string) SettingsManager::get('shopier.api_key', '', 'SHOPIER_API_KEY');
        $apiSecret = (string) SettingsManager::get('shopier.api_secret', '', 'SHOPIER_API_SECRET');

        if ($apiKey === '' && $pat !== '') {
            $apiKey = $pat;
        }
        if ($apiSecret === '' && $pat !== '') {
            $apiSecret = $pat;
        }

        return [$apiKey, $apiSecret];
    }

    private static function currencyCode(string $iso): string
    {
        return match (strtoupper($iso)) {
            'USD' => '2',
            'EUR' => '3',
            default => '1',
        };
    }

    private static function splitName(string $full): array
    {
        $parts = explode(' ', trim($full), 2);
        return [$parts[0] ?? 'Musteri', $parts[1] ?? '-'];
    }
}
