<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Basit schema builder — migration DSL için.
 */
final class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->toCreateSql();
        Connection::pdo()->exec($sql);
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);
        foreach ($blueprint->toAlterSqls() as $sql) {
            Connection::pdo()->exec($sql);
        }
    }

    public static function dropIfExists(string $table): void
    {
        Connection::pdo()->exec("DROP TABLE IF EXISTS `{$table}`");
    }

    public static function drop(string $table): void
    {
        Connection::pdo()->exec("DROP TABLE `{$table}`");
    }

    public static function hasTable(string $table): bool
    {
        $db = \App\Core\Config::get('database.connections.mysql.database');
        $row = Connection::selectOne(
            "SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
            [$db, $table]
        );
        return ($row['c'] ?? 0) > 0;
    }
}
