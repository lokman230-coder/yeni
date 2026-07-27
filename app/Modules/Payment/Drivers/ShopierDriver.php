<?php

declare(strict_types=1);

namespace App\Modules\Payment\Drivers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Logger\Logger;
use App\Services\Settings\SettingsManager;

/**
 * Shopier ödeme sağlayıcısı.
 *
 * Doküman: https://destek.shopier.com/hc/tr/articles/207965262
 *
 * Akış:
 *   1. createCheckout → POST form (Shopier.com/ShowPayment) auto-submit
 *   2. Kullanıcı Shopier tarafında öder
 *   3. Callback URL'e POST → HMAC-SHA256 signature verify
 *      → data = random_nr + platform_order_id
 *      → hmac(SHA256, data, api_secret, raw=true) → base64_encode
 */
final class ShopierDriver implements PaymentGatewayInterface
{
    private const ENDPOINT = 'https://www.shopier.com/ShowProduct/api_pay4.php';

    public function id(): string    { return 'shopier'; }
    public function label(): string { return 'Shopier'; }

    public function createCheckout(array $order, array $customer): array
    {
        $apiKey    = (string) SettingsManager::get('shopier.api_key',    '', 'SHOPIER_API_KEY');
        $apiSecret = (string) SettingsManager::get('shopier.api_secret', '', 'SHOPIER_API_SECRET');

        if ($apiKey === '' || $apiSecret === '') {
            return ['success' => false, 'error' => 'Shopier API bilgileri (admin panelden) ayarlanmamış.'];
        }

        $randomNr = (string) random_int(100000, 999999);
        $callbackUrl = rtrim((string) env('APP_URL', ''), '/') . '/odeme/shopier/callback';

        $fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        [$fname, $lname] = self::splitName($fullName);

        $args = [
            'API_key'                         => $apiKey,
            'website_index'                   => '1',
            'platform_order_id'               => (string) $order['order_number'],
            'product_name'                    => 'Sipariş ' . $order['order_number'],
            'product_type'                    => '0', // 0=fiziksel 1=indirilebilir
            'buyer_name'                      => $fname,
            'buyer_surname'                   => $lname,
            'buyer_email'                     => (string) ($customer['email'] ?? ''),
            'buyer_account_age'               => '5',
            'buyer_id_nr'                     => (string) ($customer['id'] ?? '0'),
            'buyer_phone'                     => (string) ($customer['phone'] ?? '5555555555'),
            'billing_address'                 => (string) ($customer['address'] ?? 'Adres'),
            'billing_city'                    => (string) ($customer['city'] ?? 'Istanbul'),
            'billing_country'                 => 'Turkey',
            'billing_postcode'                => (string) ($customer['zip'] ?? '34000'),
            'shipping_address'                => (string) ($customer['address'] ?? 'Adres'),
            'shipping_city'                   => (string) ($customer['city'] ?? 'Istanbul'),
            'shipping_country'                => 'Turkey',
            'shipping_postcode'               => (string) ($customer['zip'] ?? '34000'),
            'total_order_value'               => number_format((float) $order['total'], 2, '.', ''),
            'currency'                        => self::currencyCode((string) $order['currency']),
            'platform'                        => '0',
            'is_in_frame'                     => '1',
            'current_language'                => '1', // 1=tr, 0=en
            'modul_version'                   => '1.0.4',
            'random_nr'                       => $randomNr,
            'callback_url'                    => $callbackUrl,
        ];

        // İmza: random_nr + platform_order_id + total_order_value + currency
        $data = $randomNr . $args['platform_order_id'] . $args['total_order_value'] . $args['currency'];
        $args['signature'] = base64_encode(hash_hmac('SHA256', $data, $apiSecret, true));

        // Auto-submit HTML form üret — kullanıcı bunu görürse otomatik gönderilir
        $html = '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Shopier\'e yönlendiriliyor...</title></head><body>
            <form id="shopierForm" method="post" action="' . htmlspecialchars(self::ENDPOINT, ENT_HTML5) . '">';
        foreach ($args as $k => $v) {
            $html .= '<input type="hidden" name="' . htmlspecialchars((string) $k, ENT_HTML5) . '" value="' . htmlspecialchars((string) $v, ENT_HTML5) . '">';
        }
        $html .= '</form>
            <div style="font-family:system-ui,sans-serif;text-align:center;padding:60px">
                <div style="font-size:32px">⏳</div>
                <h2>Shopier ödeme sayfasına yönlendiriliyorsunuz...</h2>
                <p style="color:#6b7280">Otomatik yönlendirme başlamadıysa aşağıdaki butona tıklayın.</p>
                <button onclick="document.getElementById(\'shopierForm\').submit()" style="padding:12px 24px;background:#0ea5e9;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">Devam Et</button>
            </div>
            <script>document.getElementById("shopierForm").submit();</script>
            </body></html>';

        return [
            'success'      => true,
            'html_form'    => $html,   // Response::html ile döndür
            'redirect_url' => null,    // Shopier'de POST gerektiği için pure redirect değil
        ];
    }

    public function handleCallback(array $payload): array
    {
        $apiSecret = (string) SettingsManager::get('shopier.api_secret', '', 'SHOPIER_API_SECRET');
        $status = strtolower((string) ($payload['status'] ?? ''));
        $orderNumber = (string) ($payload['platform_order_id'] ?? '');
        $paymentId = (string) ($payload['payment_id'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');
        $randomNr = (string) ($payload['random_nr'] ?? '');

        // İmza doğrulama
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
            'success'        => $status === 'success',
            'transaction_id' => $paymentId,
            'basket_id'      => $orderNumber,
            'message'        => $status,
            'raw'            => $payload,
        ];
    }

    private static function currencyCode(string $iso): string
    {
        return match (strtoupper($iso)) {
            'USD' => '2', 'EUR' => '3', default => '1', // 1=TRY
        };
    }

    private static function splitName(string $full): array
    {
        $parts = explode(' ', trim($full), 2);
        return [$parts[0] ?? 'Musteri', $parts[1] ?? '-'];
    }
}
