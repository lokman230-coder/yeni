<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;
use App\Core\Env;
use RuntimeException;

final class Encrypter
{
    private const CIPHER = 'aes-256-gcm';

    public static function key(): string
    {
        $key = (string) Env::get('APP_KEY', Config::get('app.key', ''));
        if ($key === '') {
            $key = self::generateKey();
            if (!self::persistGeneratedKey($key)) {
                throw new RuntimeException('APP_KEY olusturuldu ama config/installation.php dosyasina yazilamadi.');
            }
            Env::set('APP_KEY', $key);
            Config::set('app.key', $key);
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || strlen($decoded) !== 32) {
                throw new RuntimeException('APP_KEY gecersiz.');
            }
            return $decoded;
        }

        if (strlen($key) === 32) {
            return $key;
        }

        if (strlen($key) === 64 && ctype_xdigit($key)) {
            $decoded = hex2bin($key);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        throw new RuntimeException('APP_KEY 32 byte olmali (base64 veya hex).');
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public static function encrypt(string $plainText): string
    {
        $key = self::key();
        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($cipherText === false) {
            throw new RuntimeException('Sifreleme basarisiz.');
        }

        return base64_encode(json_encode([
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct'  => base64_encode($cipherText),
        ], JSON_UNESCAPED_SLASHES));
    }

    public static function decrypt(string $payload): string
    {
        $key = self::key();
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Gecersiz payload.');
        }

        $obj = json_decode($decoded, true);
        if (!is_array($obj) || !isset($obj['iv'], $obj['tag'], $obj['ct'])) {
            throw new RuntimeException('Gecersiz payload yapisi.');
        }

        $result = openssl_decrypt(
            (string) base64_decode((string) $obj['ct'], true),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            (string) base64_decode((string) $obj['iv'], true),
            (string) base64_decode((string) $obj['tag'], true)
        );

        if ($result === false) {
            throw new RuntimeException('Sifre cozme basarisiz.');
        }

        return $result;
    }

    public static function mask(string $value, int $visibleStart = 4, int $visibleEnd = 4): string
    {
        $len = strlen($value);
        if ($len <= $visibleStart + $visibleEnd + 3) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, $visibleStart) . '***' . substr($value, -$visibleEnd);
    }

    private static function persistGeneratedKey(string $key): bool
    {
        if (!defined('AHO_ROOT')) {
            return false;
        }

        $installationPath = AHO_ROOT . '/config/installation.php';
        if (is_file($installationPath) || is_dir(dirname($installationPath))) {
            $config = is_file($installationPath) ? (require $installationPath) : [];
            if (!is_array($config)) {
                $config = [];
            }

            if ((string)($config['APP_KEY'] ?? '') === '') {
                $config['APP_KEY'] = $key;
                $content = "<?php\n// Bu dosya install.php tarafindan olusturulur. Gercek sirlari GitHub'a yuklemeyin.\nreturn " . var_export($config, true) . ";\n";
                if (@file_put_contents($installationPath, $content, LOCK_EX) === false) {
                    return false;
                }
                @chmod($installationPath, 0640);
            }
            return true;
        }

        $envPath = AHO_ROOT . '/.env';
        if (is_file($envPath) && is_writable($envPath)) {
            $current = (string) file_get_contents($envPath);
            if (!preg_match('/^APP_KEY=/m', $current)) {
                return @file_put_contents($envPath, rtrim($current) . "\nAPP_KEY={$key}\n", LOCK_EX) !== false;
            }
            return true;
        }

        return false;
    }
}
