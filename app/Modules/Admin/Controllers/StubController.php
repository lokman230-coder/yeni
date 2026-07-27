<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View;

/**
 * Placeholder controller — Faz 3/4/5'te gerçek controller'larla değiştirilecek.
 */
final class StubController
{
    public function show(string $slug, string $label): Response
    {
        $view = new View();
        return Response::html($view->render('admin::dashboard.stub', [
            'title' => $label,
            'slug'  => $slug,
        ]));
    }
}
