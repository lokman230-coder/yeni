<section class="ao-site-content ao-service-landing-page ao-web-design-page"><div class="ao-content-shell">
  <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('web-tasarim', ['title'=>'Kurumsal web siteleri, e-ticaret, portal ve özel yazılım projelerinizi yayına hazırlayın.']) : ''; ?>
  <?php if($managedHero): ?><?= $managedHero ?><?php else: ?><div class="ao-content-hero"><span class="ao-content-kicker">Web Tasarım Merkezi</span><h1>Kurumsal web siteleri, e-ticaret, portal ve özel yazılım projelerinizi yayına hazırlayın.</h1><p>Markanıza uygun tasarım, SEO uyumlu yapı, hızlı açılış ve yayın sonrası destekle web projenizi güvenle başlatın.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('teklif') ?>">Site Tasarlat</a><a class="ao-content-btn secondary" href="<?= url('dijital-hizmetler') ?>">Dijital Hizmetler</a></div></div><?php endif; ?>
  <div class="ao-content-grid ao-web-service-grid">
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">🖥️</div>
        <div class="ao-web-service-copy"><h3>Kurumsal Site</h3><p>Firma kimliğinize uygun, hızlı açılan ve mobil uyumlu tanıtım sitesi.</p><div class="ao-web-service-tags"><span>🎨 Modern Tasarım</span><span>📈 SEO Uyumlu</span><span>⚡ Hız & Performans</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('teklif') ?>">Detay al →</a><small>🛡 Güvenli ve Ölçeklenebilir</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">🛒</div>
        <div class="ao-web-service-copy"><h3>E-Ticaret</h3><p>Ürünlerinizi, ödeme adımlarını ve kampanyalarınızı satışa hazır bir yapıyla sunun.</p><div class="ao-web-service-tags"><span>💼 Satışa Hazır</span><span>🔒 Güvenli Ödeme</span><span>🎧 Destekli Teslim</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('marketplace') ?>">Marketplace →</a><small>🔐 7/24 Kesintisiz Hizmet</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">💻</div>
        <div class="ao-web-service-copy"><h3>Özel Yazılım</h3><p>İş akışınıza özel panel, portal, entegrasyon veya başvuru sistemleri geliştirin.</p><div class="ao-web-service-tags"><span>📁 Planlı Teslim</span><span>🧩 Esnek Çözümler</span><span>🛡 Sürekli Destek</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('teklif') ?>">Detay al →</a><small>🛡 Kaliteli ve Sürdürülebilir</small></div>
      </article>
  </div>
  <div class="ao-content-cta"><h2>Web projenizi birlikte tasarlayalım</h2><p>İhtiyacınızı seçin; tasarım, içerik, yayın ve destek sürecini adım adım başlatalım.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('teklif') ?>">Site Tasarlat</a><a class="ao-content-btn secondary" href="<?= url('urun-grubu/web-tasarim') ?>">Paketleri Gör</a></div></div>
</div></section>
