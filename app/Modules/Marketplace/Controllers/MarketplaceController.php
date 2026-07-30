<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Marketplace\Services\MarketplaceService;
use App\Services\Auth\AuthService;

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
            'files'   => MarketplaceService::files((int) $listing['id']),
        ]));
    }

    public function purchases(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }

        $customer = AuthService::customer();

        return Response::html((new View())->render('marketplace::purchases', [
            'title' => 'Marketplace Purchases',
            'purchases' => MarketplaceService::purchasesForCustomer((int) $customer['id']),
        ]));
    }

    public function token(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }

        $customer = AuthService::customer();
        $result = MarketplaceService::issueDownloadToken(
            (int) $request->param('id'),
            (int) $customer['id'],
            (int) $request->input('file_id', 0) ?: null
        );

        return Response::json($result, !empty($result['ok']) ? 200 : 404);
    }

    public function download(Request $request): Response
    {
        $result = MarketplaceService::resolveDownload((string) $request->param('token'));
        if (empty($result['ok'])) {
            return Response::notFound('Download link is invalid or expired.');
        }

        $path = (string) $result['path'];
        $name = basename((string) $result['file_name']);

        return Response::make((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Content-Length' => (string) filesize($path),
        ]);
    }
}
