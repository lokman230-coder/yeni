<?php

declare(strict_types=1);

namespace App\Core\Database;

use RuntimeException;

/**
 * Migration çalıştırıcı — çekirdek + modül migration'larını toplar, sıralar, uygular.
 */
final class Migrator
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function ensureMigrationsTable(): void
    {
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $t) {
                $t->id();
                $t->string('migration', 191);
                $t->integer('batch');
                $t->dateTime('run_at')->default('CURRENT_TIMESTAMP', true);
                $t->unique('migration');
            });
        }
    }

    /** @return list<array{name:string, file:string, class:string}> */
    public function discover(): array
    {
        $files = [];

        // Çekirdek migration'lar
        foreach (glob($this->basePath . '/database/migrations/*.php') ?: [] as $f) {
            $files[] = $this->parseFile($f, 'core');
        }

        // Modül migration'ları
        foreach (glob($this->basePath . '/app/Modules/*/Migrations/*.php') ?: [] as $f) {
            $module = strtolower(basename(dirname(dirname($f))));
            $files[] = $this->parseFile($f, $module);
        }

        // İsim (batch prefix) ile sırala
        usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $files;
    }

    private function parseFile(string $file, string $namespace): array
    {
        $base = basename($file, '.php');
        // 0001_create_users_table -> CreateUsersTable
        $parts = explode('_', $base);
        array_shift($parts); // numarayı at
        $className = implode('', array_map('ucfirst', $parts));
        return [
            'name'  => $namespace . '_' . $base,
            'file'  => $file,
            'class' => $className,
        ];
    }

    public function run(): array
    {
        $this->ensureMigrationsTable();
        $ran = array_column(Connection::select("SELECT migration FROM migrations"), 'migration');
        $files = $this->discover();
        $batch = (int) (Connection::selectOne("SELECT MAX(batch) AS b FROM migrations")['b'] ?? 0) + 1;

        $executed = [];
        foreach ($files as $f) {
            if (in_array($f['name'], $ran, true)) {
                continue;
            }
            $migration = $this->loadMigration($f);
            $migration->up();
            Connection::insert('migrations', [
                'migration' => $f['name'],
                'batch'     => $batch,
            ]);
            $executed[] = $f['name'];
        }

        return $executed;
    }

    public function fresh(): array
    {
        // Tüm tabloları düşür
        $tables = Connection::select("SHOW TABLES");
        Connection::pdo()->exec("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables as $row) {
            $name = array_values($row)[0];
            Connection::pdo()->exec("DROP TABLE IF EXISTS `{$name}`");
        }
        Connection::pdo()->exec("SET FOREIGN_KEY_CHECKS = 1");

        return $this->run();
    }

    public function rollback(int $steps = 1): array
    {
        $this->ensureMigrationsTable();
        $rolled = [];

        for ($i = 0; $i < $steps; $i++) {
            $lastBatch = Connection::selectOne("SELECT MAX(batch) AS b FROM migrations")['b'] ?? null;
            if ($lastBatch === null) break;

            $rows = Connection::select("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC", [$lastBatch]);
            $files = $this->discover();
            $byName = array_column($files, null, 'name');

            foreach ($rows as $row) {
                $name = $row['migration'];
                if (isset($byName[$name])) {
                    $migration = $this->loadMigration($byName[$name]);
                    $migration->down();
                    Connection::delete('migrations', 'migration = ?', [$name]);
                    $rolled[] = $name;
                }
            }
        }

        return $rolled;
    }

    private function loadMigration(array $f): Migration
    {
        $obj = require $f['file'];
        if (!($obj instanceof Migration)) {
            throw new RuntimeException("Migration dosyası Migration instance döndürmeli: {$f['file']}");
        }
        return $obj;
    }
}
