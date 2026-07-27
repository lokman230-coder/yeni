<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        return $response;
    }
}
