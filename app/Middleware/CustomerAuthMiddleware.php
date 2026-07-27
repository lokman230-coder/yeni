<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use Closure;

final class CustomerAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!AuthService::isCustomer()) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'Yetkisiz'], 401);
            }
            return Response::redirect('/giris');
        }
        return $next($request);
    }
}
