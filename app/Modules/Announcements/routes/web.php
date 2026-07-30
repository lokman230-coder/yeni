<?php

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Database\Connection;
use App\Core\Router;
use App\Core\View;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/duyurular', function () {
        $view = new View();
        $items = [];
        try { $items = Connection::select("SELECT * FROM announcements WHERE status = 'published' ORDER BY published_at DESC, id DESC"); } catch (\Throwable) {}
        return Response::html($view->render('announcements::index', ['title' => 'Duyurular', 'items' => $items]));
    })->name('announcements.index');
});
