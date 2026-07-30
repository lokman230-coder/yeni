<?php
ao_catalog_ensure_showcase_schema();
$rows = [];
try {
    $rows = db()->query('SELECT * FROM portfolio_references WHERE is_active=1 ORDER BY is_featured DESC,sort_order,id')->fetchAll() ?: [];
} catch (Throwable $e) {
    $rows = [];
}

$cats = [
    'all' => 'Tümü',
    'website' => 'Web Siteleri',
    'android' => 'Android',
    'ios' => 'iOS',
    'ecommerce' => 'E-Ticaret',
    'corporate' => 'Kurumsal',
];

function ao_reference_cover_v2464($cover, $image) {
    $root = dirname(__DIR__, 4);
    foreach ([trim((string)$cover), trim((string)$image), 'public/assets/img/placeholder-product.svg'] as $asset) {
        if ($asset === '') continue;
        if (preg_match('~^https?://~i', $asset)) return $asset;
        $path = $root . '/' . ltrim(str_replace('\\', '/', $asset), '/');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'svg' || !is_file($path)) return $asset;
        $info = @getimagesize($path);
        if ($info && (int)$info[0] >= 700 && (int)$info[1] >= 390) return $asset;
    }
    return 'public/assets/img/placeholder-product.svg';
}

function ao_reference_cards_v2464($items) {
    foreach ($items as $r):
        $type = $r['reference_type'] ?: 'website';
        $cover = ao_reference_cover_v2464($r['cover_image_url'] ?? '', $r['image_url'] ?? '');
        $logo = trim((string)($r['logo_url'] ?? ''));
        $projectUrl = trim((string)($r['project_url'] ?? ''));
        $cardBg = trim((string)($r['card_background_url'] ?? ''));
        $cardStyle = $cardBg !== '' ? "--ref-card-bg:url('" . e(url($cardBg)) . "')" : '';
        ?>
        <article class="ao-content-card ref-card" data-builder-block="reference-card" data-builder-label="<?= e($r['title'] ?? 'Referans Kartı') ?>" data-ref-type="<?= e($type) ?>" data-ref-sector="<?= e(mb_strtolower((string)($r['sector'] ?? ''), 'UTF-8')) ?>"<?= $cardStyle !== '' ? ' style="' . $cardStyle . '"' : '' ?>>
            <?php if ($projectUrl): ?>
                <a class="ref-cover <?= e($type) ?>" data-builder-block="reference-cover" data-ao-image-target target="_blank" rel="noopener" href="<?= e($projectUrl) ?>" style="background-image:url('<?= e(url($cover)) ?>')"><span><?= $type === 'android' ? 'Android' : 'Web' ?></span></a>
            <?php else: ?>
                <div class="ref-cover <?= e($type) ?>" data-builder-block="reference-cover" data-ao-image-target style="background-image:url('<?= e(url($cover)) ?>')"><span><?= $type === 'android' ? 'Android' : 'Web' ?></span></div>
            <?php endif; ?>
            <div class="ref-body" data-builder-block="reference-content">
                <?php if ($logo): ?><img class="ref-logo" src="<?= e(url($logo)) ?>" alt="<?= e($r['title'] ?? '') ?> logo"><?php endif; ?>
                <small><?= e($r['sector'] ?? '') ?></small>
                <h3><?= e($r['title'] ?? '') ?></h3>
                <p><?= e($r['short_description'] ?? '') ?></p>
                <?php if ($projectUrl): ?><div class="ref-site-url"><?= e(parse_url($projectUrl, PHP_URL_HOST) ?: $projectUrl) ?></div><?php endif; ?>
                <div class="ref-tech"><?= e($r['technologies'] ?? '') ?></div>
                <?php if ($projectUrl): ?>
                    <a class="ref-visit-btn" target="_blank" rel="noopener" href="<?= e($projectUrl) ?>"><span class="ref-visit-icon">↗</span><span>Siteyi Gör</span></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach;
}
?>
<section class="ao-site-content ao-references-page">
  <div class="ao-content-shell ref-page">
    <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('referanslar', ['title'=>'Fikirleri çalışan dijital ürünlere dönüştürüyoruz.']) : ''; ?>
    <?php if($managedHero): ?>
      <?= $managedHero ?>
    <?php else: ?>
    <header class="ao-content-hero ref-hero">
      <span class="ao-content-kicker">Seçili Çalışmalar</span>
      <h1>Fikirleri çalışan dijital ürünlere dönüştürüyoruz.</h1>
      <p>Web siteleri ve Android uygulamalarını kategorilerle yan yana keşfedin; sayfa aşağı kaymadan filtreleyin.</p>
      <div class="ao-content-actions">
        <a class="ao-content-btn" href="<?= url('teklif') ?>">Teklif Al</a>
        <a class="ao-content-btn secondary" href="<?= url('urunler') ?>">Çözümleri İnceleyin</a>
      </div>
    </header>
    <?php endif; ?>

    <nav class="ref-tabs" aria-label="Referans kategorileri">
      <?php foreach ($cats as $k => $label): ?>
        <a href="#" class="ref-tab <?= $k === 'all' ? 'is-active' : '' ?>" data-ref-filter="<?= e($k) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="ref-grid" data-ref-grid><?php ao_reference_cards_v2464($rows); ?></div>
  </div>
</section>
<script>
document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-ref-filter]');
  if (!t) return;
  e.preventDefault();
  var f = t.dataset.refFilter;
  document.querySelectorAll('[data-ref-filter]').forEach(function (x) {
    x.classList.toggle('is-active', x === t);
  });
  var grid = document.querySelector('[data-ref-grid]');
  if (!grid) return;
  grid.classList.toggle('is-filtered', f !== 'all');
  grid.querySelectorAll('.ref-card').forEach(function (card) {
    var sector = card.dataset.refSector || '';
    var ok = f === 'all'
      || card.dataset.refType === f
      || (f === 'ecommerce' && sector.includes('ticaret'))
      || (f === 'corporate' && sector.includes('kurumsal'));
    card.classList.toggle('is-visible', ok);
  });
});
</script>
