<?php

use App\Core\Router;
use App\Modules\Import\Controllers\AdminImportController;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/veri-aktarimi',                       [AdminImportController::class, 'index'])->name('admin.import');
    $router->get('/veri-aktarimi/baglan/{source}',       [AdminImportController::class, 'connect']);
    $router->post('/veri-aktarimi/baglan/{source}/test', [AdminImportController::class, 'testConnection'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/baglan/{source}/baslat',[AdminImportController::class, 'startImport'])->middleware(['csrf']);
    $router->get('/veri-aktarimi/is/{id}',               [AdminImportController::class, 'jobDetail']);
    $router->post('/veri-aktarimi/is/{id}/calistir',     [AdminImportController::class, 'runJob'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/is/{id}/sil',          [AdminImportController::class, 'deleteJob'])->middleware(['csrf']);
});
