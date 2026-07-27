<?php

declare(strict_types=1);

namespace App\Modules\Health\Services;

use App\Core\Database\Connection;
use App\Core\Env;

final class HealthCheck
{
    public static function all(): array
    {
        return [
            'php'         => self::php(),
            'database'    => self::database(),
            'storage'     => self::storage(),
            'extensions'  => self::extensions(),
            'app_config'  => self::appConfig(),
            'security'    => self::security(),
        ];
    }

    public static function php(): array
    {
        return [
            'label'  => 'PHP',
            'status' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'error',
            'items'  => [
                ['label' => 'Sürüm',    'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
                ['label' => 'SAPI',     'value' => PHP_SAPI, 'ok' => true],
                ['label' => 'Memory',   'value' => ini_get('memory_limit'), 'ok' => true],
                ['label' => 'Timezone', 'value' => date_default_timezone_get(), 'ok' => true],
            ],
        ];
    }

    public static function database(): array
    {
        $items = [];
        $ok = false;
        try {
            $ok = Connection::isConnected();
            if ($ok) {
                $version = Connection::selectOne("SELECT VERSION() v")['v'] ?? '?';
                $items[] = ['label' => 'Bağlantı', 'value' => 'OK', 'ok' => true];
                $items[] = ['label' => 'Sürüm', 'value' => $version, 'ok' => true];

                $tables = Connection::select("SHOW TABLES");
                $items[] = ['label' => 'Tablo sayısı', 'value' => (string) count($tables), 'ok' => count($tables) >= 20];

                $mig = Connection::selectOne("SELECT COUNT(*) c FROM migrations");
                $items[] = ['label' => 'Migration', 'value' => ($mig['c'] ?? 0) . ' çalıştırıldı', 'ok' => ($mig['c'] ?? 0) > 0];
            } else {
                $items[] = ['label' => 'Bağlantı', 'value' => 'BAŞARISIZ', 'ok' => false];
            }
        } catch (\Throwable $e) {
            $items[] = ['label' => 'Hata', 'value' => $e->getMessage(), 'ok' => false];
        }
        return ['label' => 'Veritabanı', 'status' => $ok ? 'ok' : 'error', 'items' => $items];
    }

    public static function storage(): array
    {
        $paths = [
            'storage/logs'      => AHO_ROOT . '/storage/logs',
            'storage/cache'     => AHO_ROOT . '/storage/cache',
            'storage/sessions'  => AHO_ROOT . '/storage/sessions',
            'storage/uploads'   => AHO_ROOT . '/storage/uploads',
        ];
        $items = [];
        $allOk = true;
        foreach ($paths as $label => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $ok = $exists && $writable;
            $items[] = [
                'label' => $label,
                'value' => $exists ? ($writable ? 'Yazılabilir' : 'Yazılamıyor') : 'Yok',
                'ok'    => $ok,
            ];
            if (!$ok) $allOk = false;
        }
        // Disk kullanımı
        $free = @disk_free_space(AHO_ROOT);
        $total = @disk_total_space(AHO_ROOT);
        if ($free && $total) {
            $pct = 100 - (int) round($free * 100 / $total);
            $items[] = ['label' => 'Disk kullanımı', 'value' => $pct . '% (' . round($free / 1024 / 1024 / 1024, 1) . ' GB boş)', 'ok' => $pct < 90];
            if ($pct >= 90) $allOk = false;
        }
        return ['label' => 'Depolama', 'status' => $allOk ? 'ok' : 'warning', 'items' => $items];
    }

    public static function extensions(): array
    {
        $required = ['pdo','pdo_mysql','mbstring','openssl','curl','json','fileinfo','gd','dom','xml'];
        $items = [];
        $allOk = true;
        foreach ($required as $ext) {
            $ok = extension_loaded($ext);
            $items[] = ['label' => $ext, 'value' => $ok ? 'Yüklü' : 'EKSİK', 'ok' => $ok];
            if (!$ok) $allOk = false;
        }
        return ['label' => 'PHP Uzantıları', 'status' => $allOk ? 'ok' : 'error', 'items' => $items];
    }

    public static function appConfig(): array
    {
        $items = [];
        $items[] = ['label' => 'APP_ENV',   'value' => (string) Env::get('APP_ENV', '?'), 'ok' => true];
        $items[] = ['label' => 'APP_DEBUG', 'value' => Env::get('APP_DEBUG') ? 'true' : 'false',
                    'ok'    => !Env::get('APP_DEBUG') || Env::get('APP_ENV') !== 'production'];
        $appKey = (string) Env::get('APP_KEY', '');
        $items[] = ['label' => 'APP_KEY',   'value' => $appKey !== '' ? '✓ Tanımlı' : '✗ Eksik', 'ok' => $appKey !== ''];
        return ['label' => 'Uygulama', 'status' => 'ok', 'items' => $items];
    }

    public static function security(): array
    {
        $items = [];
        $https = ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off';
        $items[] = ['label' => 'HTTPS', 'value' => $https ? 'Aktif' : 'Pasif',
                    'ok' => Env::get('APP_ENV') !== 'production' || $https];
        $items[] = ['label' => 'Session Secure', 'value' => Env::get('SESSION_SECURE') ? 'true' : 'false',
                    'ok' => Env::get('APP_ENV') !== 'production' || Env::get('SESSION_SECURE')];
        $items[] = ['label' => 'display_errors', 'value' => ini_get('display_errors'),
                    'ok' => Env::get('APP_ENV') !== 'production' || ini_get('display_errors') === '0'];
        return ['label' => 'Güvenlik', 'status' => 'ok', 'items' => $items];
    }
}
