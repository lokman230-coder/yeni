<?php

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Router;
use App\Core\View;
use App\Modules\Blog\Controllers\AdminBlogController;

/** @var Router $router */

// Public
$router->group(['middleware' => ['locale', 'currency']], function (Router $router) {
    $router->get('/blog', function (Request $r) {
        $posts = Connection::select(
            "SELECT id, title, slug, excerpt, category, featured_image, published_at, views
             FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 30"
        );
        $view = new View();
        return Response::html($view->render('blog::index', ['title' => 'Blog', 'posts' => $posts]));
    })->name('blog.index');

    $router->get('/blog/{slug}', function (Request $r) {
        $slug = (string) $r->param('slug');
        $post = Connection::selectOne("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'", [$slug]);
        if (!$post) return Response::notFound('Yazı bulunamadı');
        Connection::query("UPDATE blog_posts SET views = views + 1 WHERE id = ?", [$post['id']]);
        $view = new View();
        return Response::html($view->render('blog::show', ['title' => $post['title'], 'post' => $post]));
    })->name('blog.show');
});

// Admin
$router->group(['prefix' => 'admin', 'middleware' => ['locale', 'admin.auth']], function (Router $router) {
    $router->get('/blog',                [AdminBlogController::class, 'index'])->name('admin.blog');
    $router->get('/blog/yeni',           [AdminBlogController::class, 'createForm']);
    $router->post('/blog/kaydet',        [AdminBlogController::class, 'store'])->middleware(['csrf']);
    $router->get('/blog/{id}',           [AdminBlogController::class, 'editForm']);
    $router->post('/blog/{id}/kaydet',   [AdminBlogController::class, 'store'])->middleware(['csrf']);
    $router->post('/blog/{id}/sil',      [AdminBlogController::class, 'delete'])->middleware(['csrf']);
});
