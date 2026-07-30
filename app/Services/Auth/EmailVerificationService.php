<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * E-posta doğrulama servisi.
 *
 * Akış:
 *   1. AuthService::registerCustomer → sendVerification()
 *   2. /email-dogrula?token=... → verify() → customers.email_verified_at set
 *   3. Kullanıcı panele giriş yapmaya devam edebilir; ama bazı özellikler
 *      (ör: para transferi, referral commission, admin bildirim) sadece
 *      verified kullanıcılara açık olabilir.
 */
final class EmailVerificationService
{
    public static function sendVerification(string $userType, int $userId, string $email): void
    {
        $token = AuthTokenService::issue($userType, $userId, AuthTokenService::PURPOSE_EMAIL_VERIFY, $email, 60 * 24 * 3);
        self::sendMail($userType, $userId, $email, $token);
    }

    /**
     * @return array{ok:bool, user?:array, message?:string}
     */
    public static function verify(string $token): array
    {
        $t = AuthTokenService::verify($token, AuthTokenService::PURPOSE_EMAIL_VERIFY);
        if (!$t) return ['ok' => false, 'message' => 'Bağlantı geçersiz veya süresi dolmuş.'];

        $table = $t['user_type'] === 'admin' ? 'admins' : 'customers';
        try {
            Connection::update($table, ['email_verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['user_id']]);
            AuthTokenService::consume((int) $t['id']);
            $user = Connection::selectOne("SELECT id, email, email_verified_at FROM $table WHERE id = ?", [$t['user_id']]);
            return ['ok' => true, 'user' => $user];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Kayıt güncellenemedi: ' . $e->getMessage()];
        }
    }

    public static function isVerified(string $userType, int $userId): bool
    {
        $table = $userType === 'admin' ? 'admins' : 'customers';
        $row = Connection::selectOne("SELECT email_verified_at FROM $table WHERE id = ?", [$userId]);
        return $row && $row['email_verified_at'] !== null;
    }

    public static function resend(string $userType, int $userId): bool
    {
        $table = $userType === 'admin' ? 'admins' : 'customers';
        $row = Connection::selectOne("SELECT email, email_verified_at FROM $table WHERE id = ?", [$userId]);
        if (!$row || $row['email_verified_at'] !== null) return false;
        self::sendVerification($userType, $userId, (string) $row['email']);
        return true;
    }

    private static function sendMail(string $userType, int $userId, string $email, string $token): void
    {
        if (!class_exists(\App\Services\Mail\Mailer::class)) return;
        try {
            $link = rtrim((string) env('APP_URL', ''), '/') . '/email-dogrula?token=' . $token;
            $appName = (string) env('APP_NAME', 'Ahost Bilişim');
            $bodyHtml = '<div style="font-family:system-ui,sans-serif;max-width:560px;margin:0 auto">
                <h2>E-posta adresinizi doğrulayın</h2>
                <p>' . htmlspecialchars($appName, ENT_HTML5) . ' hesabınıza hoş geldiniz. Kaydınızı tamamlamak için aşağıdaki bağlantıya tıklayın (3 gün geçerli).</p>
                <p><a href="' . $link . '" style="display:inline-block;padding:12px 24px;background:#059669;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">E-postamı Doğrula</a></p>
                <p style="color:#6b7280;font-size:13px">Buton çalışmıyorsa: <a href="' . $link . '">' . $link . '</a></p>
                <hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0">
                <p style="color:#9ca3af;font-size:12px">Bu talebi siz yapmadıysanız güvenle yok sayabilirsiniz.</p>
            </div>';
            \App\Services\Mail\Mailer::sendRaw($email, 'E-postanızı doğrulayın', $bodyHtml, null, "E-postanızı doğrulama linki (3 gün geçerli):\n$link", false);
        } catch (\Throwable $e) {
            Logger::warning('Email verify mail failed: ' . $e->getMessage());
        }
    }
}
