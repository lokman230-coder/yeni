<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Support\Encrypter;

final class AdminRegistrarController
{
    public function index(Request $request): Response
    {
        $this->ensureTables();
        $registrars = Connection::select(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM domains d WHERE d.registrar_id = r.id) AS domain_count
             FROM domain_registrars r
             ORDER BY r.is_default DESC, r.sort_order ASC, r.name ASC"
        );
        $configs = [];
        foreach (Connection::select("SELECT registrar_id, config_key, config_value, is_encrypted FROM registrar_configs ORDER BY config_key ASC") as $row) {
            $value = (string)($row['config_value'] ?? '');
            if ((int)($row['is_encrypted'] ?? 0) === 1 && $value !== '') {
                $value = '********';
            }
            $configs[(int)$row['registrar_id']][] = [
                'key' => (string)$row['config_key'],
                'value' => $value,
                'encrypted' => (int)($row['is_encrypted'] ?? 0) === 1,
            ];
        }

        return Response::html((new View())->render('admin::registrars.index', [
            'title' => 'Registrarlar',
            'registrars' => $registrars,
            'configs' => $configs,
            'success' => flash('success'),
            'error' => flash('error'),
        ]));
    }

    public function store(Request $request): Response
    {
        $this->ensureTables();
        $name = trim((string)$request->input('name', ''));
        $driver = (string)$request->input('driver', 'manual');
        if ($name === '') {
            SessionManager::flash('error', 'Registrar adı zorunlu.');
            return Response::redirect('/admin/registrarlar');
        }
        $slug = $this->slug($request->input('slug') ?: $name);
        $driverClass = $this->driverClass($driver);

        try {
            $id = Connection::insert('domain_registrars', [
                'name' => $name,
                'slug' => $slug,
                'driver_class' => $driverClass,
                'is_active' => $request->input('is_active') ? 1 : 0,
                'is_default' => $request->input('is_default') ? 1 : 0,
                'test_mode' => $request->input('test_mode') ? 1 : 0,
                'sort_order' => (int)$request->input('sort_order', 0),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($request->input('is_default')) {
                Connection::query("UPDATE domain_registrars SET is_default = 0 WHERE id != ?", [$id]);
            }
            $this->saveConfigs($id, (array)$request->input('config_key', []), (array)$request->input('config_value', []), (array)$request->input('config_encrypted', []));
            SessionManager::flash('success', 'Registrar eklendi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Registrar eklenemedi: ' . $e->getMessage());
        }
        return Response::redirect('/admin/registrarlar');
    }

    public function update(Request $request): Response
    {
        $this->ensureTables();
        $id = (int)$request->param('id');
        $registrar = Connection::selectOne("SELECT * FROM domain_registrars WHERE id = ?", [$id]);
        if (!$registrar) {
            return Response::notFound();
        }
        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            SessionManager::flash('error', 'Registrar adı zorunlu.');
            return Response::redirect('/admin/registrarlar');
        }
        try {
            Connection::update('domain_registrars', [
                'name' => $name,
                'slug' => $this->slug($request->input('slug') ?: $name),
                'driver_class' => $this->driverClass((string)$request->input('driver', 'manual')),
                'is_active' => $request->input('is_active') ? 1 : 0,
                'is_default' => $request->input('is_default') ? 1 : 0,
                'test_mode' => $request->input('test_mode') ? 1 : 0,
                'sort_order' => (int)$request->input('sort_order', 0),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            if ($request->input('is_default')) {
                Connection::query("UPDATE domain_registrars SET is_default = 0 WHERE id != ?", [$id]);
            }
            $this->saveConfigs($id, (array)$request->input('config_key', []), (array)$request->input('config_value', []), (array)$request->input('config_encrypted', []));
            SessionManager::flash('success', 'Registrar güncellendi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Registrar güncellenemedi: ' . $e->getMessage());
        }
        return Response::redirect('/admin/registrarlar');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->param('id');
        $usage = Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE registrar_id = ?", [$id]);
        if ((int)($usage['c'] ?? 0) > 0) {
            SessionManager::flash('error', 'Bu registrar bağlı domainlerde kullanılıyor.');
            return Response::redirect('/admin/registrarlar');
        }
        Connection::delete('registrar_configs', 'registrar_id = ?', [$id]);
        Connection::delete('domain_registrars', 'id = ?', [$id]);
        SessionManager::flash('success', 'Registrar silindi.');
        return Response::redirect('/admin/registrarlar');
    }

    private function saveConfigs(int $registrarId, array $keys, array $values, array $encrypted): void
    {
        $old = [];
        foreach (Connection::select("SELECT config_key, config_value, is_encrypted FROM registrar_configs WHERE registrar_id = ?", [$registrarId]) as $row) {
            $old[(string)$row['config_key']] = $row;
        }
        Connection::delete('registrar_configs', 'registrar_id = ?', [$registrarId]);
        foreach ($keys as $i => $key) {
            $key = trim((string)$key);
            $value = trim((string)($values[$i] ?? ''));
            if ($key === '') {
                continue;
            }
            if ($value === '********' && isset($old[$key])) {
                Connection::insert('registrar_configs', [
                    'registrar_id' => $registrarId,
                    'config_key' => $key,
                    'config_value' => $old[$key]['config_value'],
                    'is_encrypted' => (int)$old[$key]['is_encrypted'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                continue;
            }
            $isEncrypted = !empty($encrypted[$i]);
            Connection::insert('registrar_configs', [
                'registrar_id' => $registrarId,
                'config_key' => $key,
                'config_value' => $isEncrypted ? Encrypter::encrypt($value) : $value,
                'is_encrypted' => $isEncrypted ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function ensureTables(): void
    {
        Connection::pdo()->exec("CREATE TABLE IF NOT EXISTS `domain_registrars` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `driver_class` VARCHAR(191) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `test_mode` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `domain_registrars_slug_unique` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        Connection::pdo()->exec("CREATE TABLE IF NOT EXISTS `registrar_configs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `registrar_id` BIGINT UNSIGNED NOT NULL,
            `config_key` VARCHAR(120) NOT NULL,
            `config_value` TEXT NULL,
            `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `registrar_configs_registrar_key_unique` (`registrar_id`, `config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function driverClass(string $driver): string
    {
        return match ($driver) {
            'domainnameapi' => 'App\\Modules\\Registrar\\Drivers\\DomainNameApiDriver',
            default => 'App\\Modules\\Registrar\\Drivers\\ManualDriver',
        };
    }

    private function slug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9_-]+/u', '-', $value) ?: 'registrar';
        return trim($value, '-_') ?: 'registrar';
    }
}
