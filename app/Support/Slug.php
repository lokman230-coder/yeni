<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database\Connection;

final class Slug
{
    /** UTF-8 metni slug'a çevir. */
    public static function make(string $text): string
    {
        $map = [
            'ı'=>'i','İ'=>'i','ş'=>'s','Ş'=>'s','ğ'=>'g','Ğ'=>'g',
            'ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
        $text = trim($text, '-');
        return $text !== '' ? $text : 'item';
    }

    /** Belirtilen tabloda benzersiz slug üret. */
    public static function unique(string $text, string $table, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = self::make($text);
        $slug = $base;
        $i = 1;
        while (self::exists($table, $column, $slug, $ignoreId)) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private static function exists(string $table, string $column, string $slug, ?int $ignoreId): bool
    {
        try {
            $sql = "SELECT id FROM `{$table}` WHERE `{$column}` = ?";
            $params = [$slug];
            if ($ignoreId) {
                $sql .= " AND id != ?";
                $params[] = $ignoreId;
            }
            $row = Connection::selectOne($sql, $params);
            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}
