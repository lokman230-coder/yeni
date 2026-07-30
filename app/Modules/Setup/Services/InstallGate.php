<?php

declare(strict_types=1);

namespace App\Modules\Setup\Services;

/**
 * Kurulum durumu kontrolü.
 *
 * `storage/installed.lock` dosyası varsa kurulum tamamlanmış demektir;
 * `SetupMiddleware` bu dosya yoksa tüm istekleri /kurulum'a yönlendirir.
 */
final class InstallGate
{
    public static function lockFile(): string
    {
        return AHO_ROOT . '/storage/installed.lock';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockFile());
    }

    public static function markInstalled(array $meta = []): void
    {
        @mkdir(dirname(self::lockFile()), 0775, true);
        file_put_contents(self::lockFile(), json_encode(array_merge([
            'installed_at' => date('c'),
            'version'      => '1.0.0',
        ], $meta), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Güvenlik: install.php'yi otomatik sil (WordPress/PrestaShop tarzı)
        $installFile = AHO_ROOT . '/install.php';
        if (is_file($installFile)) {
            @unlink($installFile);
        }
    }

    public static function reset(): void
    {
        @unlink(self::lockFile());
    }
}
