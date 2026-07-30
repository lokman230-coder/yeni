<?php

declare(strict_types=1);

namespace App\Modules\Home\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;

final class HomeController
{
    public function index(Request $request): Response
    {
        $view = new View();
        $html = $view->render('home::index', [
            'title'       => 'Ana Sayfa',
            'description' => 'Ahost Bilişim — Modern hosting, domain, site builder ve mobil uygulama platformu.',
        ]);
        return Response::html($html);
    }
}
