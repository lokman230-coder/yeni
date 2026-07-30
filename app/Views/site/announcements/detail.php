<?php
require_once __DIR__ . '/../shared/content-renderer.php';
$announcement = $announcement ?? null;
if (!$announcement) {
    http_response_code(404);
    echo '<div class="ao-content-empty"><h3>Duyuru bulunamadı.</h3><p>Seçilen duyuru aktif değil veya mevcut değil.</p></div>';
    return;
}
$summary = trim((string)($announcement['short_description'] ?? '')) ?: mb_substr(trim((string)$announcement['body'] ?? ''), 0, 180, 'UTF-8');
$body = trim((string)$announcement['body'] ?? '');
ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong><?= e($announcement['channel'] ?? 'site') ?></strong><span>•</span><span><?= e($announcement['starts_at'] ?: 'Hemen') ?> - <?= e($announcement['ends_at'] ?: 'Süresiz') ?></span></div>
  <article class="ao-content-card ao-content-card--detail">
    <h2><?= e($announcement['title'] ?: 'Duyuru') ?></h2>
    <?php if ($summary): ?><p><?= e($summary) ?></p><?php endif; ?>
    <div class="ao-content-body"><?= nl2br(e($body)) ?></div>
  </article>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content' => $content,
  'heroTitle' => $announcement['title'] ?: 'Duyuru',
  'kicker' => 'Duyuru Detayı',
  'summary' => $summary,
  'breadcrumbs' => [['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Duyurular','href'=>url('duyurular')],['label'=>'Detay']]
]);
