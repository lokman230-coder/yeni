<?php

declare(strict_types=1);

namespace App\Services\Logger;

/**
 * PSR-3 uyumlu basit dosya logger.
 */
final class Logger
{
    private static string $logPath = '';

    public static function init(string $path): void
    {
        self::$logPath = rtrim($path, '/');
        if (!is_dir(self::$logPath)) {
            @mkdir(self::$logPath, 0755, true);
        }
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (self::$logPath === '') {
            self::$logPath = AHO_ROOT . '/storage/logs';
            if (!is_dir(self::$logPath)) {
                @mkdir(self::$logPath, 0755, true);
            }
        }

        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $file = self::$logPath . "/app-{$date}.log";

        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$time}] {$level}: {$message}{$ctx}" . PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function emergency(string $m, array $c = []): void { self::log('EMERGENCY', $m, $c); }
    public static function alert(string $m, array $c = []): void { self::log('ALERT', $m, $c); }
    public static function critical(string $m, array $c = []): void { self::log('CRITICAL', $m, $c); }
    public static function error(string $m, array $c = []): void { self::log('ERROR', $m, $c); }
    public static function warning(string $m, array $c = []): void { self::log('WARNING', $m, $c); }
    public static function notice(string $m, array $c = []): void { self::log('NOTICE', $m, $c); }
    public static function info(string $m, array $c = []): void { self::log('INFO', $m, $c); }
    public static function debug(string $m, array $c = []): void { self::log('DEBUG', $m, $c); }
}
