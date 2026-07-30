<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Basit .env okuyucu. Composer/vlucas/phpdotenv yerine minimal implementasyon.
 */
final class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !file_exists($path)) {
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Tırnak temizle
            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            // ${VAR} referanslarını çöz
            $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($m) {
                return self::$vars[$m[1]] ?? $_ENV[$m[1]] ?? '';
            }, $value);

            self::$vars[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$vars[$key] = $value;
        $_ENV[$key] = $value;
        putenv($key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$vars[$key] ?? $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        // Bool cast
        return match (strtolower((string)$value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}
