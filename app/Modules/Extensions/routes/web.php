<?php

use App\Core\Router;
use App\Modules\Extensions\Controllers\ExtensionAdminController;
use App\Modules\Extensions\Controllers\ExtensionApiController;

/** @var Router $router */

$router->group(['middleware' => ['locale', 'ratelimit']], function (Router $router) {
    $router->post('/api/live-chat/start', [ExtensionApiController::class, 'chatStart'])->middleware(['csrf']);
    $router->post('/api/live-chat/message', [ExtensionApiController::class, 'chatMessage'])->middleware(['csrf']);
    $router->get('/api/live-chat/{id}', [ExtensionApiController::class, 'chatStatus']);
    $router->post('/api/forms/{slug}/submit', [ExtensionApiController::class, 'formSubmit'])->middleware(['csrf']);
    $router->get('/api/popups/active', [ExtensionApiController::class, 'activePopups']);
    $router->post('/api/popups/event', [ExtensionApiController::class, 'popupEvent'])->middleware(['csrf']);
    $router->post('/api/integrations/event', [ExtensionApiController::class, 'integrationEvent']);
});

$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/live-chat', [ExtensionAdminController::class, 'liveChat'])->name('admin.live_chat');
    $router->post('/live-chat/{id}/reply', [ExtensionAdminController::class, 'chatReply'])->middleware(['csrf']);
    $router->get('/form-builder', [ExtensionAdminController::class, 'forms'])->name('admin.form_builder');
    $router->get('/popup-builder', [ExtensionAdminController::class, 'popups'])->name('admin.popup_builder');
    $router->get('/seo-analyzer', [ExtensionAdminController::class, 'seo'])->name('admin.seo_analyzer');
    $router->post('/seo-analyzer/analyze', [ExtensionAdminController::class, 'seoAnalyze'])->middleware(['csrf']);
    $router->get('/integrations', [ExtensionAdminController::class, 'integrations'])->name('admin.integrations');
    $router->get('/production-readiness', [ExtensionAdminController::class, 'readiness'])->name('admin.production_readiness');
    $router->get('/api/production-readiness', [ExtensionAdminController::class, 'readinessJson']);
});
