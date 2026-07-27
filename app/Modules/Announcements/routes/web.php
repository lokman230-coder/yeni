<?php

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/duyurular', function () {
        $view = new View();
        return Response::html($view->render('announcements::index', ['title' => 'Duyurular']));
    })->name('announcements.index');
});
