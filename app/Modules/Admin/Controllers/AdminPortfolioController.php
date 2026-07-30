<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Support\Slug;

final class AdminPortfolioController
{
    public function index(Request $request): Response
    {
        $projects = Connection::select("SELECT * FROM portfolio_projects ORDER BY sort_order, id DESC");
        return Response::html((new View())->render('admin::portfolio.index', [
            'title'    => 'Portfolio / Referanslar',
            'projects' => $projects,
        ]));
    }

    public function create(Request $request): Response
    {
        return Response::html((new View())->render('admin::portfolio.form', [
            'title'   => 'Yeni Portfolio Projesi',
            'project' => null,
        ]));
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $project = Connection::selectOne("SELECT * FROM portfolio_projects WHERE id = ?", [$id]);
        if (!$project) return Response::notFound();
        return Response::html((new View())->render('admin::portfolio.form', [
            'title'   => 'Düzenle: ' . $project['title'],
            'project' => $project,
        ]));
    }

    public function store(Request $request): Response
    {
        $data = $this->extract($request);
        $data['slug'] = Slug::make($data['title']);
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $data['published_at'] = $data['is_published'] ? date('Y-m-d H:i:s') : null;

        $id = Connection::insert('portfolio_projects', $data);
        SessionManager::flash('success', 'Portfolio eklendi.');
        return Response::redirect('/admin/portfolio/' . $id . '/duzenle');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $data = $this->extract($request);
        $data['updated_at'] = date('Y-m-d H:i:s');
        Connection::update('portfolio_projects', $data, 'id = ?', [$id]);
        SessionManager::flash('success', 'Güncellendi.');
        return Response::redirect('/admin/portfolio/' . $id . '/duzenle');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::query("DELETE FROM portfolio_projects WHERE id = ?", [$id]);
        SessionManager::flash('success', 'Silindi.');
        return Response::redirect('/admin/portfolio');
    }

    private function extract(Request $request): array
    {
        $techs = trim((string) $request->input('technologies', ''));
        return [
            'title'          => trim((string) $request->input('title', '')),
            'client_name'    => trim((string) $request->input('client_name', '')) ?: null,
            'category'       => in_array($request->input('category'), ['web','mobile','ecommerce','corporate','landing','custom','saas','marketplace','portfolio'], true) ? $request->input('category') : 'web',
            'sector'         => (string) $request->input('sector', '') ?: null,
            'description'    => (string) $request->input('description', '') ?: null,
            'challenge'      => (string) $request->input('challenge', '') ?: null,
            'solution'       => (string) $request->input('solution', '') ?: null,
            'preview_url'    => (string) $request->input('preview_url', '') ?: null,
            'thumbnail'      => (string) $request->input('thumbnail', '') ?: null,
            'technologies'   => $techs ? json_encode(array_map('trim', explode(',', $techs)), JSON_UNESCAPED_UNICODE) : null,
            'customer_quote' => (string) $request->input('customer_quote', '') ?: null,
            'duration_days'  => (int) $request->input('duration_days', 0) ?: null,
            'sort_order'     => (int) $request->input('sort_order', 0),
            'is_featured'    => $request->input('is_featured') ? 1 : 0,
            'is_published'   => $request->input('is_published') ? 1 : 0,
        ];
    }
}
