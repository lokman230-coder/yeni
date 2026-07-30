<?php

use App\Core\Router;
use App\Modules\Builder\Controllers\BuilderController;
use App\Modules\Builder\Controllers\MobileBuildController;

/** @var Router $router */

$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/site-builder', [BuilderController::class, 'index'])->name('builder.site.index');
    $router->get('/mobile-builder', [BuilderController::class, 'index'])->name('builder.mobile.index');
    $router->post('/site-builder/olustur', [BuilderController::class, 'create'])->middleware(['csrf']);
    $router->post('/mobile-builder/olustur', [BuilderController::class, 'create'])->middleware(['csrf']);
});

$router->group(['prefix' => 'panel', 'middleware' => ['locale']], function (Router $router) {
    $router->get('/builder/{id}', [BuilderController::class, 'editor'])->name('builder.editor');
    $router->get('/builder/{id}/preview', [BuilderController::class, 'preview']);
    $router->post('/builder/{id}/pages/{page}', [BuilderController::class, 'saveTree'])->middleware(['csrf']);
    $router->post('/builder/{id}/settings', [BuilderController::class, 'saveSettings'])->middleware(['csrf']);
    $router->post('/builder/upload', [BuilderController::class, 'upload'])->middleware(['csrf']);

    $router->get('/builder/{id}/export', [\App\Modules\Builder\Controllers\ExportController::class, 'index'])->name('builder.export');
    $router->post('/builder/{id}/export/talep', [\App\Modules\Builder\Controllers\ExportController::class, 'request'])->middleware(['csrf']);
    $router->get('/builder/export/{id}/indir', [\App\Modules\Builder\Controllers\ExportController::class, 'download'])->name('builder.export.download');
});

$router->group(['prefix' => 'panel', 'middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/mobile-buildler', [MobileBuildController::class, 'index'])->name('mobile.builds');
    $router->get('/mobile-build/{id}/status', [MobileBuildController::class, 'status']);
});

$router->group(['middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/mobile-build/{id}/indir', [MobileBuildController::class, 'download'])->name('mobile.build.download');
});
