<?php

use App\Core\Router;
use App\Modules\Ticket\Controllers\TicketController;

/** @var Router $router */

// Müşteri paneli
$router->group(['prefix' => 'panel', 'middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/destek',            [TicketController::class, 'customerList'])->name('customer.tickets');
    $router->get('/destek/yeni',       [TicketController::class, 'customerNew']);
    $router->post('/destek/yeni',      [TicketController::class, 'customerCreate'])->middleware(['csrf']);
    $router->get('/destek/{id}',       [TicketController::class, 'customerShow']);
    $router->post('/destek/{id}/yanit',[TicketController::class, 'customerReply'])->middleware(['csrf']);
    $router->get('/destek/{id}/ek/{att}', [TicketController::class, 'downloadAttachment']);
});

// Admin
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/destek-merkezi',            [TicketController::class, 'adminList'])->name('admin.tickets');
    $router->get('/destek-merkezi/{id}',       [TicketController::class, 'adminShow']);
    $router->post('/destek-merkezi/{id}/yanit',[TicketController::class, 'adminReply'])->middleware(['csrf']);
    $router->post('/destek-merkezi/{id}/guncelle',[TicketController::class, 'adminUpdate'])->middleware(['csrf']);
    $router->get('/destek-merkezi/{id}/ek/{att}', [TicketController::class, 'downloadAttachment']);
});
