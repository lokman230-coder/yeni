<?php
/**
 * Global route dosyası. Modül route'ları otomatik yüklenir; burada tema
 * ve modül asset serve rotaları bulunur.
 */

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;

/** @var Router $router */

// Tema skin CSS serve — /themes/{slug}/css/{file*}
$router->get('/themes/{slug}/css/{file*}', function (Request $req) {
    $slug = (string) $req->param('slug');
    $file = (string) $req->param('file');
    if (str_contains($slug, '..') || str_contains($file, '..') || str_contains($file, '\\')) {
        return Response::make('Forbidden', 403);
    }
    $path = AHO_ROOT . '/themes/' . $slug . '/css/' . $file;
    if (!file_exists($path) || !is_readable($path)) {
        return Response::notFound('Theme asset not found');
    }
    return Response::make(
        (string) file_get_contents($path),
        200,
        [
            'Content-Type'  => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]
    );
});

// Modül asset serve — /assets/modules/{module}/{file*}
$router->get('/assets/modules/{module}/{file*}', function (Request $req) {
    $module = ucfirst((string) $req->param('module'));
    $file = (string) $req->param('file');

    // Path traversal koruması
    if (str_contains($module, '..') || str_contains($file, '..') || str_contains($file, '\\')) {
        return Response::make('Forbidden', 403);
    }

    // module → CookieAnalytics, cookie, Cookie gibi olabilir — dizini bul
    $candidates = [
        AHO_ROOT . '/app/Modules/' . $module . '/assets/',
        AHO_ROOT . '/app/Modules/' . ucfirst(strtolower($module)) . '/assets/',
    ];

    // Slug ile modül eşleştir
    if ($info = \App\Core\ModuleLoader::get(strtolower($module))) {
        $candidates[] = $info['path'] . '/assets/';
    }

    // "cookie" → "CookieAnalytics" gibi özel eşleşmeler
    $slugMap = ['cookie' => 'CookieAnalytics'];
    if (isset($slugMap[strtolower($module)])) {
        $candidates[] = AHO_ROOT . '/app/Modules/' . $slugMap[strtolower($module)] . '/assets/';
    }

    // css/xxx.css veya js/xxx.js
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $subdir = match ($ext) {
        'css' => 'css/',
        'js'  => 'js/',
        default => '',
    };

    foreach ($candidates as $base) {
        $path = $base . $subdir . $file;
        if (file_exists($path) && is_readable($path)) {
            $mime = match ($ext) {
                'css' => 'text/css',
                'js'  => 'application/javascript',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'woff', 'woff2' => 'font/' . $ext,
                default => 'application/octet-stream',
            };
            return Response::make(
                (string) file_get_contents($path),
                200,
                [
                    'Content-Type'  => $mime . '; charset=UTF-8',
                    'Cache-Control' => 'public, max-age=86400',
                ]
            );
        }
    }

    return Response::notFound('Asset not found');
});

// Statik asset fallback — /assets/{path*}
// Sırayla: public/assets/ → themes/default/ → 404
$router->get('/assets/{path*}', function (Request $req) {
    $path = (string) $req->param('path');
    if (str_contains($path, '..')) {
        return Response::make('Forbidden', 403);
    }

    $candidates = [
        AHO_ROOT . '/public/assets/' . $path,
        AHO_ROOT . '/themes/default/' . $path,
    ];

    $file = null;
    foreach ($candidates as $c) {
        if (file_exists($c) && is_readable($c)) { $file = $c; break; }
    }

    if ($file === null) {
        return Response::notFound('Asset not found');
    }
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mime = match ($ext) {
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        default => 'application/octet-stream',
    };
    return Response::make(
        (string) file_get_contents($file),
        200,
        [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]
    );
});
