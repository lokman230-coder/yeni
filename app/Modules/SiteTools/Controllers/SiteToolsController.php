<?php

declare(strict_types=1);

namespace App\Modules\SiteTools\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\SiteTools\ToolRegistry;

final class SiteToolsController
{
    public function index(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('sitetools::index', [
            'title' => 'Site Araçları',
            'tools' => ToolRegistry::all(),
        ]));
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $tool = ToolRegistry::find($slug);
        if (!$tool) return Response::notFound();

        $input = trim((string) $request->query('q', ''));
        $result = $input !== '' ? $tool->run($input) : null;

        $view = new View();
        return Response::html($view->render('sitetools::show', [
            'title'  => $tool->label(),
            'tool'   => $tool,
            'input'  => $input,
            'result' => $result,
            'tools'  => ToolRegistry::all(),
        ]));
    }
}
