<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;
use App\Services\Mail\Mailer;

/**
 * Sepet terk edilme (Abandoned Cart) hatırlatıcı.
 *
 * Kural:
 *   - 24 saat sonra sepetinde ürün olan ama sipariş VERMEMİŞ müşterilere e-posta
 *   - Sadece bir kere gönderilir (reminder_sent_at set edilir)
 *   - Cart'ta customer_id olması şart (guest sepetler için mail yok)
 *   - Cart items 7 günden eski değil (çok eski sepetlere spam olmasın)
 *
 * Sektör ortalaması: %8-15 dönüşüm artışı.
 */
final class AbandonedCartService
{
    /** @return array{checked:int, sent:int, errors:array<int,string>} */
    public static function sendReminders(int $limit = 50): array
    {
        // Kriterler:
        // 1. Cart'ta ürünü olan müşteriler (customer_id NOT NULL)
        // 2. 24 saatten eski, 7 günden yeni
        // 3. Bu müşterinin hiç ödenmiş siparişi YOK (yeni müşteri kaybı önleme)
        // 4. Daha önce reminder gönderilmemiş
        $rows = Connection::select(
            "SELECT DISTINCT ci.customer_id, c.email, c.first_name,
                    COUNT(ci.id) AS item_count,
                    MIN(ci.created_at) AS cart_started
             FROM cart_items ci
             JOIN customers c ON c.id = ci.customer_id
             WHERE ci.customer_id IS NOT NULL
               AND ci.reminder_sent_at IS NULL
               AND ci.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
               AND ci.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND NOT EXISTS (
                   SELECT 1 FROM orders o
                   WHERE o.customer_id = ci.customer_id
                     AND o.status IN ('paid','active')
                     AND o.created_at > ci.created_at
               )
             GROUP BY ci.customer_id, c.email, c.first_name
             LIMIT ?",
            [$limit]
        );

        $result = ['checked' => count($rows), 'sent' => 0, 'errors' => []];

        foreach ($rows as $row) {
            try {
                $sent = self::sendReminderMail($row);
                if ($sent) {
                    Connection::query(
                        "UPDATE cart_items SET reminder_sent_at = NOW() WHERE customer_id = ? AND reminder_sent_at IS NULL",
                        [$row['customer_id']]
                    );
                    $result['sent']++;
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "Customer #{$row['customer_id']}: " . $e->getMessage();
                Logger::warning('Abandoned cart mail failed', ['customer' => $row['customer_id'], 'err' => $e->getMessage()]);
            }
        }

        return $result;
    }

    private static function sendReminderMail(array $row): bool
    {
        $siteName = (string) env('APP_NAME', 'Ahost Bilişim');
        $cartUrl = rtrim((string) env('APP_URL', ''), '/') . '/sepet';
        $subject = '🛒 Sepetiniz sizi bekliyor';
        $bodyHtml = '<div style="font-family:system-ui,-apple-system,sans-serif;max-width:560px;margin:0 auto;padding:20px">
            <h2 style="color:#0ea5e9">Merhaba ' . htmlspecialchars((string)($row['first_name'] ?? ''), ENT_HTML5) . ',</h2>
            <p>Sepetinize bir şeyler eklediniz ama tamamlamayı unutmuş olabilirsiniz. Sepetinizde <strong>' . (int)$row['item_count'] . ' ürün</strong> sizi bekliyor.</p>
            <p style="text-align:center;margin:32px 0">
                <a href="' . $cartUrl . '" style="display:inline-block;padding:14px 32px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">🛒 Sepete Dön</a>
            </p>
            <p style="color:#6b7280;font-size:13px">Hâlâ ilgileniyor musunuz? Siparişinizi tamamlarsanız hosting altyapımızla web sitenizi hemen hayata geçirebiliriz.</p>
            <hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0">
            <p style="color:#9ca3af;font-size:11px;text-align:center">
                Bu e-posta size ' . htmlspecialchars($siteName, ENT_HTML5) . ' sepetinizde ürün bıraktığınız için gönderildi.<br>
                Bir daha almak istemiyorsanız <a href="' . $cartUrl . '" style="color:#9ca3af">bu bağlantıya</a> tıklayıp sepeti boşaltabilirsiniz.
            </p>
        </div>';
        $r = Mailer::sendRaw((string) $row['email'], $subject, $bodyHtml, (string) $row['first_name'] ?? null);
        return (bool) ($r['ok'] ?? false);
    }
}
