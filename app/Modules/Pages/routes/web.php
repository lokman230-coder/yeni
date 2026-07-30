<?php

use App\Core\Router;
use App\Modules\Pages\Controllers\PageController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $slugs = [
        'hakkimizda', 'misyon', 'vizyon',
        'gizlilik-politikasi', 'cerez-politikasi', 'kullanim-sartlari',
        'hizmet-politikasi', 'iade-sartlari',
    ];
    foreach ($slugs as $slug) {
        $router->get('/' . $slug, [PageController::class, 'show'])
            ->name('pages.' . str_replace('-', '_', $slug));
    }
});
