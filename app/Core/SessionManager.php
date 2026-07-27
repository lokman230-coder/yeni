<?php

declare(strict_types=1);

namespace App\Core;

final class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = (int) Config::get('session.lifetime', 7200);
        $secure   = (bool) Config::get('session.cookie_secure', false);
        $samesite = (string) Config::get('session.cookie_samesite', 'Lax');
        $name     = (string) Config::get('session.cookie_name', 'aho_session');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $samesite,
        ]);

        $savePath = AHO_ROOT . '/storage/sessions';
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }

        session_start();

        // CSRF token üret
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        // Flash mesajları yeni istek için hazırla
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash_current'] = $_SESSION['_flash'];
        $_SESSION['_flash'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_current'][$key] ?? $default;
    }

    public static function csrfToken(): string
    {
        return $_SESSION['_csrf'] ?? '';
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
        if (!isset($_SESSION)) $_SESSION = [];
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
