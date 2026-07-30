<?php

declare(strict_types=1);

namespace App\Modules\Referral\Services;

use App\Core\Database\Connection;
use App\Core\SessionManager;
use App\Services\Logger\Logger;

/**
 * Referans (Affiliate) çekirdek servisi.
 *
 * Akış:
 *   1. Müşteri kayıt olduğunda / ilk girişte codeForCustomer() otomatik oluşur.
 *   2. Ziyaretçi ?ref=CODE ile geldiğinde captureVisit() → cookie kurulur.
 *   3. Ziyaretçi kayıt olursa attachOnSignup() → referrals tablosuna eklenir.
 *   4. Referrer'ın kayıt ettirdiği müşteri ödeme yaptığında onOrderPaid() →
 *      referral_commissions tablosuna komisyon kaydedilir.
 *   5. Admin komisyonu onaylar → müşteri bakiyesine eklenir.
 */
final class ReferralService
{
    private const COOKIE_NAME = 'aho_ref';

    // ---- Settings ---------------------------------------------------------

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        try {
            $row = Connection::selectOne("SELECT * FROM referral_settings WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            if ($row) return $row;
        } catch (\Throwable) {}
        return [
            'commission_percent' => 10.0,
            'cookie_days'        => 60,
            'min_payout'         => 100.0,
            'payout_method'      => 'balance',
            'first_order_only'   => 1,
            'is_active'          => 1,
        ];
    }

    public static function isProgramActive(): bool
    {
        try {
            $count = Connection::selectOne("SELECT COUNT(*) c FROM referral_settings WHERE is_active = 1");
            return ((int) ($count['c'] ?? 0)) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** İlk kurulumda default ayar satırı oluşturur. */
    public static function ensureDefaultSettings(): void
    {
        try {
            $has = Connection::selectOne("SELECT id FROM referral_settings LIMIT 1");
            if ($has) return;
            Connection::insert('referral_settings', [
                'name'               => 'Varsayılan Program',
                'commission_percent' => 10.000,
                'cookie_days'        => 60,
                'min_payout'         => 100.00,
                'payout_method'      => 'balance',
                'first_order_only'   => 1,
                'is_active'          => 1,
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Referral default settings insert failed: ' . $e->getMessage());
        }
    }

    // ---- Codes ------------------------------------------------------------

    /** Müşteri için mevcut kodu döndürür, yoksa oluşturur. */
    public static function codeForCustomer(int $customerId): string
    {
        try {
            $row = Connection::selectOne("SELECT code FROM referral_codes WHERE customer_id = ?", [$customerId]);
            if ($row) return (string) $row['code'];
        } catch (\Throwable) {
            return '';
        }
        $code = self::generateUniqueCode();
        try {
            Connection::insert('referral_codes', [
                'customer_id' => $customerId,
                'code'        => $code,
            ]);
        } catch (\Throwable $e) {
            // race → tekrar oku
            $row = Connection::selectOne("SELECT code FROM referral_codes WHERE customer_id = ?", [$customerId]);
            if ($row) return (string) $row['code'];
            throw $e;
        }
        return $code;
    }

    /** Kod → codes satırı */
    public static function codeRow(string $code): ?array
    {
        try {
            return Connection::selectOne("SELECT * FROM referral_codes WHERE code = ?", [$code]);
        } catch (\Throwable) { return null; }
    }

    private static function generateUniqueCode(int $len = 8): string
    {
        // Alfabe: karışıklığı azaltmak için 0/O/1/I çıkarıldı
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < $len; $j++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = Connection::selectOne("SELECT id FROM referral_codes WHERE code = ?", [$code]);
            if (!$exists) return $code;
        }
        throw new \RuntimeException('Referans kodu üretilemedi');
    }

    // ---- Visits & cookie --------------------------------------------------

    /** ?ref=CODE isteğinde çağrılır — ziyareti kaydeder ve cookie kurar. */
    public static function captureVisit(string $code, array $ctx = []): bool
    {
        $codeRow = self::codeRow($code);
        if (!$codeRow) return false;

        $settings = self::settings();
        $days = (int) ($settings['cookie_days'] ?? 60);

        try {
            Connection::insert('referral_visits', [
                'referral_code_id' => (int) $codeRow['id'],
                'code'             => $code,
                'ip'               => mb_substr((string) ($ctx['ip'] ?? ''), 0, 45),
                'user_agent'       => mb_substr((string) ($ctx['user_agent'] ?? ''), 0, 500),
                'landing_url'      => mb_substr((string) ($ctx['landing_url'] ?? ''), 0, 500),
                'referer_url'      => mb_substr((string) ($ctx['referer_url'] ?? ''), 0, 500),
            ]);
            Connection::query("UPDATE referral_codes SET total_visits = total_visits + 1 WHERE id = ?", [$codeRow['id']]);
        } catch (\Throwable $e) {
            Logger::warning('Referral visit insert failed: ' . $e->getMessage());
        }

        // Cookie kur (kullanıcı kaydolana kadar taşınır) — header gönderilmediyse
        $expires = time() + $days * 86400;
        if (!headers_sent()) {
            @setcookie(self::COOKIE_NAME, $code, [
                'expires'  => $expires,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        // Aynı istek içinde de kullanılabilir
        $_COOKIE[self::COOKIE_NAME] = $code;
        // Session'a da yaz (cookie'ler bazen bloklanır)
        try { SessionManager::set('ref_code', $code); } catch (\Throwable) {}
        return true;
    }

    /** Aktif cookie'deki kod (varsa). */
    public static function activeCode(): ?string
    {
        $code = $_COOKIE[self::COOKIE_NAME] ?? SessionManager::get('ref_code', null);
        return is_string($code) && $code !== '' ? $code : null;
    }

    // ---- Signup attach ----------------------------------------------------

    /**
     * Yeni müşteri kayıt olunca çağrılır — aktif referans kodu varsa
     * referrals tablosuna kayıt açılır.
     */
    public static function attachOnSignup(int $newCustomerId): ?int
    {
        $code = self::activeCode();
        if ($code === null) return null;
        $codeRow = self::codeRow($code);
        if (!$codeRow) return null;
        $referrerId = (int) $codeRow['customer_id'];
        if ($referrerId === $newCustomerId) return null; // kendi kendine referans

        try {
            // Aynı müşteri iki kere kaydedilmesin
            $has = Connection::selectOne("SELECT id FROM referrals WHERE referred_customer_id = ?", [$newCustomerId]);
            if ($has) return (int) $has['id'];

            $id = Connection::insert('referrals', [
                'referrer_customer_id' => $referrerId,
                'referred_customer_id' => $newCustomerId,
                'code_used'            => $code,
                'status'               => 'pending',
            ]);
            Connection::query("UPDATE referral_codes SET total_signups = total_signups + 1 WHERE id = ?", [$codeRow['id']]);

            // Cookie'yi temizle (tek kere kullanılır)
            if (!headers_sent()) {
                @setcookie(self::COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => '/']);
            }
            unset($_COOKIE[self::COOKIE_NAME]);
            try { SessionManager::remove('ref_code'); } catch (\Throwable) {}

            return $id;
        } catch (\Throwable $e) {
            Logger::warning('Referral attach failed: ' . $e->getMessage());
            return null;
        }
    }

    // ---- Commission on payment -------------------------------------------

    /**
     * Yönlendirilen müşteri ödeme yaptığında çağrılır.
     * @param array $order  ['id','customer_id','total','currency']
     * @return int|null Oluşan commission_id
     */
    public static function onOrderPaid(array $order): ?int
    {
        $customerId = (int) $order['customer_id'];
        try {
            $ref = Connection::selectOne(
                "SELECT * FROM referrals WHERE referred_customer_id = ? AND status IN ('pending','converted') LIMIT 1",
                [$customerId]
            );
        } catch (\Throwable) { $ref = null; }
        if (!$ref) return null;

        $settings = self::settings();

        // "İlk sipariş" kısıtı
        if ((int) ($settings['first_order_only'] ?? 1) === 1 && (string) $ref['status'] === 'converted') {
            return null;
        }

        $percent = (float) $settings['commission_percent'];
        $amount = round((float) $order['total'] * $percent / 100.0, 2);

        try {
            $commissionId = Connection::insert('referral_commissions', [
                'referral_id'          => (int) $ref['id'],
                'referrer_customer_id' => (int) $ref['referrer_customer_id'],
                'order_id'             => (int) $order['id'],
                'payment_id'           => null,
                'order_total'          => (float) $order['total'],
                'commission_percent'   => $percent,
                'commission_amount'    => $amount,
                'currency'             => (string) ($order['currency'] ?? 'TRY'),
                'status'               => 'pending',
                'note'                 => 'Otomatik oluştu — admin onayı bekliyor.',
            ]);
            Connection::update('referrals', [
                'status'       => 'converted',
                'converted_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$ref['id']]);
            Connection::query(
                "UPDATE referral_codes SET total_conversions = total_conversions + 1 WHERE customer_id = ?",
                [$ref['referrer_customer_id']]
            );
            return $commissionId;
        } catch (\Throwable $e) {
            Logger::warning('Referral commission insert failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Admin komisyonu onaylayınca çağrılır → referrer'ın bakiyesine ekle.
     */
    public static function approveCommission(int $commissionId): bool
    {
        try {
            $c = Connection::selectOne("SELECT * FROM referral_commissions WHERE id = ?", [$commissionId]);
            if (!$c || $c['status'] !== 'pending') return false;

            Connection::beginTransaction();
            Connection::update('referral_commissions', [
                'status'      => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$commissionId]);

            // Referrer'ın bakiyesine ekle
            Connection::query(
                "UPDATE customers SET balance = COALESCE(balance,0) + ? WHERE id = ?",
                [(float) $c['commission_amount'], (int) $c['referrer_customer_id']]
            );
            Connection::query(
                "UPDATE referral_codes SET total_earned = total_earned + ? WHERE customer_id = ?",
                [(float) $c['commission_amount'], (int) $c['referrer_customer_id']]
            );
            Connection::commit();
            return true;
        } catch (\Throwable $e) {
            Connection::rollback();
            Logger::error('Referral approve failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function rejectCommission(int $commissionId, string $note = ''): bool
    {
        try {
            Connection::update('referral_commissions', [
                'status' => 'rejected',
                'note'   => $note !== '' ? $note : 'Admin tarafından reddedildi',
            ], 'id = ?', [$commissionId]);
            return true;
        } catch (\Throwable) { return false; }
    }

    // ---- Read: customer dashboard ----------------------------------------

    /** @return array<string,mixed> */
    public static function statsFor(int $customerId): array
    {
        try {
            $code = Connection::selectOne("SELECT * FROM referral_codes WHERE customer_id = ?", [$customerId]);
            if (!$code) {
                self::codeForCustomer($customerId);
                $code = Connection::selectOne("SELECT * FROM referral_codes WHERE customer_id = ?", [$customerId]);
            }
            $pending = Connection::selectOne(
                "SELECT COALESCE(SUM(commission_amount),0) t FROM referral_commissions WHERE referrer_customer_id = ? AND status = 'pending'",
                [$customerId]
            );
            $approved = Connection::selectOne(
                "SELECT COALESCE(SUM(commission_amount),0) t FROM referral_commissions WHERE referrer_customer_id = ? AND status IN ('approved','paid')",
                [$customerId]
            );
            $recent = Connection::select(
                "SELECT rc.*, c.email AS referred_email
                 FROM referral_commissions rc
                 LEFT JOIN referrals r ON r.id = rc.referral_id
                 LEFT JOIN customers c ON c.id = r.referred_customer_id
                 WHERE rc.referrer_customer_id = ?
                 ORDER BY rc.created_at DESC LIMIT 20",
                [$customerId]
            );
            return [
                'code'             => $code,
                'pending_amount'   => (float) $pending['t'],
                'approved_amount'  => (float) $approved['t'],
                'recent_commissions'=> $recent,
            ];
        } catch (\Throwable $e) {
            Logger::warning('Referral stats failed: ' . $e->getMessage());
            return ['code' => null, 'pending_amount' => 0, 'approved_amount' => 0, 'recent_commissions' => []];
        }
    }

    /** Paylaşım URL'i */
    public static function shareUrl(string $code): string
    {
        return rtrim((string) env('APP_URL', ''), '/') . '/?ref=' . urlencode($code);
    }
}
