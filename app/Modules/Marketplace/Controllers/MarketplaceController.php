<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Marketplace\Services\MarketplaceService;

final class MarketplaceController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q'           => (string) $request->query('q', ''),
            'category_id' => (int) $request->query('category_id', 0) ?: null,
        ];
        $view = new View();
        return Response::html($view->render('marketplace::index', [
            'title'      => 'Marketplace',
            'categories' => MarketplaceService::categories(),
            'listings'   => MarketplaceService::listings(array_filter($filters, fn($v) => $v !== '' && $v !== null && $v !== 0)),
            'filters'    => $filters,
        ]));
    }

    public function show(Request $request): Response
    {
        $listing = MarketplaceService::findBySlug((string) $request->param('slug'));
        if (!$listing) return Response::notFound();
        $view = new View();
        return Response::html($view->render('marketplace::show', [
            'title'   => $listing['title'],
            'listing' => $listing,
        ]));
    }
}
