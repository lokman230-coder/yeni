<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Services\Currency\CurrencyService;
use Closure;

final class CurrencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = (string) Config::get('app.currency', 'TRY');
        $supported = CurrencyService::supported();

        $currency = $request->query('cur');
        if ($currency === null || $currency === '') {
            $currency = $request->query('currency');
        }
        if ($currency === null || $currency === '') {
            $currency = SessionManager::get('currency');
        }
        if ($currency === null || $currency === '') {
            $currency = $request->cookie('currency');
        }
        if ($currency === null || $currency === '') {
            $currency = $default;
        }

        $currency = strtoupper((string) $currency);

        if (!in_array($currency, $supported, true)) {
            $currency = $default;
        }

        SessionManager::set('currency', $currency);

        if ($request->query('cur') || $request->query('currency')) {
            setcookie('currency', $currency, time() + 86400 * 365, '/', '', false, true);
            $_COOKIE['currency'] = $currency;
        }

        return $next($request);
    }
}
