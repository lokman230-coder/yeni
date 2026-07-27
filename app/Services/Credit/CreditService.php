<?php

declare(strict_types=1);

namespace App\Services\Credit;

use App\Core\Database\Connection;

/**
 * Müşteri kredi/bakiye yönetimi.
 * Her hareket customer_credits'e insert, customers.balance sütunu senkron güncellenir.
 */
final class CreditService
{
    /**
     * Müşteri bakiyesine ekleme/çıkarma yap.
     *
     * @param int    $customerId
     * @param float  $amount       Pozitif = yükleme, Negatif = düşürme
     * @param string $source       admin_manual/payment/invoice_pay/refund/promo
     * @param array  $meta         [admin_id?, invoice_id?, payment_id?, description?]
     * @return array{ok:bool, balance:float, credit_id?:int, error?:string}
     */
    public static function record(int $customerId, float $amount, string $source, array $meta = []): array
    {
        if ($customerId <= 0) {
            return ['ok' => false, 'balance' => 0.0, 'error' => 'Geçersiz müşteri.'];
        }
        if ($amount == 0.0) {
            return ['ok' => false, 'balance' => 0.0, 'error' => 'Tutar sıfır olamaz.'];
        }

        $customer = Connection::selectOne("SELECT id, balance FROM customers WHERE id = ?", [$customerId]);
        if (!$customer) {
            return ['ok' => false, 'balance' => 0.0, 'error' => 'Müşteri bulunamadı.'];
        }

        $currentBalance = (float) $customer['balance'];
        $newBalance = $currentBalance + $amount;

        // Kredi düşürme durumunda bakiye 0 altına inebilir mi? Business kurallara göre
        // Şimdilik izin veriyoruz (negatif bakiye = müşteri borçlu)

        try {
            Connection::pdo()->beginTransaction();

            $creditId = Connection::insert('customer_credits', [
                'customer_id'    => $customerId,
                'amount'         => $amount,
                'currency'       => 'TRY',
                'balance_after'  => $newBalance,
                'source'         => $source,
                'admin_id'       => $meta['admin_id'] ?? null,
                'invoice_id'     => $meta['invoice_id'] ?? null,
                'payment_id'     => $meta['payment_id'] ?? null,
                'description'    => $meta['description'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            Connection::update('customers', ['balance' => $newBalance], 'id = ?', [$customerId]);

            Connection::pdo()->commit();
        } catch (\Throwable $e) {
            Connection::pdo()->rollBack();
            return ['ok' => false, 'balance' => $currentBalance, 'error' => 'DB hatası: ' . $e->getMessage()];
        }

        // Audit
        try {
            \App\Services\Logger\ActivityLog::log(
                'credit.' . $source,
                'customer',
                $customerId,
                sprintf('%s: %s%.2f TRY (yeni bakiye: %.2f)',
                    $source,
                    $amount > 0 ? '+' : '',
                    $amount,
                    $newBalance
                ),
                $meta
            );
        } catch (\Throwable) {}

        return ['ok' => true, 'balance' => $newBalance, 'credit_id' => $creditId];
    }

    /** Müşterinin bakiyesi yeter mi kontrol */
    public static function canPay(int $customerId, float $amount): bool
    {
        $c = Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$customerId]);
        return $c && (float) $c['balance'] >= $amount;
    }

    /** Bakiye ile fatura öde (- işaretli hareket) */
    public static function payInvoice(int $customerId, int $invoiceId, float $amount, ?string $description = null): array
    {
        return self::record($customerId, -abs($amount), 'invoice_pay', [
            'invoice_id'  => $invoiceId,
            'description' => $description ?: "Fatura #$invoiceId bakiye ile ödendi",
        ]);
    }

    /** Müşterinin son N hareketi */
    public static function history(int $customerId, int $limit = 50): array
    {
        return Connection::select(
            "SELECT c.*, a.email AS admin_email
             FROM customer_credits c
             LEFT JOIN admins a ON a.id = c.admin_id
             WHERE c.customer_id = ?
             ORDER BY c.id DESC
             LIMIT " . max(1, min(500, $limit)),
            [$customerId]
        );
    }
}
