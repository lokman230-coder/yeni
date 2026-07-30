<?php
require_once __DIR__ . '/../shared/content-renderer.php';
try {
    $q = db()->prepare('SELECT * FROM announcements WHERE is_active=1 AND channel IN ("site","all") AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY id DESC LIMIT 50');
    $rows = $q->execute() ? $q->fetchAll() : [];
} catch (Throwable $e) {
    $rows = [];
}
ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Son Duyurular</strong><span>•</span><span>Güncel duyurular buradan takip edilebilir.</span></div>
  <?php if ($rows): ?>
    <div class="ao-content-grid">
      <?php foreach ($rows as $announcement): ?>
        <article class="ao-content-card ao-content-card--announcement">
          <span class="ao-content-badge"><?= e($announcement['channel'] ?? 'site') ?></span>
          <h3><a href="<?= e(url('duyurular/'.(int)$announcement['id'])) ?>"><?= e($announcement['title'] ?: 'Duyuru') ?></a></h3>
          <?php if (trim((string)($announcement['short_description'] ?? '')) !== ''): ?>
            <p><?= e(mb_substr(trim((string)$announcement['short_description']), 0, 180, 'UTF-8')) ?></p>
          <?php else: ?>
            <p><?= e(mb_substr(trim((string)$announcement['body'] ?? ''), 0, 180, 'UTF-8')) ?></p>
          <?php endif; ?>
          <div class="ao-content-meta"><?= e($announcement['starts_at'] ?: 'Hemen') ?> • <?= e($announcement['ends_at'] ?: 'Süresiz') ?></div>
          <div class="ao-content-actions"><a class="ao-content-btn secondary" href="<?= e(url('duyurular/'.(int)$announcement['id'])) ?>">Detay</a></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="ao-content-empty"><h3>Aktif duyuru bulunamadı.</h3><p>Şu anda yayımlanmış yeni bir duyuru yok.</p></div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content' => $content,
  'heroTitle' => 'Duyurular',
  'kicker' => 'Ahost One Haberleri',
  'summary' => 'Güncel kampanya, bakım ve hizmet duyurularını burada takip edebilirsiniz.',
  'breadcrumbs' => [['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Duyurular']]
]);
