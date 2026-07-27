<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;

/**
 * Auth token yönetimi — password_reset, email_verify, magic_link.
 * Tek noktadan üret / doğrula / tüket.
 */
final class AuthTokenService
{
    public const PURPOSE_PASSWORD_RESET = 'password_reset';
    public const PURPOSE_EMAIL_VERIFY   = 'email_verify';
    public const PURPOSE_MAGIC_LINK     = 'magic_link';

    /**
     * Yeni token oluştur.
     * @param string $userType 'customer'|'admin'
     */
    public static function issue(string $userType, int $userId, string $purpose, ?string $email = null, int $ttlMinutes = 60): string
    {
        // Aynı user+purpose için var olan aktif token'ları geçersiz kıl
        Connection::query(
            "UPDATE auth_tokens SET used_at = NOW() WHERE user_type = ? AND user_id = ? AND purpose = ? AND used_at IS NULL",
            [$userType, $userId, $purpose]
        );

        $token = bin2hex(random_bytes(32)); // 64 char
        Connection::insert('auth_tokens', [
            'user_type'  => $userType,
            'user_id'    => $userId,
            'purpose'    => $purpose,
            'token'      => $token,
            'email'      => $email,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return $token;
    }

    /** @return array|null Geçerli ve kullanılmamış token satırı */
    public static function verify(string $token, string $purpose): ?array
    {
        if ($token === '' || mb_strlen($token) < 32) return null;
        $row = Connection::selectOne(
            "SELECT * FROM auth_tokens WHERE token = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
            [$token, $purpose]
        );
        return $row ?: null;
    }

    public static function consume(int $tokenId): void
    {
        Connection::update('auth_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = ?', [$tokenId]);
    }

    /** Süresi geçmişleri temizle (cron için). */
    public static function cleanup(): int
    {
        try {
            $stmt = Connection::query("DELETE FROM auth_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            return $stmt->rowCount();
        } catch (\Throwable) { return 0; }
    }
}
