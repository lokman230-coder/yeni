<?php

use App\Core\Router;
use App\Modules\Import\Controllers\AdminImportController;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/veri-aktarimi',                       [AdminImportController::class, 'index'])->name('admin.import');
    $router->get('/veri-aktarimi/sema-kontrol',           [AdminImportController::class, 'schemaCheck']);
    $router->post('/veri-aktarimi/sema-kontrol/otomatik-onar', [AdminImportController::class, 'schemaAutoFix'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/sema-kontrol/elle-ekle',     [AdminImportController::class, 'schemaManualAdd'])->middleware(['csrf']);
    $router->get('/veri-aktarimi/baglan/{source}',       [AdminImportController::class, 'connect']);
    $router->post('/veri-aktarimi/baglan/{source}/test', [AdminImportController::class, 'testConnection'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/baglan/{source}/sql-yukle', [AdminImportController::class, 'connectSqlUpload'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/baglan/{source}/baslat',[AdminImportController::class, 'startImport'])->middleware(['csrf']);
    $router->get('/veri-aktarimi/is/{id}',               [AdminImportController::class, 'jobDetail']);
    $router->post('/veri-aktarimi/is/{id}/calistir',     [AdminImportController::class, 'runJob'])->middleware(['csrf']);
    $router->post('/veri-aktarimi/is/{id}/sil',          [AdminImportController::class, 'deleteJob'])->middleware(['csrf']);
});
