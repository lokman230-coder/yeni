<?php

use App\Core\Router;
use App\Modules\Hosting\Controllers\AdminServerController;

/** @var Router $router */

// Admin: Hosting sunucu CRUD
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/hosting-sunucu',                [AdminServerController::class, 'index'])->name('admin.servers');
    $router->get('/hosting-sunucu/yeni',           [AdminServerController::class, 'createForm']);
    $router->post('/hosting-sunucu/kaydet',        [AdminServerController::class, 'store'])->middleware(['csrf']);
    $router->get('/hosting-sunucu/{id}',           [AdminServerController::class, 'editForm']);
    $router->post('/hosting-sunucu/{id}/kaydet',   [AdminServerController::class, 'store'])->middleware(['csrf']);
    $router->post('/hosting-sunucu/{id}/test',     [AdminServerController::class, 'test']);
    $router->post('/hosting-sunucu/{id}/sil',      [AdminServerController::class, 'delete'])->middleware(['csrf']);
});
