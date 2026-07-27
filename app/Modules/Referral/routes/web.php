<?php

use App\Core\Router;
use App\Modules\Referral\Controllers\AdminReferralController;
use App\Modules\Referral\Controllers\ReferralController;

/** @var Router $router */

// Müşteri paneli
$router->group(['middleware' => ['locale', 'customer.auth']], function (Router $router) {
    $router->get('/panel/referanslarim',                  [ReferralController::class, 'index'])->name('customer.referrals');
    $router->post('/panel/referanslarim/payout',          [ReferralController::class, 'requestPayout'])->middleware(['csrf']);
    $router->post('/panel/referanslarim/payout/{id}/iptal',[ReferralController::class, 'cancelPayout'])->middleware(['csrf']);
});

// Admin
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/referral',                       [AdminReferralController::class, 'index'])->name('admin.referral');
    $router->post('/referral/ayarlar',              [AdminReferralController::class, 'saveSettings'])->middleware(['csrf']);
    $router->post('/referral/komisyon/{id}/onayla', [AdminReferralController::class, 'approve'])->middleware(['csrf']);
    $router->post('/referral/komisyon/{id}/reddet', [AdminReferralController::class, 'reject'])->middleware(['csrf']);
    $router->post('/referral/payout/{id}/onayla',   [AdminReferralController::class, 'approvePayout'])->middleware(['csrf']);
    $router->post('/referral/payout/{id}/odendi',   [AdminReferralController::class, 'markPayoutPaid'])->middleware(['csrf']);
    $router->post('/referral/payout/{id}/reddet',   [AdminReferralController::class, 'rejectPayout'])->middleware(['csrf']);
});
