<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use Closure;

final class CsrfMiddleware
{
    private array $stateMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), $this->stateMethods, true)) {
            return $next($request);
        }

        $token = $request->input('_csrf') ?? $request->header('X-CSRF-TOKEN') ?? '';
        $sessionToken = SessionManager::csrfToken();

        if (!is_string($token) || !hash_equals($sessionToken, $token)) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'CSRF token uyuşmuyor.'], 419);
            }
            return Response::html('<h1>419 - Oturum süresi doldu veya CSRF token geçersiz.</h1>', 419);
        }

        return $next($request);
    }
}
