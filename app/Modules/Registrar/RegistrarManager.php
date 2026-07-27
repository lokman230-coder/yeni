<?php

declare(strict_types=1);

namespace App\Modules\Registrar;

use App\Core\Database\Connection;
use App\Modules\Registrar\Contracts\RegistrarInterface;
use App\Modules\Registrar\Drivers\ManualDriver;

/**
 * Aktif registrar sürücüsünü seçen manager.
 *
 * - Varsayılan registrar (is_default=1) veya belirtilmiş id
 * - Config değerleri (registrar_configs) driver'a geçilir
 */
final class RegistrarManager
{
    /** Aktif ve varsayılan registrar driver'ını döndürür. */
    public static function default(): RegistrarInterface
    {
        try {
            $reg = Connection::selectOne(
                "SELECT * FROM domain_registrars WHERE is_active = 1
                 ORDER BY is_default DESC, sort_order ASC, id ASC LIMIT 1"
            );
        } catch (\Throwable) {
            $reg = null;
        }

        if (!$reg) return new ManualDriver([]);

        $config = self::loadConfig((int) $reg['id']);
        $class = (string) $reg['driver_class'];
        if (!class_exists($class)) {
            return new ManualDriver($config);
        }

        return new $class($config, (bool) ($reg['test_mode'] ?? true));
    }

    public static function get(int $registrarId): RegistrarInterface
    {
        try {
            $reg = Connection::selectOne("SELECT * FROM domain_registrars WHERE id = ?", [$registrarId]);
        } catch (\Throwable) {
            $reg = null;
        }
        if (!$reg) return new ManualDriver([]);

        $config = self::loadConfig($registrarId);
        $class = (string) $reg['driver_class'];
        if (!class_exists($class)) return new ManualDriver($config);
        return new $class($config, (bool) ($reg['test_mode'] ?? true));
    }

    /** Registrar config değerlerini şifrelenmişse çözerek döndürür. */
    private static function loadConfig(int $registrarId): array
    {
        try {
            $rows = Connection::select(
                "SELECT config_key, config_value, is_encrypted FROM registrar_configs WHERE registrar_id = ?",
                [$registrarId]
            );
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $val = $row['config_value'];
            if ((int) $row['is_encrypted'] === 1 && $val !== null && $val !== '') {
                try {
                    $val = \App\Support\Encrypter::decrypt((string) $val);
                } catch (\Throwable) {
                    $val = null;
                }
            }
            $out[$row['config_key']] = $val;
        }
        return $out;
    }
}
