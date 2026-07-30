<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Services\Logger\Logger;

/**
 * Backup / Restore servisi.
 *
 * Yedek türleri:
 *   - db      → mysqldump ile DB dump (.sql.gz)
 *   - storage → storage/ dizini tarball (.tar.gz) — encrypted secrets, uploads
 *   - full    → ikisini birden zip'ler
 *
 * Yedekler: storage/backups/YYYY-MM-DD_HHMMSS/
 * 30 günden eski yedekler otomatik silinir (cron).
 */
final class BackupService
{
    public static function backupDir(): string
    {
        return AHO_ROOT . '/storage/backups';
    }

    /** @return array{ok:bool, file?:string, size?:int, error?:string} */
    public static function createDbBackup(): array
    {
        $dir = self::backupDir();
        @mkdir($dir, 0770, true);
        $ts = date('Y-m-d_His');
        $file = "$dir/db-$ts.sql.gz";

        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (int) env('DB_PORT', 3306);
        $db   = (string) env('DB_DATABASE', '');
        $user = (string) env('DB_USERNAME', '');
        $pass = (string) env('DB_PASSWORD', '');

        if ($db === '' || $user === '') {
            return ['ok' => false, 'error' => 'DB bilgileri eksik.'];
        }

        // mysqldump varsa onu kullan
        $mysqldump = trim((string) @shell_exec('command -v mysqldump'));
        if ($mysqldump !== '') {
            $cmd = sprintf(
                'MYSQL_PWD=%s %s -h%s -P%d -u%s --single-transaction --quick --lock-tables=false --routines --triggers --no-tablespaces %s 2>/dev/null | gzip > %s',
                escapeshellarg($pass),
                escapeshellarg($mysqldump),
                escapeshellarg($host),
                $port,
                escapeshellarg($user),
                escapeshellarg($db),
                escapeshellarg($file)
            );
            @exec($cmd, $out, $ret);
            if ($ret !== 0 || !is_file($file) || filesize($file) < 100) {
                @unlink($file);
                return ['ok' => false, 'error' => 'mysqldump başarısız. Log kontrol edin.'];
            }
        } else {
            // Fallback: PHP ile PDO üzerinden basit dump (küçük DB'ler için)
            $sql = self::phpDump();
            file_put_contents($file, gzencode($sql, 6));
        }

        Logger::info('DB backup created', ['file' => basename($file), 'size' => filesize($file)]);
        return ['ok' => true, 'file' => basename($file), 'size' => filesize($file)];
    }

    /** @return array{ok:bool, file?:string, size?:int, error?:string} */
    public static function createStorageBackup(): array
    {
        $dir = self::backupDir();
        @mkdir($dir, 0770, true);
        $ts = date('Y-m-d_His');
        $file = "$dir/storage-$ts.tar.gz";

        $tar = trim((string) @shell_exec('command -v tar'));
        if ($tar === '') {
            return ['ok' => false, 'error' => 'tar binary bulunamadı.'];
        }
        // storage/backups/ kendini yedekleme kısır döngüsünü önle
        $cmd = sprintf(
            'cd %s && %s czf %s --exclude=./storage/backups --exclude=./storage/logs --exclude=./storage/cache ./storage 2>&1',
            escapeshellarg(AHO_ROOT),
            escapeshellarg($tar),
            escapeshellarg($file)
        );
        @exec($cmd, $out, $ret);
        if ($ret !== 0 || !is_file($file)) {
            return ['ok' => false, 'error' => 'tar başarısız: ' . implode("\n", $out)];
        }

        Logger::info('Storage backup created', ['file' => basename($file), 'size' => filesize($file)]);
        return ['ok' => true, 'file' => basename($file), 'size' => filesize($file)];
    }

    /** @return array<int, array{name:string,size:int,mtime:int,type:string}> */
    public static function listBackups(): array
    {
        $dir = self::backupDir();
        if (!is_dir($dir)) return [];
        $files = glob("$dir/*") ?: [];
        $out = [];
        foreach ($files as $f) {
            if (!is_file($f)) continue;
            $name = basename($f);
            $type = str_starts_with($name, 'db-') ? 'db' : (str_starts_with($name, 'storage-') ? 'storage' : 'other');
            $out[] = [
                'name'  => $name,
                'size'  => filesize($f),
                'mtime' => filemtime($f),
                'type'  => $type,
            ];
        }
        usort($out, fn($a,$b) => $b['mtime'] - $a['mtime']);
        return $out;
    }

    public static function backupPath(string $name): ?string
    {
        // Path traversal koruması
        if (basename($name) !== $name || !preg_match('/^[a-zA-Z0-9._-]+$/', $name)) return null;
        $path = self::backupDir() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    public static function deleteBackup(string $name): bool
    {
        $path = self::backupPath($name);
        if (!$path) return false;
        return @unlink($path);
    }

    /** 30 günden eski yedekleri temizle (cron için) */
    public static function pruneOld(int $days = 30): int
    {
        $dir = self::backupDir();
        if (!is_dir($dir)) return 0;
        $cutoff = time() - $days * 86400;
        $count = 0;
        foreach ((glob("$dir/*") ?: []) as $f) {
            if (is_file($f) && filemtime($f) < $cutoff) {
                if (@unlink($f)) $count++;
            }
        }
        return $count;
    }

    /** Basit PHP fallback dump (mysqldump yoksa) */
    private static function phpDump(): string
    {
        $pdo = \App\Core\Database\Connection::pdo();
        $sql = "-- Ahost Bilişim PHP-fallback DB dump — " . date('c') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            $sql .= "\n-- Table: $t\n";
            $sql .= "DROP TABLE IF EXISTS `$t`;\n";
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(\PDO::FETCH_ASSOC);
            $sql .= ($create['Create Table'] ?? '') . ";\n";

            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $cols = array_keys($r);
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($r));
                $sql .= "INSERT INTO `$t` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
            }
        }
        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }
}
