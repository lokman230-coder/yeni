<?php

use App\Core\Router;
use App\Modules\SiteTools\Controllers\SiteToolsController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/site-araclari',        [SiteToolsController::class, 'index'])->name('sitetools.index');
    $router->get('/site-araclari/{slug}', [SiteToolsController::class, 'show'])->name('sitetools.show');
});
