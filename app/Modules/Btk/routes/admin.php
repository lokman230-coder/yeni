<?php

use App\Core\Router;
use App\Modules\Btk\Controllers\BtkController;

/** @var Router $router */
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/btk-raporu',            [BtkController::class, 'index'])->name('admin.btk');
    $router->get('/btk-raporu/{type}',     [BtkController::class, 'download']);
});
