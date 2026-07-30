<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\Logger\Logger;
use Throwable;

final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        try {
            Logger::error($e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (Throwable) {
            // logger yoksa görmezden gel
        }

        // Harici hata takip (Sentry vs) — DSN yapılandırılmışsa
        try {
            \App\Services\Logger\ExternalReporter::report($e, [
                'tags' => ['app_env' => (string) env('APP_ENV', 'production')],
            ]);
        } catch (Throwable) {}

        if (self::$debug) {
            self::renderDebug($e);
        } else {
            self::renderProduction($e);
        }
    }

    public static function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::handleException(new \ErrorException(
                $err['message'], 0, $err['type'], $err['file'], $err['line']
            ));
        }
    }

    private static function renderDebug(Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        $class = htmlspecialchars(get_class($e), ENT_QUOTES);
        $msg   = htmlspecialchars($e->getMessage(), ENT_QUOTES);
        $file  = htmlspecialchars($e->getFile(), ENT_QUOTES);
        $line  = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES);

        echo <<<HTML
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Hata - Ahost Bilişim</title>
<style>
body{font-family:'Inter',system-ui,sans-serif;background:#0b1220;color:#e2e8f0;margin:0;padding:2rem;line-height:1.5}
.container{max-width:960px;margin:0 auto}
h1{color:#f87171;font-size:1.5rem;margin:0 0 .5rem}
.msg{background:#111a2e;padding:1.25rem;border-left:4px solid #f87171;border-radius:8px;margin-bottom:1rem}
.file{color:#94a3b8;font-family:'JetBrains Mono',monospace;font-size:.875rem;margin-bottom:1rem}
pre{background:#1a2540;padding:1rem;border-radius:8px;overflow:auto;font-size:.8125rem;color:#cbd5e1}
.badge{display:inline-block;background:#f87171;color:#0b1220;padding:.125rem .5rem;border-radius:4px;font-size:.75rem;font-weight:600;margin-right:.5rem}
</style></head><body><div class="container">
<span class="badge">EXCEPTION</span>
<h1>{$class}</h1>
<div class="msg">{$msg}</div>
<div class="file">📄 {$file}:{$line}</div>
<pre>{$trace}</pre>
</div></body></html>
HTML;
        exit;
    }

    private static function renderProduction(Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        $id = substr(bin2hex(random_bytes(4)), 0, 8);
        $ref = 'err_' . $id;
        error_log(sprintf(
            '[%s] Ahost fatal %s: %s in %s:%d',
            date('c'),
            $ref,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
        $tpl = AHO_ROOT . '/themes/default/partials/500.php';
        if (file_exists($tpl)) {
            include $tpl;
        } else {
            echo "<h1>Bir hata oluştu</h1><p>Referans: <code>err_{$id}</code></p>";
        }
        exit;
    }
}
