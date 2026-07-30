<section class="ao-site-content ao-service-landing-page ao-web-design-page"><div class="ao-content-shell">
  <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('dijital-hizmetler', ['title'=>'Web, mobil, SEO ve dijital büyüme hizmetleri.']) : ''; ?>
  <?php if($managedHero): ?><?= $managedHero ?><?php else: ?><div class="ao-content-hero"><span class="ao-content-kicker">Dijital Hizmetler</span><h1>Web, mobil, SEO ve dijital büyüme hizmetleri.</h1><p>Markanız için web tasarım, mobil uygulama, SEO, reklam ve dijital ürün çözümlerini tek çatı altında keşfedin.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('marketplace') ?>">Marketplace'i Keşfet</a><a class="ao-content-btn secondary" href="<?= url('teklif') ?>">Teklif Al</a></div></div><?php endif; ?>
  <div class="ao-content-grid ao-web-service-grid">
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">💻</div>
        <div class="ao-web-service-copy"><h3>Web Tasarım</h3><p>Kurumsal web sitesi, e-ticaret, portal ve özel yazılım projeleri.</p><div class="ao-web-service-tags"><span>🎨 Modern Tasarım</span><span>📈 SEO Uyumlu</span><span>⚡ Hız & Performans</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('web-tasarim') ?>">İncele →</a><small>🛡 Güvenli ve Ölçeklenebilir</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">📲</div>
        <div class="ao-web-service-copy"><h3>Mobil Uygulama</h3><p>Android/iOS uygulama, bildirimler, marka ekranları ve mağaza yayını.</p><div class="ao-web-service-tags"><span>🤖 Android</span><span>🍎 iOS</span><span>🔗 Entegre Yapı</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('mobil-uygulama') ?>">İncele →</a><small>🛡 Mağaza Yayın Desteği</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">🛍</div>
        <div class="ao-web-service-copy"><h3>Marketplace</h3><p>Hazır tema, script, uygulama ve dijital hizmet ilanları.</p><div class="ao-web-service-tags"><span>🎨 Hazır Tema</span><span>🧩 Script & Eklenti</span><span>🛒 Dijital Ürün</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('marketplace') ?>">Pazara git →</a><small>🔐 Güvenli Alışveriş</small></div>
      </article>
  </div>
  <div class="ao-content-cta"><h2>Dijital projeniz için doğru paketi seçin</h2><p>Web sitesi, mobil uygulama, SEO veya dijital ürün ihtiyacınızı netleştirip hızlıca başlayın.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('teklif') ?>">Teklif Al</a><a class="ao-content-btn secondary" href="<?= url('urunler') ?>">Ürünleri Gör</a></div></div>
</div></section>
