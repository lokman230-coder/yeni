<?php

use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/bilgi-bankasi', function () {
        $view = new View();
        return Response::html($view->render('knowledge::index', ['title' => 'Bilgi Bankası']));
    })->name('knowledge.index');
});
