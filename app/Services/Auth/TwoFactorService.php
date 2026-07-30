<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Support\Encrypter;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * 2FA (TOTP) yönetimi — pragmarx/google2fa + bacon/bacon-qr-code.
 *
 * Akış (setup):
 *   1. generateSecret() → Base32 secret
 *   2. saveSecret($userType,$userId,$secret) → encrypted olarak kaydet (confirmed_at=null)
 *   3. Kullanıcı Google Authenticator ile QR okur → 6 haneli kod girer
 *   4. confirm($userType,$userId,$code) → kod doğruysa confirmed_at + 10 recovery code oluştur
 *
 * Login sırasında:
 *   - user'ın two_factor_enabled=1 ve confirmed_at != NULL ise
 *   - kod ekranı gösterilir → verify($userType,$userId,$code)
 *
 * Recovery code:
 *   - 10 tek kullanımlık kod (XXXX-XXXX formatında)
 *   - Kullanılınca dizinden çıkar
 */
final class TwoFactorService
{
    /** Yeni TOTP secret üret. */
    public static function generateSecret(): string
    {
        return (new Google2FA())->generateSecretKey(32);
    }

    /** QR code SVG data URL — otpauth://totp/... */
    public static function qrCodeSvg(string $issuer, string $email, string $secret): string
    {
        $g = new Google2FA();
        $uri = $g->getQRCodeUrl($issuer, $email, $secret);
        $renderer = new ImageRenderer(
            new RendererStyle(220, 2),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($uri);
    }

    /** Secret'i encrypted olarak kaydet, confirmed_at NULL. */
    public static function saveSecret(string $userType, int $userId, string $secret): void
    {
        $table = self::table($userType);
        Connection::update($table, [
            'two_factor_secret_encrypted' => Encrypter::encrypt($secret),
            'two_factor_confirmed_at'     => null,
        ], 'id = ?', [$userId]);
    }

    /**
     * Kullanıcı ilk 6 haneli kodu girer → doğruysa confirm et,
     * enabled=1 yap, 10 recovery code üret ve döndür (kullanıcıya bir kere gösterilir).
     *
     * @return string[]|null Recovery codes (10 adet) veya doğrulama başarısızsa null
     */
    public static function confirm(string $userType, int $userId, string $code): ?array
    {
        $secret = self::getSecret($userType, $userId);
        if (!$secret) return null;
        $g = new Google2FA();
        if (!$g->verifyKey($secret, $code, 1)) return null;

        $codes = self::generateRecoveryCodes();
        $table = self::table($userType);
        Connection::update($table, [
            'two_factor_enabled'        => 1,
            'two_factor_confirmed_at'   => date('Y-m-d H:i:s'),
            'two_factor_recovery_codes' => Encrypter::encrypt(json_encode($codes)),
        ], 'id = ?', [$userId]);
        return $codes;
    }

    /**
     * Login sırasında 6 haneli kod veya recovery code doğrulama.
     * Recovery code kullanıldıysa dizinden çıkarılır.
     */
    public static function verify(string $userType, int $userId, string $code): bool
    {
        $secret = self::getSecret($userType, $userId);
        if (!$secret) return false;

        // 6 haneli TOTP kodu mu?
        $trim = str_replace([' ', '-'], '', $code);
        if (preg_match('/^\d{6}$/', $trim)) {
            $g = new Google2FA();
            return $g->verifyKey($secret, $trim, 1);
        }

        // Recovery code (10 karakter, XXXX-XXXX)
        $codes = self::getRecoveryCodes($userType, $userId);
        $normalized = strtoupper(str_replace(' ', '', $code));
        foreach ($codes as $i => $c) {
            if (hash_equals(strtoupper($c), $normalized)) {
                unset($codes[$i]);
                $codes = array_values($codes);
                Connection::update(self::table($userType), [
                    'two_factor_recovery_codes' => Encrypter::encrypt(json_encode($codes)),
                ], 'id = ?', [$userId]);
                return true;
            }
        }
        return false;
    }

    public static function disable(string $userType, int $userId): void
    {
        Connection::update(self::table($userType), [
            'two_factor_enabled'          => 0,
            'two_factor_secret_encrypted' => null,
            'two_factor_confirmed_at'     => null,
            'two_factor_recovery_codes'   => null,
        ], 'id = ?', [$userId]);
    }

    public static function isEnabled(string $userType, int $userId): bool
    {
        $table = self::table($userType);
        $row = Connection::selectOne("SELECT two_factor_enabled, two_factor_confirmed_at FROM $table WHERE id = ?", [$userId]);
        return $row && (int) $row['two_factor_enabled'] === 1 && $row['two_factor_confirmed_at'] !== null;
    }

    public static function getSecret(string $userType, int $userId): ?string
    {
        $table = self::table($userType);
        $row = Connection::selectOne("SELECT two_factor_secret_encrypted FROM $table WHERE id = ?", [$userId]);
        if (!$row || empty($row['two_factor_secret_encrypted'])) return null;
        try { return Encrypter::decrypt($row['two_factor_secret_encrypted']); }
        catch (\Throwable) { return null; }
    }

    /** @return string[] */
    public static function getRecoveryCodes(string $userType, int $userId): array
    {
        $table = self::table($userType);
        $row = Connection::selectOne("SELECT two_factor_recovery_codes FROM $table WHERE id = ?", [$userId]);
        if (!$row || empty($row['two_factor_recovery_codes'])) return [];
        try {
            $decrypted = Encrypter::decrypt($row['two_factor_recovery_codes']);
            $arr = json_decode($decrypted, true);
            return is_array($arr) ? $arr : [];
        } catch (\Throwable) { return []; }
    }

    /** @return string[] */
    private static function generateRecoveryCodes(int $count = 10): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $a = ''; $b = '';
            for ($j = 0; $j < 4; $j++) $a .= $alphabet[random_int(0, strlen($alphabet)-1)];
            for ($j = 0; $j < 4; $j++) $b .= $alphabet[random_int(0, strlen($alphabet)-1)];
            $codes[] = "$a-$b";
        }
        return $codes;
    }

    private static function table(string $userType): string
    {
        return $userType === 'admin' ? 'admins' : 'customers';
    }
}
