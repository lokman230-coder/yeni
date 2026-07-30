<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Config;
use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO wrapper — connection pool + query helpers.
 */
final class Connection
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $conf = Config::get('database.connections.' . Config::get('database.default', 'mysql'), []);

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $conf['driver']   ?? 'mysql',
            $conf['host']     ?? '127.0.0.1',
            $conf['port']     ?? '3306',
            $conf['database'] ?? '',
            $conf['charset']  ?? 'utf8mb4'
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                $conf['username'] ?? 'root',
                $conf['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('DB bağlantı hatası: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @return array<int, array<string, mixed>> */
    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        // Positional (?) parametreler kullanılıyor → hem SET hem WHERE için uyumlu.
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );
        return self::query($sql, array_merge($params, array_values($whereParams)))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        return self::query($sql, $params)->rowCount();
    }

    public static function beginTransaction(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollback(): void { self::pdo()->rollBack(); }

    public static function isConnected(): bool
    {
        try {
            self::pdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
