<?php
require_once __DIR__ . '/../shared/content-renderer.php';

$posts = [];
$featured = [];
$categories = [];

try {
    $pdo = db();
    $posts = $pdo->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM blog_posts p
        LEFT JOIN blog_categories c ON c.id = p.category_id
        WHERE p.status = 'published'
        ORDER BY COALESCE(p.published_at, p.created_at) DESC
        LIMIT 12
    ")->fetchAll() ?: [];
    $featured = $pdo->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM blog_posts p
        LEFT JOIN blog_categories c ON c.id = p.category_id
        WHERE p.status = 'published' AND p.is_featured = 1
        ORDER BY COALESCE(p.published_at, p.created_at) DESC
        LIMIT 3
    ")->fetchAll() ?: [];
    $categories = $pdo->query("
        SELECT c.*, COUNT(p.id) AS post_count
        FROM blog_categories c
        LEFT JOIN blog_posts p ON p.category_id = c.id AND p.status = 'published'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.sort_order ASC, c.name ASC
    ")->fetchAll() ?: [];
} catch (Throwable $e) {
    $posts = [];
    $featured = [];
    $categories = [];
}

ob_start();
?>
<?php if ($featured): ?>
<section class="ao-content-panel ao-blog-featured">
    <div class="ao-content-meta">
        <strong>Öne Çıkan Yazılar</strong>
        <span>Güncel rehberler ve duyurular</span>
    </div>
    <?= ao_site_content_grid($featured, [
        'type' => 'blog-featured',
        'href' => fn($item) => url('blog/' . $item['slug']),
        'link_text' => 'Devamını Oku',
        'empty_title' => 'Öne çıkan yazı yok',
    ]) ?>
</section>
<?php endif; ?>

<?php if ($categories): ?>
<nav class="ao-content-pills ao-blog-categories" aria-label="Blog kategorileri">
    <a class="ao-content-pill active" href="<?= e(url('blog')) ?>">Tümü</a>
    <?php foreach ($categories as $category): ?>
        <?php if ((int)($category['post_count'] ?? 0) <= 0) continue; ?>
        <a class="ao-content-pill" href="<?= e(url('blog/category/' . $category['slug'])) ?>">
            <?= e($category['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<?= ao_site_content_grid($posts, [
    'type' => 'blog',
    'href' => fn($item) => url('blog/' . $item['slug']),
    'link_text' => 'Devamını Oku',
    'empty_title' => 'Henüz yazı yok',
    'empty_text' => 'Yeni blog yazıları hazırlandığında bu alanda listelenecek.',
]) ?>
<?php
$content = ob_get_clean();

ao_site_content_page([
    'content' => $content,
    'heroTitle' => 'Ahost One Blog',
    'kicker' => 'Blog & Rehberler',
    'summary' => 'Hosting, domain, güvenlik ve teknoloji rehberleri tek premium içerik görünümüyle yayında.',
    'breadcrumbs' => [
        ['label' => 'Ana Sayfa', 'href' => url('')],
        ['label' => 'Blog'],
    ],
]);
