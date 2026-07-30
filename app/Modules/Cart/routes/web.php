<?php

use App\Core\Router;
use App\Modules\Cart\Controllers\CartController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/sepet',                 [CartController::class, 'index'])->name('cart.index');
    $router->post('/sepet/ekle',           [CartController::class, 'add'])->middleware(['csrf']);
    $router->post('/sepet/{id}/sil',       [CartController::class, 'remove'])->middleware(['csrf']);
    $router->post('/sepet/temizle',        [CartController::class, 'clear'])->middleware(['csrf']);
    $router->post('/sepet/kupon-uygula',   [CartController::class, 'applyCoupon'])->middleware(['csrf']);
});
