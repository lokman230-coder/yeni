<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use Closure;

final class LocaleMiddleware
{
    private array $supported = ['tr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $default = (string) Config::get('app.locale', 'tr');
        $locale = $request->query('lang')
            ?? $request->cookie('locale')
            ?? SessionManager::get('locale')
            ?? $default;

        if (!in_array($locale, $this->supported, true)) {
            $locale = $default;
        }

        SessionManager::set('locale', $locale);

        // ?lang= geldi ise cookie'yi güncelle
        if ($request->query('lang')) {
            setcookie('locale', (string)$locale, time() + 86400 * 365, '/', '', false, true);
        }

        return $next($request);
    }
}
