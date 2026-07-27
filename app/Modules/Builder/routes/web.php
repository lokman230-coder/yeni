<?php

use App\Core\Router;
use App\Modules\Builder\Controllers\BuilderController;

/** @var Router $router */

// Public + demo giriş noktaları — Product modülündeki stub'ları override eder
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/site-builder',   [BuilderController::class, 'index'])->name('builder.site.index');
    $router->get('/mobile-builder', [BuilderController::class, 'index'])->name('builder.mobile.index');
    $router->post('/site-builder/olustur',   [BuilderController::class, 'create'])->middleware(['csrf']);
    $router->post('/mobile-builder/olustur', [BuilderController::class, 'create'])->middleware(['csrf']);
});

// Müşteri paneli içi editör
$router->group(['prefix' => 'panel', 'middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/builder/{id}',                [BuilderController::class, 'editor'])->name('builder.editor');
    $router->get('/builder/{id}/preview',        [BuilderController::class, 'preview']);
    $router->post('/builder/{id}/pages/{page}',  [BuilderController::class, 'saveTree'])->middleware(['csrf']);
    $router->post('/builder/{id}/settings',      [BuilderController::class, 'saveSettings'])->middleware(['csrf']);
    $router->get('/builder/{id}/export',         [BuilderController::class, 'export']);
    $router->post('/builder/upload',             [BuilderController::class, 'upload'])->middleware(['csrf']);

    // Export (Site ZIP + Mobile APK/AAB/Source)
    $router->get('/builder/{id}/export',              [\App\Modules\Builder\Controllers\ExportController::class, 'index'])->name('builder.export');
    $router->post('/builder/{id}/export/talep',       [\App\Modules\Builder\Controllers\ExportController::class, 'request'])->middleware(['csrf']);
    $router->get('/builder/export/{id}/indir',        [\App\Modules\Builder\Controllers\ExportController::class, 'download'])->name('builder.export.download');
});
