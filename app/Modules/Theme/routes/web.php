<?php

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Services\Theme\ThemeManager;

/** @var Router $router */

// Tema seçimi
$router->post('/tema/degistir', function (Request $r) {
    $slug = (string) $r->input('theme', 'default');
    $ok = ThemeManager::setActive($slug);
    if ($r->wantsJson()) {
        return Response::json(['success' => $ok, 'active' => ThemeManager::active()]);
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    return Response::redirect($referer);
})->middleware(['csrf'])->name('theme.change');

// AJAX tema önizleme (GET, kalıcı olmayan)
$router->get('/tema/onizle/{slug}', function (Request $r) {
    $slug = (string) $r->param('slug');
    if (!ThemeManager::exists($slug)) {
        return Response::json(['success' => false, 'error' => 'Tema bulunamadı.'], 404);
    }
    $manifest = ThemeManager::get($slug);
    return Response::json([
        'success' => true,
        'theme'   => $manifest,
        'stylesheets' => array_map(
            fn($f) => '/themes/' . $slug . '/css/' . ltrim($f, '/'),
            $manifest['stylesheets'] ?? []
        ),
    ]);
});
