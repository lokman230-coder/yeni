<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Bakım modu — storage/maintenance.lock varken public site kapatır,
 * admin paneli açık kalır.
 *
 * Aç: php console maintenance:on "Kısa süre sonra dönüyoruz"
 * Kapat: php console maintenance:off
 */
final class MaintenanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = AHO_ROOT . '/storage/maintenance.lock';
        if (!is_file($lockFile)) return $next($request);

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        // Admin ve statik dosyalar bakım modunda da açık
        if (str_starts_with($path, '/admin')
            || str_starts_with($path, '/assets')
            || str_starts_with($path, '/themes')
            || preg_match('#\.(css|js|png|jpg|jpeg|svg|ico|woff2?)$#', $path)) {
            return $next($request);
        }

        // Admin oturumu varsa geçir (test amaçlı)
        if (\App\Services\Auth\AuthService::isAdmin()) {
            return $next($request);
        }

        $meta = json_decode((string) @file_get_contents($lockFile), true) ?: [];
        $message = (string) ($meta['message'] ?? 'Sistem bakımda. Kısa süre içinde döneceğiz.');
        $siteName = (string) env('APP_NAME', 'Ahost Bilişim');

        $html = '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">
            <title>' . htmlspecialchars($siteName, ENT_HTML5) . ' — Bakım</title>
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:system-ui,-apple-system,sans-serif;background:linear-gradient(135deg,#0ea5e9 0%,#8b5cf6 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#fff}
                .card{max-width:520px;text-align:center;padding:40px}
                h1{font-size:32px;margin:16px 0 12px}
                p{font-size:16px;opacity:.9;line-height:1.6}
                .icon{font-size:80px;margin-bottom:8px}
                .brand{margin-top:32px;opacity:.7;font-size:13px}
            </style></head><body>
            <div class="card">
                <div class="icon">🔧</div>
                <h1>Kısa Bir Ara</h1>
                <p>' . htmlspecialchars($message, ENT_HTML5) . '</p>
                <p style="margin-top:16px;font-size:14px;opacity:.8">Anlayışınız için teşekkür ederiz.</p>
                <div class="brand">' . htmlspecialchars($siteName, ENT_HTML5) . '</div>
            </div></body></html>';

        return Response::html($html, 503, ['Retry-After' => '600']);
    }
}
