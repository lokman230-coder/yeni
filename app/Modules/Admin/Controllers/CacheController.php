<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

final class CacheController
{
    public function index(Request $request): Response
    {
        $stats = self::stats();
        $view = new View();
        return Response::html($view->render('admin::cache.index', [
            'title' => 'Cache Center',
            'stats' => $stats,
        ]));
    }

    public function clear(Request $request): Response
    {
        $target = (string) $request->input('target', 'all');
        $result = self::clearTarget($target);
        SessionManager::flash('success', "{$result['deleted']} dosya silindi.");
        return Response::redirect('/admin/cache-center');
    }

    private static function stats(): array
    {
        $paths = [
            'cache'         => AHO_ROOT . '/storage/cache',
            'sessions'      => AHO_ROOT . '/storage/sessions',
            'rate-limits'   => AHO_ROOT . '/storage/cache/rate-limits',
            'logs'          => AHO_ROOT . '/storage/logs',
        ];
        $out = [];
        foreach ($paths as $key => $path) {
            if (!is_dir($path)) { $out[$key] = ['count' => 0, 'size' => 0]; continue; }
            $count = 0; $size = 0;
            foreach (glob($path . '/*') as $f) {
                if (is_file($f)) { $count++; $size += filesize($f) ?: 0; }
            }
            $out[$key] = ['count' => $count, 'size' => $size];
        }
        return $out;
    }

    private static function clearTarget(string $target): array
    {
        $paths = match ($target) {
            'cache'       => [AHO_ROOT . '/storage/cache'],
            'sessions'    => [AHO_ROOT . '/storage/sessions'],
            'rate-limits' => [AHO_ROOT . '/storage/cache/rate-limits'],
            'logs'        => [AHO_ROOT . '/storage/logs'],
            default       => [AHO_ROOT . '/storage/cache', AHO_ROOT . '/storage/cache/rate-limits'],
        };
        $deleted = 0;
        foreach ($paths as $path) {
            if (!is_dir($path)) continue;
            foreach (glob($path . '/*') as $f) {
                if (is_file($f) && basename($f) !== '.gitkeep') {
                    @unlink($f); $deleted++;
                }
            }
        }
        return ['deleted' => $deleted];
    }
}
