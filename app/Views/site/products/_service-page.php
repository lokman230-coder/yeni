<?php
$service = $service ?? [];
$title = $service['title'] ?? 'Ahost One Hizmetleri';
$kicker = $service['kicker'] ?? 'Ahost One';
$summary = $service['summary'] ?? 'Hosting, domain, builder ve dijital hizmetleri tek merkezden yönetin.';
$primary = $service['primary'] ?? ['Sipariş Başlat', 'urunler'];
$secondary = $service['secondary'] ?? ['Dijital Hizmetler', 'dijital-hizmetler'];
$finalCta = $service['final_cta'] ?? [
  'title' => 'Projenizi Ahost One ekosistemine taşıyın',
  'text' => 'İhtiyacınıza uygun paketi seçin; kurulum, yayın ve destek sürecini anlaşılır adımlarla başlatın.',
  'primary' => $primary,
  'secondary' => ['Ürünleri Gör', 'urunler'],
];
$panel = $service['panel'] ?? [
  'title' => 'Sizin için ne sağlar?',
  'items' => ['İhtiyaca uygun paket seçimi', 'Hızlı başlangıç ve yönlendirme', 'Yayın sonrası destek imkanı'],
];
$cards = $service['cards'] ?? [];
$features = $service['features'] ?? [];
?>
<section class="ao-public-page ao-service-page">
  <div class="ao-public-shell">
    <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render(null, ['title'=>$title]) : ''; ?>
    <?php if($managedHero): ?>
      <?= $managedHero ?>
    <?php else: ?>
    <section class="ao-service-hero">
      <div>
        <span class="ao-kicker"><?= e($kicker) ?></span>
        <h1><?= e($title) ?></h1>
        <p><?= e($summary) ?></p>
        <div class="ao-content-actions">
          <a class="ao-content-btn" href="<?= url($primary[1]) ?>"><?= e($primary[0]) ?></a>
          <a class="ao-content-btn secondary" href="<?= url($secondary[1]) ?>"><?= e($secondary[0]) ?></a>
        </div>
      </div>
      <aside class="ao-service-panel">
        <b><?= e($panel['title'] ?? 'Sizin için ne sağlar?') ?></b>
        <ul>
          <?php foreach (($panel['items'] ?? []) as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </aside>
    </section>
    <?php endif; ?>

    <section class="ao-service-grid">
      <?php foreach ($cards as $card): ?>
        <article class="ao-service-card-pro">
          <div class="ao-service-icon"><?= e($card['icon'] ?? '+') ?></div>
          <h3><?= e($card['title'] ?? 'Hizmet') ?></h3>
          <p><?= e($card['text'] ?? '') ?></p>
          <a class="ao-content-btn secondary" href="<?= url($card['href'] ?? 'teklif') ?>"><?= e($card['action'] ?? 'İncele') ?> →</a>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="ao-feature-strip">
      <?php foreach ($features as $feature): ?>
        <div>
          <strong><?= e($feature[0]) ?></strong>
          <span><?= e($feature[1]) ?></span>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="ao-content-cta">
      <h2><?= e($finalCta['title'] ?? 'Projenizi Ahost One ekosistemine taşıyın') ?></h2>
      <p><?= e($finalCta['text'] ?? 'İhtiyacınıza uygun paketi seçin; kurulum, yayın ve destek sürecini anlaşılır adımlarla başlatın.') ?></p>
      <div class="ao-content-actions">
        <a class="ao-content-btn" href="<?= url($finalCta['primary'][1] ?? $primary[1]) ?>"><?= e($finalCta['primary'][0] ?? $primary[0]) ?></a>
        <a class="ao-content-btn secondary" href="<?= url($finalCta['secondary'][1] ?? 'urunler') ?>"><?= e($finalCta['secondary'][0] ?? 'Ürünleri Gör') ?></a>
      </div>
    </section>
  </div>
</section>
