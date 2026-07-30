<?php

use App\Core\Router;
use App\Modules\Ai\Controllers\AiController;
use App\Modules\Ai\Controllers\SiteGeneratorController;

/** @var Router $router */

// 3 bağlam — 3 endpoint, bağımsız middleware
$router->group(['middleware' => ['locale']], function (Router $router) {
    $router->post('/ai/public',   [AiController::class, 'chatPublic'])->middleware(['csrf', 'ratelimit']);
    $router->post('/ai/customer', [AiController::class, 'chatCustomer'])->middleware(['csrf', 'ratelimit']);
    $router->post('/ai/admin',    [AiController::class, 'chatAdmin'])->middleware(['csrf', 'ratelimit']);
    $router->post('/ai/builder',  [AiController::class, 'chatBuilder'])->middleware(['csrf', 'ratelimit']);

    // Faz 6b: AI Site Generator (3 adımlı akış)
    $router->get('/ai/site-olustur',           [SiteGeneratorController::class, 'form'])->name('ai.site.form');
    $router->post('/ai/site-olustur/onizle',   [SiteGeneratorController::class, 'preview'])->middleware(['csrf']);
    $router->post('/ai/site-olustur/uret',     [SiteGeneratorController::class, 'generate'])->middleware(['csrf']);
});

// Admin AI Center + Content API
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/ai-center', [\App\Modules\Ai\Controllers\AiCenterController::class, 'index'])->name('admin.ai_center');
    $router->get('/ai-asistan', [\App\Modules\Ai\Controllers\AiCenterController::class, 'assistant'])->name('admin.ai_assistant');
    $router->post('/api/ai/product', [\App\Modules\Ai\Controllers\ContentApiController::class, 'product'])->middleware(['csrf']);
    $router->post('/api/ai/blog',    [\App\Modules\Ai\Controllers\ContentApiController::class, 'blog'])->middleware(['csrf']);
    $router->post('/api/ai/seo',           [\App\Modules\Ai\Controllers\ContentApiController::class, 'seo'])->middleware(['csrf']);
    $router->post('/api/ai/ticket-reply',  [\App\Modules\Ai\Controllers\ContentApiController::class, 'ticketReply'])->middleware(['csrf']);
    $router->post('/api/ai/provider-test', [\App\Modules\Ai\Controllers\ProviderTestController::class, 'test'])->middleware(['csrf']);
});
