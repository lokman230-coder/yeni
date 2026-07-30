<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Basit dosya tabanlı rate limit (60 req/min per IP default).
 */
final class RateLimitMiddleware
{
    private int $max = 60;
    private int $window = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $key = md5($request->ip() . '|' . $request->path());
        $dir = AHO_ROOT . '/storage/cache/rate-limits';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . $key;

        $now = time();
        $data = ['count' => 0, 'reset' => $now + $this->window];

        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $data = $raw ? json_decode($raw, true) ?: $data : $data;
            if (($data['reset'] ?? 0) < $now) {
                $data = ['count' => 0, 'reset' => $now + $this->window];
            }
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > $this->max) {
            return Response::make('429 - Çok fazla istek', 429, [
                'Retry-After' => (string) max(1, $data['reset'] - $now),
            ]);
        }

        return $next($request);
    }
}
