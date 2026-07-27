<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use Closure;

final class CurrencyMiddleware
{
    private array $supported = ['TRY', 'USD', 'EUR', 'GBP'];

    public function handle(Request $request, Closure $next): Response
    {
        $default = (string) Config::get('app.currency', 'TRY');
        $currency = strtoupper((string)(
            $request->query('cur')
            ?? $request->cookie('currency')
            ?? SessionManager::get('currency')
            ?? $default
        ));

        if (!in_array($currency, $this->supported, true)) {
            $currency = $default;
        }

        SessionManager::set('currency', $currency);

        if ($request->query('cur')) {
            setcookie('currency', $currency, time() + 86400 * 365, '/', '', false, true);
        }

        return $next($request);
    }
}
