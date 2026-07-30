<?php
// Single Blog Post - RC6 shared site content UI
$slug = $_GET['slug'] ?? ($slug ?? '');
if(!$slug) { header('Location: ' . url('blog')); exit; }
try {
    $pdo = db();
    $st = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published' LIMIT 1");
    $st->execute([$slug]);
    $post = $st->fetch();
    if(!$post) { http_response_code(404); $pageTitle='Yazı bulunamadı'; require __DIR__.'/../partials/header.php'; echo '<section class="ao-site-content"><div class="ao-content-shell narrow"><div class="ao-content-empty"><h1>Yazı bulunamadı</h1><p>Aradığınız blog yazısı yayında değil veya kaldırılmış.</p><a class="ao-content-btn" href="'.e(url('blog')).'">Bloga Dön</a></div></div></section>'; require __DIR__.'/../partials/footer.php'; exit; }
    $pdo->prepare("UPDATE blog_posts SET view_count=COALESCE(view_count,0)+1 WHERE id=?")->execute([(int)$post['id']]);
    $rel = $pdo->prepare("SELECT id,title,slug,featured_image,excerpt,created_at,published_at FROM blog_posts WHERE status='published' AND category_id=? AND id<>? ORDER BY COALESCE(published_at,created_at) DESC LIMIT 3");
    $rel->execute([(int)($post['category_id'] ?? 0),(int)$post['id']]);
    $related = $rel->fetchAll();
    $cm = $pdo->prepare("SELECT * FROM blog_comments WHERE post_id=? AND status='approved' ORDER BY created_at ASC");
    $cm->execute([(int)$post['id']]);
    $comments = $cm->fetchAll();
} catch(Throwable $e) { $post=null; $related=[]; $comments=[]; }
if(!$post) exit;
$metaTitle = trim((string)($post['meta_title'] ?? '')) ?: (($post['title'] ?? 'Blog') . ' - Ahost One Blog');
$metaDescription = trim((string)($post['meta_description'] ?? '')) ?: trim((string)($post['excerpt'] ?? '')) ?: mb_substr(strip_tags((string)($post['content'] ?? '')),0,160,'UTF-8');
$metaKeywords = trim((string)($post['meta_keywords'] ?? ''));
$ogImage = trim((string)($post['featured_image'] ?? ''));
$canonicalUrl = url('blog/'.$post['slug']);
$schemaJsonLd = json_encode([
    '@context'=>'https://schema.org',
    '@type'=>'Article',
    'headline'=>(string)($post['title'] ?? ''),
    'description'=>mb_substr(strip_tags($metaDescription),0,160,'UTF-8'),
    'url'=>$canonicalUrl,
    'datePublished'=>date('c', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now')),
    'dateModified'=>date('c', strtotime($post['updated_at'] ?? $post['published_at'] ?? $post['created_at'] ?? 'now')),
    'author'=>['@type'=>'Organization','name'=>(string)admin_setting('site_name','Ahost One')]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$pageTitle = $metaTitle;
require __DIR__ . '/../partials/header.php';
ob_start();
?>
<article class="ao-content-panel">
  <?php if(!empty($post['featured_image'])): ?><img class="ao-content-image ao-blog-featured-image" src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>"><?php endif; ?>
  <div class="ao-content-meta">
    <span><?= e($post['category_name'] ?? 'Genel') ?></span><span>•</span>
    <span><?= date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'] ?? 'now')) ?></span>
    <?php if(isset($post['view_count'])): ?><span>•</span><span><?= (int)$post['view_count'] + 1 ?> görüntülenme</span><?php endif; ?>
  </div>
  <div class="ao-content-rich"><?= $post['content'] ?? '' ?></div>
</article>

<section class="ao-content-panel">
  <h3>Paylaş</h3>
  <div class="ao-content-actions">
    <?php $share=url('blog/'.$post['slug']); ?>
    <a class="ao-content-btn secondary" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= urlencode($share) ?>&text=<?= urlencode($post['title']) ?>">X / Twitter</a>
    <a class="ao-content-btn secondary" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($share) ?>">Facebook</a>
    <a class="ao-content-btn secondary" target="_blank" rel="noopener" href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($share) ?>&title=<?= urlencode($post['title']) ?>">LinkedIn</a>
    <a class="ao-content-btn secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($post['title'].' '.$share) ?>">WhatsApp</a>
  </div>
</section>

<?php if($comments || !empty($post['allow_comments'])): ?>
<section class="ao-content-panel">
  <h3>Yorumlar</h3>
  <div class="ao-content-list compact">
  <?php foreach($comments as $c): ?>
    <div class="ao-content-list-item"><div class="ao-content-list-icon"><?= e(mb_substr($c['author_name'] ?? '?',0,1,'UTF-8')) ?></div><div><strong><?= e($c['author_name']) ?></strong><div class="ao-content-meta"><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></div><p><?= e($c['content']) ?></p></div></div>
  <?php endforeach; ?>
  </div>
  <?php if(!empty($post['allow_comments'])): ?>
  <form class="ao-content-panel ao-blog-comment-form" method="post" action="<?= url('blog/comment') ?>">
    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
    <div class="ao-form-grid ao-blog-comment-grid">
      <label>Ad Soyad<input name="author_name" required></label>
      <label>E-posta<input type="email" name="author_email" required></label>
      <label class="full">Yorum<textarea name="content" required></textarea></label>
    </div>
    <button class="ao-content-btn ao-blog-comment-submit" type="submit">Gönder</button>
  </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if($related): ?>
<section>
  <h2>Benzer Yazılar</h2>
  <div class="ao-content-grid">
  <?php foreach($related as $r): ?>
    <article class="ao-content-card">
      <?php if(!empty($r['featured_image'])): ?><img src="<?= e($r['featured_image']) ?>" alt="<?= e($r['title']) ?>"><?php endif; ?>
      <h3><?= e($r['title']) ?></h3>
      <p><?= e(mb_substr(strip_tags((string)($r['excerpt'] ?? '')),0,120,'UTF-8')) ?></p>
      <a class="ao-content-btn secondary" href="<?= url('blog/'.$r['slug']) ?>">Oku</a>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
$heroTitle = $post['title'];
$kicker = $post['category_name'] ?? 'Blog';
$summary = trim($post['excerpt'] ?? '') ?: mb_substr(strip_tags((string)($post['content'] ?? '')),0,170,'UTF-8');
$breadcrumbs = [['label'=>'Ana Sayfa','href'=>url('')], ['label'=>'Blog','href'=>url('blog')], ['label'=>$post['title']]];
$narrow = true;
require __DIR__ . '/../shared/content-page.php';
require __DIR__ . '/../partials/footer.php';
