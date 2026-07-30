<?php

use App\Core\Router;
use App\Modules\Home\Controllers\HomeController;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index'])->name('home')->middleware(['locale', 'currency']);
