<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Setup\Services\InstallGate;
use Closure;

/**
 * Kurulum sihirbazı gate.
 *
 * installed.lock yoksa tüm istekleri /kurulum'a yönlendirir.
 * (Assets ve /kurulum path'i istisna.)
 */
final class SetupMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallGate::isInstalled()) {
            return $next($request);
        }
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        // İzin verilenler
        if (str_starts_with($path, '/kurulum')
            || str_starts_with($path, '/assets')
            || str_starts_with($path, '/themes')
            || preg_match('#\.(css|js|png|jpg|jpeg|svg|ico|woff2?)$#', $path)) {
            return $next($request);
        }
        return Response::redirect('/kurulum');
    }
}
