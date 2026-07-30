<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Database\Connection;
use App\Services\Credit\CreditService;

/**
 * Otomatik tahsilat cron servisi.
 *
 * Cron:  `php console billing:auto-charge` — her gün 09:00'da çalışır.
 *
 * Akış:
 *   1. Vadesi bugün-3gün olan tüm unpaid faturaları bul
 *   2. Her müşteri için:
 *      a. Önce BAKİYE'ye bak — yeterli mi? → bakiyeden düş
 *      b. Yetersizse: default stored_card + auto_billing_enabled var mı?
 *         → gateway'e recurring charge isteği gönder
 *      c. Başarısızsa: mail + SMS uyarı yolla
 *   3. Tüm denemeler recurring_charge_attempts'e log'lanır
 */
final class RecurringChargeService
{
    /** Bugün ve önümüzdeki N gün içinde vadesi gelecek faturaları işle */
    public static function run(int $daysAhead = 3): array
    {
        $rows = Connection::select(
            "SELECT i.*, c.email AS customer_email, c.first_name, c.phone,
                    (SELECT sc.id FROM stored_cards sc WHERE sc.customer_id = i.customer_id AND sc.auto_billing_enabled = 1 AND sc.is_default = 1 LIMIT 1) AS default_card_id
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             WHERE i.status IN ('unpaid','partially_paid','overdue')
               AND i.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND i.balance > 0
             ORDER BY i.due_date ASC
             LIMIT 500",
            [$daysAhead]
        );

        $success = 0; $failed = 0; $skipped = 0;

        foreach ($rows as $inv) {
            $result = self::processInvoice($inv);
            if ($result === 'success') $success++;
            elseif ($result === 'skipped') $skipped++;
            else $failed++;
        }

        return ['success' => $success, 'failed' => $failed, 'skipped' => $skipped, 'total' => count($rows)];
    }

    private static function processInvoice(array $invoice): string
    {
        $customerId = (int) $invoice['customer_id'];
        $amount     = (float) $invoice['balance'];

        // 1) Bakiye yeterli mi?
        if (CreditService::canPay($customerId, $amount)) {
            $r = CreditService::payInvoice($customerId, (int) $invoice['id'], $amount,
                "Otomatik tahsilat (bakiye ile) — vade: {$invoice['due_date']}"
            );
            if ($r['ok']) {
                // Fatura status güncelle
                self::markInvoicePaid((int)$invoice['id'], $amount, 'balance', null);
                self::logAttempt((int)$invoice['id'], $customerId, null, 'balance', $amount, 'success');
                self::notifyCustomer($invoice, 'success', 'Bakiye');
                return 'success';
            }
        }

        // 2) Saklı kart var mı + auto_billing on mu?
        if (empty($invoice['default_card_id'])) {
            self::logAttempt((int)$invoice['id'], $customerId, null, 'skipped', $amount, 'no_card',
                'Otomatik tahsilata izinli kart yok — müşteri manuel ödemeli');
            // 3 gün öncesinden hatırlatma
            if (self::dueSoon($invoice['due_date'], 3)) {
                self::notifyCustomer($invoice, 'reminder', null);
            }
            return 'skipped';
        }

        $card = Connection::selectOne("SELECT * FROM stored_cards WHERE id = ?", [$invoice['default_card_id']]);
        if (!$card) return 'skipped';

        // 3) Gateway'e recurring charge (basitleştirilmiş — gerçek üretim gateway_key'i kullanır)
        $chargeResult = self::chargeGateway($card, $amount, $invoice);

        if ($chargeResult['ok']) {
            self::markInvoicePaid((int)$invoice['id'], $amount, $card['gateway'], $chargeResult['tx_id'] ?? null);
            Connection::update('stored_cards',
                ['last_used_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?', [$card['id']]
            );
            self::logAttempt((int)$invoice['id'], $customerId, (int)$card['id'], 'stored_card', $amount, 'success', 'TX: ' . ($chargeResult['tx_id'] ?? '?'));
            self::notifyCustomer($invoice, 'success', $card['card_brand'] . ' ****' . $card['card_last4']);
            return 'success';
        }

        self::logAttempt((int)$invoice['id'], $customerId, (int)$card['id'], 'stored_card', $amount, 'gateway_error', $chargeResult['error'] ?? '?');
        self::notifyCustomer($invoice, 'failed', $card['card_brand'] . ' ****' . $card['card_last4']);
        return 'failed';
    }

    private static function chargeGateway(array $card, float $amount, array $invoice): array
    {
        // NOT: Gerçek üretim ortamında iyzico/PayTR recurring API'sini çağır.
        // Şu an placeholder — driver'a delege edecek şekilde tasarlandı.
        $gateway = (string) $card['gateway'];
        try {
            // Her driver'da chargeStored($cardKey, $amount, $invoiceRef) metodu implement edilebilir.
            // Şimdilik "simülasyon" — sadece log
            return [
                'ok'    => false,
                'error' => "Recurring charge $gateway için gateway credentials + implementation gerekli. " .
                           "Şimdilik bakiye ile deneniyor, kart varsa manuel ödeme akışı devreye giriyor.",
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private static function markInvoicePaid(int $invoiceId, float $amount, string $method, ?string $txId): void
    {
        $invoice = Connection::selectOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
        if (!$invoice) return;

        Connection::insert('payments', [
            'invoice_id'             => $invoiceId,
            'order_id'               => $invoice['order_id'],
            'customer_id'            => $invoice['customer_id'],
            'method'                 => $method,
            'amount'                 => $amount,
            'currency'               => $invoice['currency'],
            'gateway_transaction_id' => $txId,
            'status'                 => 'success',
            'processed_at'           => date('Y-m-d H:i:s'),
            'notes'                  => 'Otomatik tahsilat',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        $newPaid = (float) $invoice['paid_total'] + $amount;
        $newBalance = max(0, (float)$invoice['total'] - $newPaid);
        Connection::update('invoices', [
            'paid_total' => $newPaid,
            'balance'    => $newBalance,
            'status'     => $newBalance <= 0.01 ? 'paid' : 'partially_paid',
            'paid_at'    => $newBalance <= 0.01 ? date('Y-m-d H:i:s') : $invoice['paid_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$invoiceId]);

        if ($newBalance <= 0.01 && $invoice['order_id']) {
            Connection::update('orders', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                'id = ? AND status IN (?, ?)', [$invoice['order_id'], 'pending', 'processing']
            );
            // Provisioning tetiklenmiş olur (markPaid via cron)
        }
    }

    private static function logAttempt(int $invoiceId, int $customerId, ?int $cardId, string $method, float $amount, string $result, ?string $response = null): void
    {
        try {
            Connection::insert('recurring_charge_attempts', [
                'invoice_id'     => $invoiceId,
                'customer_id'    => $customerId,
                'stored_card_id' => $cardId,
                'method_used'    => $method,
                'amount'         => $amount,
                'currency'       => 'TRY',
                'result'         => $result,
                'response'       => $response,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {}
    }

    private static function notifyCustomer(array $invoice, string $type, ?string $methodLabel): void
    {
        $subject = match($type) {
            'success'  => "✓ Otomatik ödeme başarılı — Fatura #{$invoice['invoice_number']}",
            'failed'   => "⚠ Otomatik ödeme başarısız — Fatura #{$invoice['invoice_number']}",
            'reminder' => "🔔 Fatura vadesi yaklaşıyor — #{$invoice['invoice_number']}",
            default    => "Fatura bildirimi",
        };
        $body = match($type) {
            'success'  => "Merhaba,<br><br><strong>{$invoice['invoice_number']}</strong> numaralı faturanız " . number_format((float)$invoice['balance'], 2) . " TL tutarında $methodLabel ile başarıyla ödendi.<br><br>Teşekkürler.",
            'failed'   => "Merhaba,<br><br><strong>{$invoice['invoice_number']}</strong> numaralı faturanız için otomatik tahsilat başarısız oldu ($methodLabel).<br><br>Lütfen panelinizden manuel ödeme yapın.<br><br>Tutar: " . number_format((float)$invoice['balance'], 2) . " TL",
            'reminder' => "Merhaba,<br><br><strong>{$invoice['invoice_number']}</strong> numaralı faturanızın vadesi yaklaşıyor: <strong>{$invoice['due_date']}</strong>.<br><br>Tutar: " . number_format((float)$invoice['balance'], 2) . " TL",
            default    => '',
        };

        try {
            Connection::insert('mail_queue', [
                'to_email'   => $invoice['customer_email'],
                'subject'    => $subject,
                'body_html'  => $body,
                'status'     => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {}

        // SMS (opsiyonel)
        if (!empty($invoice['phone']) && class_exists(\App\Services\Sms\SmsManager::class)) {
            $smsMsg = match($type) {
                'success' => "Ahost: Fatura #{$invoice['invoice_number']} otomatik odendi.",
                'failed'  => "Ahost: Fatura #{$invoice['invoice_number']} otomatik odeme basarisiz. Lutfen panelden odeyin.",
                'reminder'=> "Ahost: Fatura #{$invoice['invoice_number']} vadesi {$invoice['due_date']}. Odeme yapmayi unutmayin.",
                default   => null,
            };
            if ($smsMsg) {
                try {
                    \App\Services\Sms\SmsManager::send((string)$invoice['phone'], $smsMsg);
                } catch (\Throwable) {}
            }
        }
    }

    private static function dueSoon(string $due, int $days): bool
    {
        return (strtotime($due) - time()) < ($days * 86400);
    }
}
