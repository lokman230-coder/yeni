<?php

use App\Core\Router;
use App\Modules\Marketplace\Controllers\AdminMarketplaceController;
use App\Modules\Marketplace\Controllers\MarketplaceController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/marketplace',        [MarketplaceController::class, 'index'])->name('marketplace.index');
    $router->get('/marketplace/{slug}', [MarketplaceController::class, 'show']);
});

// Admin
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/marketplace',                [AdminMarketplaceController::class, 'index'])->name('admin.marketplace');
    $router->post('/marketplace/{id}/onayla',   [AdminMarketplaceController::class, 'approve'])->middleware(['csrf']);
    $router->post('/marketplace/{id}/reddet',   [AdminMarketplaceController::class, 'reject'])->middleware(['csrf']);
    $router->post('/marketplace/{id}/sil',      [AdminMarketplaceController::class, 'delete'])->middleware(['csrf']);
});
