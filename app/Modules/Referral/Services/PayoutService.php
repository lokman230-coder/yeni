<?php

declare(strict_types=1);

namespace App\Modules\Referral\Services;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * Referral bakiyesi çekim (payout) yönetimi.
 *
 * Akış:
 *   1. Müşteri istek oluşturur → request()
 *      - bakiye >= min_payout kontrol
 *      - IBAN + account_holder + bank_name zorunlu
 *      - Bakiye HEMEN düşülür (rezerv), status=pending
 *   2. Admin onaylar → approve() → status=approved (havaleyi kendisi yapar)
 *   3. Admin işaretler → markPaid() → status=paid, paid_at
 *   4. Admin reddeder → reject() → bakiye iade + status=rejected
 *   5. Müşteri iptal eder → cancel() → bakiye iade
 */
final class PayoutService
{
    /**
     * @param array $data ['amount','iban','account_holder','bank_name','note'?]
     * @return array{ok:bool, id?:int, error?:string}
     */
    public static function request(int $customerId, array $data): array
    {
        $amount = round((float) str_replace(',', '.', (string) ($data['amount'] ?? 0)), 2);
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($data['iban'] ?? '')));
        $holder = trim((string) ($data['account_holder'] ?? ''));
        $bank = trim((string) ($data['bank_name'] ?? ''));

        if ($amount <= 0) return ['ok' => false, 'error' => 'Geçerli bir tutar girin.'];
        if (!preg_match('/^TR\d{24}$/', $iban)) return ['ok' => false, 'error' => 'Geçerli TR IBAN girin (TR + 24 rakam).'];
        if ($holder === '') return ['ok' => false, 'error' => 'Hesap sahibi zorunlu.'];
        if ($bank === '')   return ['ok' => false, 'error' => 'Banka adı zorunlu.'];

        $settings = ReferralService::settings();
        $minPayout = (float) ($settings['min_payout'] ?? 100);
        if ($amount < $minPayout) {
            return ['ok' => false, 'error' => "Minimum çekim tutarı: " . number_format($minPayout, 2, ',', '.') . " ₺"];
        }

        $balance = (float) (Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$customerId])['balance'] ?? 0);
        if ($amount > $balance) {
            return ['ok' => false, 'error' => "Yetersiz bakiye. Mevcut: " . number_format($balance, 2, ',', '.') . " ₺"];
        }

        try {
            Connection::beginTransaction();
            $id = Connection::insert('payout_requests', [
                'customer_id'    => $customerId,
                'amount'         => $amount,
                'currency'       => 'TRY',
                'method'         => 'bank_transfer',
                'iban'           => $iban,
                'account_holder' => $holder,
                'bank_name'      => $bank,
                'note'           => (string) ($data['note'] ?? '') ?: null,
                'status'         => 'pending',
            ]);
            // Bakiyeyi rezerve et (hemen düş)
            Connection::query("UPDATE customers SET balance = balance - ? WHERE id = ?", [$amount, $customerId]);
            Connection::commit();
            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            Connection::rollback();
            Logger::error('Payout request failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Sistem hatası: ' . $e->getMessage()];
        }
    }

    public static function approve(int $payoutId, int $adminId): bool
    {
        $p = Connection::selectOne("SELECT * FROM payout_requests WHERE id = ?", [$payoutId]);
        if (!$p || $p['status'] !== 'pending') return false;
        Connection::update('payout_requests', [
            'status'      => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'processed_by_admin_id' => $adminId,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$payoutId]);
        return true;
    }

    public static function markPaid(int $payoutId, int $adminId, string $note = ''): bool
    {
        $p = Connection::selectOne("SELECT * FROM payout_requests WHERE id = ?", [$payoutId]);
        if (!$p || !in_array($p['status'], ['pending', 'approved'], true)) return false;
        Connection::update('payout_requests', [
            'status'      => 'paid',
            'paid_at'     => date('Y-m-d H:i:s'),
            'approved_at' => $p['approved_at'] ?? date('Y-m-d H:i:s'),
            'processed_by_admin_id' => $adminId,
            'admin_note'  => $note ?: $p['admin_note'],
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$payoutId]);
        return true;
    }

    public static function reject(int $payoutId, int $adminId, string $note): bool
    {
        $p = Connection::selectOne("SELECT * FROM payout_requests WHERE id = ?", [$payoutId]);
        if (!$p || !in_array($p['status'], ['pending', 'approved'], true)) return false;
        try {
            Connection::beginTransaction();
            // Bakiyeyi iade et
            Connection::query("UPDATE customers SET balance = balance + ? WHERE id = ?", [$p['amount'], $p['customer_id']]);
            Connection::update('payout_requests', [
                'status'      => 'rejected',
                'rejected_at' => date('Y-m-d H:i:s'),
                'processed_by_admin_id' => $adminId,
                'admin_note'  => $note,
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'id = ?', [$payoutId]);
            Connection::commit();
            return true;
        } catch (\Throwable $e) {
            Connection::rollback();
            Logger::error('Payout reject failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function cancel(int $payoutId, int $customerId): bool
    {
        $p = Connection::selectOne("SELECT * FROM payout_requests WHERE id = ? AND customer_id = ?", [$payoutId, $customerId]);
        if (!$p || $p['status'] !== 'pending') return false;
        try {
            Connection::beginTransaction();
            Connection::query("UPDATE customers SET balance = balance + ? WHERE id = ?", [$p['amount'], $customerId]);
            Connection::update('payout_requests', [
                'status'     => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$payoutId]);
            Connection::commit();
            return true;
        } catch (\Throwable) {
            Connection::rollback();
            return false;
        }
    }

    /** @return array<int, array<string,mixed>> */
    public static function forCustomer(int $customerId): array
    {
        return Connection::select("SELECT * FROM payout_requests WHERE customer_id = ? ORDER BY created_at DESC LIMIT 50", [$customerId]);
    }

    public static function pendingForAdmin(int $limit = 100): array
    {
        return Connection::select(
            "SELECT p.*, c.email AS customer_email,
                    CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')) AS customer_name
             FROM payout_requests p
             LEFT JOIN customers c ON c.id = p.customer_id
             WHERE p.status IN ('pending','approved')
             ORDER BY p.created_at DESC LIMIT ?", [$limit]
        );
    }
}
