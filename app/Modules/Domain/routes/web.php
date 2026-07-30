<?php

use App\Core\Router;
use App\Modules\Domain\Controllers\AdminDomainController;
use App\Modules\Domain\Controllers\DomainController;

/** @var Router $router */

// Public: domain sorgulama
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/domain', [DomainController::class, 'index'])->name('domain.search');
});

// Admin: Domain Center CRUD
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/domain-center',                 [AdminDomainController::class, 'index'])->name('admin.domain.index');
    $router->get('/domain-center/yeni',            [AdminDomainController::class, 'createForm'])->name('admin.domain.create');
    $router->post('/domain-center/kaydet',         [AdminDomainController::class, 'store'])->middleware(['csrf']);
    $router->get('/domain-center/{id}',            [AdminDomainController::class, 'show'])->name('admin.domain.show');
    $router->post('/domain-center/{id}/kaydet',    [AdminDomainController::class, 'save'])->middleware(['csrf']);
    $router->post('/domain-center/{id}/sil',       [AdminDomainController::class, 'delete'])->middleware(['csrf']);
    $router->post('/domain-center/{id}/whois',     [AdminDomainController::class, 'refreshWhois'])->middleware(['csrf']);
});
