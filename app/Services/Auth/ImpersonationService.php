<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Core\SessionManager;
use App\Services\Logger\ActivityLog;

/**
 * Admin > Müşteri adına panele giriş (Impersonate) — Rapor Madde 5.4
 *
 * Akış:
 *   1) Admin, müşteri detayında "Adına Giriş" butonuna basar → start()
 *   2) Session'a impersonation state kurulur, admin_id backup edilir
 *   3) Müşteri paneli açılır, tüm sayfalarda "Admin olarak giriş yaptın" bandı görünür
 *   4) "Çık" → stop() → admin session'ına geri dön
 */
final class ImpersonationService
{
    private const SESSION_KEY = 'impersonation';
    private const TOKEN_TTL_MINUTES = 60;

    /** Admin, bir müşterinin adına girer. Başarılıysa token döner. */
    public static function start(int $adminId, int $customerId, ?string $reason = null): array
    {
        // Müşteri gerçekten var mı?
        $customer = Connection::selectOne(
            "SELECT id, email, first_name, last_name FROM customers WHERE id = ? AND status IN ('active','pending') LIMIT 1",
            [$customerId]
        );
        if ($customer === null) {
            return ['ok' => false, 'error' => 'Müşteri bulunamadı veya kapalı.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_MINUTES * 60);

        Connection::insert('impersonation_tokens', [
            'admin_id'    => $adminId,
            'customer_id' => $customerId,
            'token'       => $token,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
            'expires_at'  => $expiresAt,
        ]);

        // Session'a state kur — admin_id backup, customer_id olarak set
        SessionManager::set(self::SESSION_KEY, [
            'admin_id'      => $adminId,
            'customer_id'   => $customerId,
            'token'         => $token,
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'started_at'    => time(),
        ]);
        SessionManager::set('customer_id', $customerId);
        SessionManager::set('customer_email', $customer['email']);
        SessionManager::set('customer_name', trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')));

        ActivityLog::log('impersonation.start', 'customer', $customerId,
            "Admin #$adminId '{$customer['email']}' hesabına giriş yaptı",
            ['admin_id' => $adminId, 'reason' => $reason]
        );

        return ['ok' => true, 'token' => $token, 'customer' => $customer];
    }

    /** Impersonation'ı sonlandır, admin oturumuna geri dön. */
    public static function stop(): bool
    {
        $state = SessionManager::get(self::SESSION_KEY);
        if (!is_array($state) || empty($state['token'])) {
            return false;
        }

        // Token'ı iptal et
        Connection::pdo()
            ->prepare('UPDATE impersonation_tokens SET revoked_at = NOW() WHERE token = ?')
            ->execute([$state['token']]);

        ActivityLog::log('impersonation.stop', 'customer', (int)($state['customer_id'] ?? 0),
            'Impersonation sonlandırıldı',
            [
                'admin_id' => (int)($state['admin_id'] ?? 0),
                'duration' => time() - (int)($state['started_at'] ?? time()),
            ]
        );

        // Customer session'ını temizle
        SessionManager::forget('customer_id');
        SessionManager::forget('customer_email');
        SessionManager::forget('customer_name');
        SessionManager::forget(self::SESSION_KEY);

        return true;
    }

    /** Aktif bir impersonation var mı? */
    public static function isActive(): bool
    {
        $state = SessionManager::get(self::SESSION_KEY);
        return is_array($state) && !empty($state['token']);
    }

    public static function currentState(): ?array
    {
        $state = SessionManager::get(self::SESSION_KEY);
        return is_array($state) ? $state : null;
    }
}
