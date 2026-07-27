<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * Şifremi unuttum akışı.
 *
 * Adım 1: PasswordResetService::request($email) → e-posta gönderir (varsa)
 *         Güvenlik: kullanıcı olsa da olmasa da AYNI response döndürülür (enumeration önleme)
 * Adım 2: URL: /sifre-sifirla?token=... → PasswordResetService::validate($token)
 * Adım 3: PasswordResetService::reset($token, $newPassword) → hash + AuthToken::consume
 */
final class PasswordResetService
{
    public static function request(string $email, string $userType = 'customer'): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => true]; // enumeration önlemek için ok döndür
        }
        $table = $userType === 'admin' ? 'admins' : 'customers';
        try {
            $user = Connection::selectOne("SELECT id, email, first_name FROM $table WHERE email = ? LIMIT 1", [$email]);
        } catch (\Throwable) { $user = null; }

        if ($user) {
            $token = AuthTokenService::issue($userType, (int) $user['id'], AuthTokenService::PURPOSE_PASSWORD_RESET, $email, 60);
            self::sendResetEmail($userType, $user, $token);
        }
        return ['ok' => true]; // her durumda ok — kullanıcı bilgi sızdırmayız
    }

    public static function validate(string $token): ?array
    {
        return AuthTokenService::verify($token, AuthTokenService::PURPOSE_PASSWORD_RESET);
    }

    /**
     * @return array{ok:bool, error?:string}
     */
    public static function reset(string $token, string $newPassword): array
    {
        $check = PasswordPolicy::validate($newPassword);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => implode(' ', $check['errors'])];
        }
        $t = AuthTokenService::verify($token, AuthTokenService::PURPOSE_PASSWORD_RESET);
        if (!$t) {
            return ['ok' => false, 'error' => 'Token geçersiz veya süresi dolmuş.'];
        }
        $table = $t['user_type'] === 'admin' ? 'admins' : 'customers';
        try {
            Connection::update($table, ['password_hash' => PasswordHasher::hash($newPassword)], 'id = ?', [$t['user_id']]);
            AuthTokenService::consume((int) $t['id']);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Şifre güncellenemedi: ' . $e->getMessage()];
        }
    }

    private static function sendResetEmail(string $userType, array $user, string $token): void
    {
        if (!class_exists(\App\Services\Mail\Mailer::class)) return;
        try {
            $link = rtrim((string) env('APP_URL', ''), '/') . '/sifre-sifirla?token=' . $token;
            $to = (string) $user['email'];
            $subject = 'Şifre Sıfırlama Talebi';
            $bodyHtml = '<div style="font-family:system-ui,sans-serif;max-width:560px;margin:0 auto">
                <h2>Merhaba ' . htmlspecialchars((string) ($user['first_name'] ?? ''), ENT_HTML5) . ',</h2>
                <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın. Bu bağlantı <strong>60 dakika</strong> geçerlidir.</p>
                <p><a href="' . $link . '" style="display:inline-block;padding:12px 24px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">Yeni Şifre Belirle</a></p>
                <p style="color:#6b7280;font-size:13px">Bu talebi siz yapmadıysanız güvenle yok sayabilirsiniz. Şifreniz değişmeyecektir.</p>
                <hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0">
                <p style="color:#9ca3af;font-size:12px">' . htmlspecialchars((string) env('APP_NAME', 'Ahost Bilişim'), ENT_HTML5) . '</p>
            </div>';
            $bodyText = "Şifre sıfırlama linki (60 dk geçerli):\n$link";

            \App\Services\Mail\Mailer::sendRaw($to, $subject, $bodyHtml, (string) ($user['first_name'] ?? '') ?: null, $bodyText, false);
        } catch (\Throwable $e) {
            Logger::warning('Password reset mail failed: ' . $e->getMessage());
        }
    }
}
