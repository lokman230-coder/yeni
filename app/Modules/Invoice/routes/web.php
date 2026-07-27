<?php

use App\Core\Router;
use App\Modules\Invoice\Controllers\InvoiceController;
use App\Modules\Invoice\Controllers\InvoicePayController;

/** @var Router $router */

// Customer PDF + Öde
$router->group(['middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/panel/fatura/{id}/pdf', [InvoiceController::class, 'customerPdf']);
    $router->get('/odeme/{id}',            [InvoicePayController::class, 'show']);
    $router->post('/odeme/{id}/tamamla',   [InvoicePayController::class, 'process'])->middleware(['csrf']);
});

// Admin PDF
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/faturalar/{id}/pdf',    [InvoiceController::class, 'adminPdf']);
});
