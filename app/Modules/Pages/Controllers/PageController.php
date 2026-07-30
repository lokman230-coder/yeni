<?php

declare(strict_types=1);

namespace App\Modules\Pages\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;

final class PageController
{
    public function show(Request $request): Response
    {
        $slug = ltrim($request->path(), '/');
        try {
            $page = Connection::selectOne(
                "SELECT * FROM cms_pages WHERE slug = ? AND is_published = 1",
                [$slug]
            );
        } catch (\Throwable) {
            $page = null;
        }

        // DB'de yoksa varsayılan boş sayfa
        if (!$page) {
            $page = [
                'title'           => ucwords(str_replace('-', ' ', $slug)),
                'content'         => '<p>Bu sayfanın içeriği henüz eklenmemiş. Admin panelden içerik ekleyebilirsiniz.</p>',
                'seo_title'       => ucwords(str_replace('-', ' ', $slug)),
                'seo_description' => '',
            ];
        }

        $view = new View();
        return Response::html($view->render('pages::show', [
            'title'       => $page['seo_title'] ?: $page['title'],
            'description' => $page['seo_description'] ?? '',
            'page'        => $page,
        ]));
    }
}
