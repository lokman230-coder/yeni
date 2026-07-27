<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;
use App\Core\Env;
use RuntimeException;

/**
 * AES-256-GCM encrypter.
 *
 * Kullanım:
 *   $cipher = Encrypter::encrypt('gizli-api-anahtari');   // -> "eyJpdiI6..."
 *   $plain  = Encrypter::decrypt($cipher);
 *
 * Anahtar: .env içindeki APP_KEY (32 byte random, base64).
 * APP_KEY yoksa yükleme sırasında otomatik üretilir ve .env'e yazılır.
 */
final class Encrypter
{
    private const CIPHER = 'aes-256-gcm';

    public static function key(): string
    {
        $key = (string) Env::get('APP_KEY', Config::get('app.key', ''));
        if ($key === '') {
            throw new RuntimeException(
                'APP_KEY tanımlı değil. `php console key:generate` çalıştırın.'
            );
        }
        // base64 ile başlar mı?
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || strlen($decoded) !== 32) {
                throw new RuntimeException('APP_KEY geçersiz.');
            }
            return $decoded;
        }
        // ham 32 byte
        if (strlen($key) === 32) return $key;
        // 64 karakter hex olabilir
        if (strlen($key) === 64 && ctype_xdigit($key)) return (string) hex2bin($key);
        throw new RuntimeException('APP_KEY 32 byte olmalı (base64: veya hex).');
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /** Düz metni şifrele. Sonuç: base64(json(iv, tag, ciphertext)). */
    public static function encrypt(string $plainText): string
    {
        $key = self::key();
        $iv = random_bytes(12); // GCM için 96-bit
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16
        );
        if ($cipherText === false) {
            throw new RuntimeException('Şifreleme başarısız.');
        }
        return base64_encode(json_encode([
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct'  => base64_encode($cipherText),
        ], JSON_UNESCAPED_SLASHES));
    }

    /** Şifreli metni çöz. */
    public static function decrypt(string $payload): string
    {
        $key = self::key();
        $decoded = base64_decode($payload, true);
        if ($decoded === false) throw new RuntimeException('Geçersiz payload.');
        $obj = json_decode($decoded, true);
        if (!is_array($obj) || !isset($obj['iv'], $obj['tag'], $obj['ct'])) {
            throw new RuntimeException('Geçersiz payload yapısı.');
        }
        $result = openssl_decrypt(
            base64_decode($obj['ct']),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($obj['iv']),
            base64_decode($obj['tag'])
        );
        if ($result === false) {
            throw new RuntimeException('Şifre çözme başarısız (bütünlük hatası).');
        }
        return $result;
    }

    /** Admin panelde gösterim: "sk_liv...***abcd" gibi maskele. */
    public static function mask(string $value, int $visibleStart = 4, int $visibleEnd = 4): string
    {
        $len = strlen($value);
        if ($len <= $visibleStart + $visibleEnd + 3) {
            return str_repeat('*', $len);
        }
        return substr($value, 0, $visibleStart) . '***' . substr($value, -$visibleEnd);
    }
}
