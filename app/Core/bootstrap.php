<?php
/**
 * Ahost Bilişim - Bootstrap
 * Autoloader, error handler, env yükleme.
 */

declare(strict_types=1);

// Composer autoload varsa kullan
if (file_exists(AHO_ROOT . '/vendor/autoload.php')) {
    require AHO_ROOT . '/vendor/autoload.php';
} else {
    // Composer yoksa basit PSR-4 autoloader
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = AHO_ROOT . '/app/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });

    // helpers.php'yi manuel yükle
    require AHO_ROOT . '/app/Support/helpers.php';
}

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Charset
mb_internal_encoding('UTF-8');
