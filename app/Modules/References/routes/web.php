<?php

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;

/** @var Router $router */
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/referanslar', function () {
        $view = new View();
        return Response::html($view->render('references::index', ['title' => 'Referanslar']));
    })->name('references.index');

    $router->get('/referanslar/{slug}', function (Request $r) {
        $project = Connection::selectOne(
            "SELECT * FROM portfolio_projects WHERE slug = ? AND is_published = 1",
            [(string) $r->param('slug')]
        );
        if (!$project) return Response::notFound();

        // Aynı kategoriden 3 öneri
        $related = Connection::select(
            "SELECT id, title, slug, sector, thumbnail FROM portfolio_projects
             WHERE category = ? AND id != ? AND is_published = 1
             ORDER BY RAND() LIMIT 3",
            [$project['category'], $project['id']]
        );

        $view = new View();
        return Response::html($view->render('references::show', [
            'title'   => $project['title'],
            'project' => $project,
            'related' => $related,
        ]));
    })->name('references.show');
});
