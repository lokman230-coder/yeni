<?php

use App\Core\Router;
use App\Modules\Health\Controllers\HealthController;

/** @var Router $router */
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/health-center', [HealthController::class, 'index'])->name('admin.health');
    $router->get('/qa-scan-center', [HealthController::class, 'qa'])->name('admin.qa_scan');
});

// Public healthcheck (monitor için)
$router->get('/health', [HealthController::class, 'ping'])->name('health.ping');
