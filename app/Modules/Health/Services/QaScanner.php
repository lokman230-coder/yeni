<?php

declare(strict_types=1);

namespace App\Modules\Health\Services;

use App\Core\Router;

/**
 * Tüm route'ları listeler + basit HTTP scan yapabilir (opsiyonel).
 * Manuel çalıştırmada HTTP call'u opsiyonel; sadece route inventory dahi
 * dead link kontrolü için değerli.
 */
final class QaScanner
{
    public static function routes(): array
    {
        try {
            return Router::instance()->getRoutes();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Route'ları grup + method özetler.
     */
    public static function summary(): array
    {
        $routes = self::routes();
        $byMethod = []; $byGroup = [];
        foreach ($routes as $r) {
            $byMethod[$r['method']] = ($byMethod[$r['method']] ?? 0) + 1;
            $group = self::guessGroup($r['path']);
            $byGroup[$group] = ($byGroup[$group] ?? 0) + 1;
        }
        ksort($byGroup);
        return [
            'total_routes' => count($routes),
            'by_method'    => $byMethod,
            'by_group'     => $byGroup,
        ];
    }

    private static function guessGroup(string $path): string
    {
        if (str_starts_with($path, '/admin')) return 'admin';
        if (str_starts_with($path, '/panel')) return 'customer';
        if (str_starts_with($path, '/sepet')) return 'cart';
        if (str_starts_with($path, '/odeme')) return 'checkout';
        if (str_starts_with($path, '/urun')) return 'product';
        if (str_starts_with($path, '/domain')) return 'domain';
        if (str_starts_with($path, '/site-araclari')) return 'sitetools';
        if (str_starts_with($path, '/themes')) return 'theme-asset';
        if (str_starts_with($path, '/assets')) return 'asset';
        if (str_starts_with($path, '/blog')) return 'blog';
        return 'public';
    }
}
