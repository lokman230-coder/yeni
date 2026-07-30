<?php

use App\Core\Router;
use App\Modules\License\Controllers\AdminLicenseController;
use App\Modules\License\Controllers\LicenseApiController;

/** @var Router $router */

// Public API — script'ler buraya çağırır (CORS açık)
$router->post('/api/license/verify', [LicenseApiController::class, 'verify']);
$router->post('/api/license/envato', [LicenseApiController::class, 'envato']);
$router->get('/api/license/verify',  [LicenseApiController::class, 'verify']); // GET de kabul et

// Admin CRUD
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/lisanslar',                     [AdminLicenseController::class, 'index'])->name('admin.licenses.index');
    $router->get('/lisanslar/yeni',                [AdminLicenseController::class, 'create']);
    $router->post('/lisanslar/kaydet',             [AdminLicenseController::class, 'store'])->middleware(['csrf']);
    $router->get('/lisanslar/{id}',                [AdminLicenseController::class, 'show'])->name('admin.licenses.show');
    $router->post('/lisanslar/{id}/iptal',         [AdminLicenseController::class, 'revoke'])->middleware(['csrf']);
    $router->post('/lisanslar/{id}/aktivasyon-kapat', [AdminLicenseController::class, 'deactivateActivation'])->middleware(['csrf']);
});
