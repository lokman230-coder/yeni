<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Services\Sms\SmsManager;

/**
 * Tek kullanımlık kod (OTP) servisi — Rapor Madde 6.1
 * Kullanım alanları: SMS ile giriş, telefon doğrulama, şifre sıfırlama SMS
 */
final class OtpService
{
    private const CODE_LENGTH = 6;
    private const TTL_SECONDS = 300;    // 5 dakika
    private const MAX_ATTEMPTS = 5;
    private const RATE_LIMIT_SECONDS = 60; // Aynı numaraya 1 dk içinde ikinci kod gönderme

    /** Yeni kod üret + hash'le + kaydet + gönder */
    public static function issue(string $identity, string $purpose = 'login', string $channel = 'sms'): array
    {
        $identity = self::normalize($identity, $channel);
        if ($identity === '') {
            return ['ok' => false, 'error' => 'Geçersiz alıcı.'];
        }

        // Rate limit kontrolü — son 60 saniyede aynı hedefe kod gönderilmiş mi?
        $recent = Connection::selectOne(
            'SELECT id, created_at FROM otp_codes
             WHERE identity = ? AND purpose = ?
             ORDER BY id DESC LIMIT 1',
            [$identity, $purpose]
        );
        if ($recent && (time() - strtotime((string) $recent['created_at'])) < self::RATE_LIMIT_SECONDS) {
            $wait = self::RATE_LIMIT_SECONDS - (time() - strtotime((string) $recent['created_at']));
            return ['ok' => false, 'error' => "Yeni kod için $wait saniye bekle."];
        }

        $code = self::generateCode();
        $hash = password_hash($code, PASSWORD_DEFAULT);

        Connection::insert('otp_codes', [
            'channel'    => $channel,
            'purpose'    => $purpose,
            'identity'   => $identity,
            'code_hash'  => $hash,
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + self::TTL_SECONDS),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // Kanala göre gönder
        $sent = self::send($channel, $identity, $code, $purpose);
        if (!$sent['ok']) {
            return ['ok' => false, 'error' => $sent['error'] ?? 'Gönderim başarısız.'];
        }

        return [
            'ok'         => true,
            'expires_in' => self::TTL_SECONDS,
            // DEV modunda kodu döndür — production'da asla!
            'dev_code'   => (defined('APP_DEBUG') && APP_DEBUG === true) ? $code : null,
        ];
    }

    /** Kodu doğrula (başarılıysa kod kullanılmış işaretlenir) */
    public static function verify(string $identity, string $code, string $purpose = 'login'): bool
    {
        $identity = self::normalize($identity);
        $row = Connection::selectOne(
            'SELECT * FROM otp_codes
             WHERE identity = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [$identity, $purpose]
        );
        if (!$row) {
            return false;
        }

        if ((int)$row['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (!password_verify($code, (string)$row['code_hash'])) {
            Connection::pdo()
                ->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?')
                ->execute([$row['id']]);
            return false;
        }

        Connection::pdo()
            ->prepare('UPDATE otp_codes SET used_at = NOW() WHERE id = ?')
            ->execute([$row['id']]);

        return true;
    }

    public static function purgeExpired(): int
    {
        $st = Connection::pdo()->prepare('DELETE FROM otp_codes WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        $st->execute();
        return $st->rowCount();
    }

    private static function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;
        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private static function normalize(string $identity, string $channel = 'sms'): string
    {
        $identity = trim($identity);
        if ($channel === 'sms') {
            // Sadece rakamları al, TR ön eki normalize et
            $digits = preg_replace('/\D/', '', $identity) ?? '';
            if (strlen($digits) === 10) {
                $digits = '90' . $digits;
            } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = '9' . $digits;
            }
            return $digits;
        }
        if ($channel === 'email') {
            return strtolower($identity);
        }
        return $identity;
    }

    private static function send(string $channel, string $identity, string $code, string $purpose): array
    {
        if ($channel === 'sms') {
            $msg = "Ahost dogrulama kodunuz: $code (5 dk gecerli)";
            return SmsManager::send($identity, $msg);
        }
        if ($channel === 'email') {
            // Basit — Mailer kullan
            try {
                $subject = 'Ahost Doğrulama Kodu';
                $body    = "Doğrulama kodunuz: <strong style='font-size:24px'>$code</strong><br>5 dakika geçerlidir.";
                \App\Services\Mail\Mailer::send($identity, $subject, $body);
                return ['ok' => true];
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }
        return ['ok' => false, 'error' => 'Desteklenmeyen kanal: ' . $channel];
    }
}
