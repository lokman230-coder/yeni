<?php

declare(strict_types=1);

namespace App\Modules\Blog\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Auth\AuthService;
use App\Services\Logger\ActivityLog;
use App\Support\Slug;

final class AdminBlogController
{
    public function index(Request $request): Response
    {
        $posts = Connection::select("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 100");
        $metrics = [
            'total'     => (int)(Connection::selectOne("SELECT COUNT(*) c FROM blog_posts")['c'] ?? 0),
            'published' => (int)(Connection::selectOne("SELECT COUNT(*) c FROM blog_posts WHERE status='published'")['c'] ?? 0),
            'draft'     => (int)(Connection::selectOne("SELECT COUNT(*) c FROM blog_posts WHERE status='draft'")['c'] ?? 0),
            'views'     => (int)(Connection::selectOne("SELECT COALESCE(SUM(views),0) c FROM blog_posts")['c'] ?? 0),
        ];
        $view = new View();
        return Response::html($view->render('blog::admin.index', [
            'title'   => 'Blog',
            'posts'   => $posts,
            'metrics' => $metrics,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function createForm(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('blog::admin.form', [
            'title' => 'Yeni Blog Yazısı',
            'post'  => null,
            'error' => flash('error'),
        ]));
    }

    public function editForm(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = Connection::selectOne("SELECT * FROM blog_posts WHERE id = ?", [$id]);
        if (!$post) return Response::notFound();
        $view = new View();
        return Response::html($view->render('blog::admin.form', [
            'title'   => 'Düzenle: ' . $post['title'],
            'post'    => $post,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function store(Request $request): Response
    {
        $id = (int) $request->param('id');
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            SessionManager::flash('error', 'Başlık zorunlu.');
            return Response::redirect($id ? "/admin/blog/$id" : '/admin/blog/yeni');
        }

        $admin = AuthService::admin();
        $status = (string) $request->input('status', 'draft');

        $data = [
            'title'          => $title,
            'excerpt'        => (string) $request->input('excerpt', ''),
            'body_html'      => (string) $request->input('body_html', ''),
            'category'       => (string) $request->input('category', '') ?: null,
            'tags'           => (string) $request->input('tags', '') ?: null,
            'status'         => in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft',
            'seo_title'      => (string) $request->input('seo_title', '') ?: null,
            'seo_description'=> (string) $request->input('seo_description', '') ?: null,
            'featured_image' => (string) $request->input('featured_image', '') ?: null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            if ($id > 0) {
                Connection::update('blog_posts', $data, 'id = ?', [$id]);
                ActivityLog::log('updated', 'blog_post', $id, "Blog güncellendi: $title");
                SessionManager::flash('success', 'Yazı güncellendi.');
            } else {
                $data['author_id'] = (int)($admin['id'] ?? 0);
                $data['slug'] = Slug::unique($title, 'blog_posts', 'slug');
                if ($status === 'published') $data['published_at'] = date('Y-m-d H:i:s');
                $id = Connection::insert('blog_posts', $data);
                ActivityLog::log('created', 'blog_post', $id, "Yeni blog: $title");
                SessionManager::flash('success', 'Yazı oluşturuldu.');
            }
            return Response::redirect("/admin/blog/$id");
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
            return Response::redirect('/admin/blog');
        }
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = Connection::selectOne("SELECT title FROM blog_posts WHERE id = ?", [$id]);
        Connection::delete('blog_posts', 'id = ?', [$id]);
        ActivityLog::log('deleted', 'blog_post', $id, "Blog silindi: " . ($post['title'] ?? '?'));
        SessionManager::flash('success', 'Yazı silindi.');
        return Response::redirect('/admin/blog');
    }
}
