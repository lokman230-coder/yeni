<?php

use App\Core\Router;
use App\Modules\Contact\Controllers\ContactController;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/iletisim', [ContactController::class, 'show'])->name('contact.show');
    $router->post('/iletisim', [ContactController::class, 'submit'])
        ->middleware(['csrf', 'ratelimit'])->name('contact.submit');
});
