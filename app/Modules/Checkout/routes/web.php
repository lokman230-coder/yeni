<?php

use App\Core\Router;
use App\Modules\Checkout\Controllers\CheckoutController;
use App\Modules\Payment\Controllers\IyzicoController;
use App\Modules\Payment\Controllers\PaparaController;
use App\Modules\Payment\Controllers\PayTrController;
use App\Modules\Payment\Controllers\ShopierController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/odeme',                    [CheckoutController::class, 'index'])->name('checkout.index');
    $router->post('/odeme/tamamla',           [CheckoutController::class, 'process'])->middleware(['csrf']);
    $router->get('/odeme/basarili/{id}',      [CheckoutController::class, 'success'])->name('checkout.success');

    // PayTR
    $router->get('/odeme/paytr/{id}',         [PayTrController::class, 'checkout'])->name('paytr.checkout');
    $router->post('/odeme/paytr/callback',    [PayTrController::class, 'callback']);
    $router->get('/odeme/paytr/basarili',     [PayTrController::class, 'success']);
    $router->get('/odeme/paytr/basarisiz',    [PayTrController::class, 'fail']);

    // iyzico
    $router->get('/odeme/iyzico/{id}',        [IyzicoController::class, 'checkout'])->name('iyzico.checkout');
    $router->post('/odeme/iyzico/callback',   [IyzicoController::class, 'callback']);
    $router->get('/odeme/iyzico/callback',    [IyzicoController::class, 'callback']); // iyzico bazen GET ile döner

    // Papara
    $router->get('/odeme/papara/{id}',        [PaparaController::class, 'checkout'])->name('papara.checkout');
    $router->post('/odeme/papara/callback',   [PaparaController::class, 'callback']);
    $router->get('/odeme/papara/return',      [PaparaController::class, 'returnUrl']);

    // Shopier
    $router->get('/odeme/shopier/{id}',       [ShopierController::class, 'checkout'])->name('shopier.checkout');
    $router->post('/odeme/shopier/callback',  [ShopierController::class, 'callback']);
});
