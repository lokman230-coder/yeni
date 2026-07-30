<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Env;
use App\Core\Router;
use App\Core\SessionManager;
use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        try {
            return Router::instance()->url($name, $params);
        } catch (\Throwable) {
            return '/';
        }
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('site_setting')) {
    function site_setting(string $key, mixed $default = ''): mixed
    {
        try { return \App\Services\Settings\SettingsManager::get($key, $default); } catch (\Throwable) { return $default; }
    }
}

if (!function_exists('site_menu')) {
    function site_menu(string $location = 'header'): array
    {
        static $menus = null;
        if ($menus === null) {
            $path = defined('AHO_ROOT') ? AHO_ROOT . '/storage/menu-config.json' : '';
            $menus = (is_file($path) ? json_decode((string) file_get_contents($path), true) : []) ?: [];
        }
        return array_values(array_filter($menus, static fn($item) => ($item['location'] ?? 'header') === $location && !empty($item['active'])));
    }
}

if (!function_exists('csrf')) {
    function csrf(): string
    {
        $token = SessionManager::csrfToken();
        return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return SessionManager::csrfToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        $flashOld = SessionManager::getFlash('_old', []);
        return (string) ($flashOld[$key] ?? $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $value = null): mixed
    {
        if ($value === null) {
            return SessionManager::getFlash($key);
        }
        SessionManager::flash($key, $value);
        return null;
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        static $cache = [];
        $locale ??= (string) (SessionManager::get('locale', config('app.locale', 'tr')));

        [$file, $item] = str_contains($key, '.') ? explode('.', $key, 2) : ['common', $key];

        if (!isset($cache[$locale][$file])) {
            // Ana lang dizini
            $path = AHO_ROOT . "/lang/{$locale}/{$file}.php";
            if (file_exists($path)) {
                $cache[$locale][$file] = require $path;
            } else {
                // Modül lang dizini
                $moduleName = ucfirst($file);
                $mp = AHO_ROOT . "/app/Modules/{$moduleName}/lang/{$locale}.php";
                $cache[$locale][$file] = file_exists($mp) ? require $mp : [];
            }
        }

        $items = $cache[$locale][$file] ?? [];
        $value = $items;
        foreach (explode('.', $item) as $seg) {
            if (is_array($value) && array_key_exists($seg, $value)) {
                $value = $value[$seg];
            } else {
                return $key; // bulunamadıysa key'i döndür
            }
        }

        if (is_string($value)) {
            foreach ($replace as $k => $v) {
                $value = str_replace(':' . $k, (string) $v, $value);
            }
        }

        return is_string($value) ? $value : $key;
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        static $view = null;
        $view ??= new View();
        return $view->render($template, $data);
    }
}

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $c = Container::getInstance();
        return $abstract === null ? $c : $c->get($abstract);
    }
}

if (!function_exists('current_url')) {
    function current_url(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
}

if (!function_exists('is_active_route')) {
    function is_active_route(string $prefix): bool
    {
        return str_starts_with(current_url(), $prefix);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 16): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#0b1220;color:#e2e8f0;padding:1rem;border-radius:8px;font-family:JetBrains Mono,monospace;">';
        foreach ($vars as $v) {
            var_dump($v);
        }
        echo '</pre>';
        exit;
    }
}
