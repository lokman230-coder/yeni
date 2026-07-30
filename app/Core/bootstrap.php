<?php

/**
 * Ahost One bootstrap.
 * Registers the project autoloader first, then tries Composer for optional
 * vendor packages. If a cPanel upload leaves vendor permissions broken, the
 * installer can still open and report/fix the environment.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = AHO_ROOT . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$helpers = AHO_ROOT . '/app/Support/helpers.php';
if (is_file($helpers)) {
    require_once $helpers;
}

function aho_vendor_is_usable(string $root): bool
{
    $autoload = $root . '/vendor/autoload.php';
    if (!is_file($autoload) || !is_readable($autoload)) {
        return false;
    }

    $safeRoot = $root . '/vendor/thecodingmachine/safe';
    if (is_dir($safeRoot)) {
        foreach ([
            $safeRoot . '/lib/special_cases.php',
            $safeRoot . '/generated/array.php',
            $safeRoot . '/generated/datetime.php',
        ] as $file) {
            if (!is_file($file) || !is_readable($file)) {
                return false;
            }
        }
    }

    return true;
}

$composerAutoload = AHO_ROOT . '/vendor/autoload.php';
if (aho_vendor_is_usable(AHO_ROOT)) {
    try {
        require_once $composerAutoload;
    } catch (\Throwable $e) {
        if (defined('AHO_ROOT') && is_dir(AHO_ROOT . '/storage/logs')) {
            @file_put_contents(
                AHO_ROOT . '/storage/logs/bootstrap.log',
                '[' . date('c') . '] Composer autoload skipped: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
} elseif (is_file($composerAutoload) && defined('AHO_ROOT') && is_dir(AHO_ROOT . '/storage/logs')) {
    @file_put_contents(
        AHO_ROOT . '/storage/logs/bootstrap.log',
        '[' . date('c') . '] Composer autoload skipped: vendor package files are missing or unreadable.' . PHP_EOL,
        FILE_APPEND
    );
}

date_default_timezone_set('Europe/Istanbul');

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
