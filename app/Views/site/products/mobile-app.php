<section class="ao-site-content ao-service-landing-page ao-web-design-page"><div class="ao-content-shell">
  <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('mobil-uygulama', ['title'=>'Android ve iOS projeleri için uçtan uca çözüm.']) : ''; ?>
  <?php if($managedHero): ?><?= $managedHero ?><?php else: ?><div class="ao-content-hero"><span class="ao-content-kicker">Mobil Uygulama Hizmetleri</span><h1>Android ve iOS projeleri için uçtan uca çözüm.</h1><p>Kurumsal uygulama, e-ticaret, randevu, radyo, haber, eğitim ve özel mobil uygulama projeleri için tasarım, geliştirme ve yayın desteği.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('urun-grubu/mobil-uygulama') ?>">Uygulama Tasarlat</a><a class="ao-content-btn secondary" href="<?= url('dijital-hizmetler') ?>">Dijital Hizmetler</a></div></div><?php endif; ?>
  <div class="ao-content-grid ao-web-service-grid">
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">🤖</div>
        <div class="ao-web-service-copy"><h3>Android Uygulama</h3><p>APK/AAB hazırlığı, Play Store yayın süreci ve markanıza uygun ekran tasarımı.</p><div class="ao-web-service-tags"><span>📱 Native Performans</span><span>🏪 Play Store Hazır</span><span>🔗 Entegre Yapı</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('teklif') ?>">Başvur →</a><small>🛡 Google Play Standartları</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">🍎</div>
        <div class="ao-web-service-copy"><h3>iOS Uygulama</h3><p>Apple ekosistemi için tasarım, geliştirme ve teslim süreçleri.</p><div class="ao-web-service-tags"><span>🍏 Apple Ekosistemi</span><span>🎯 App Store Hazır</span><span>⚙️ Native Performans</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('teklif') ?>">Detay al →</a><small>🛡 App Store Standartları</small></div>
      </article>
      <article class="ao-content-card ao-service-card ao-web-service-card">
        <div class="ao-content-list-icon" aria-hidden="true">⚙️</div>
        <div class="ao-web-service-copy"><h3>Özel Yazılım</h3><p>API bağlantıları, bildirimler, ödeme adımları ve size özel uygulama özellikleri.</p><div class="ao-web-service-tags"><span>🔌 API Entegrasyonu</span><span>🔔 Bildirim & Ödeme</span><span>🧩 Esnek Mimari</span></div></div>
        <div class="ao-web-service-side"><a class="ao-content-btn secondary" href="<?= url('dijital-hizmetler') ?>">Dijital hizmetler →</a><small>🛡 Sürekli Destek</small></div>
      </article>
  </div>
  <div class="ao-content-cta"><h2>Uygulamanızı birlikte tasarlayalım</h2><p>İhtiyacınız olan platformu, ekranları ve yayın paketini seçerek mobil proje sürecini başlatın.</p><div class="ao-content-actions"><a class="ao-content-btn" href="<?= url('urun-grubu/mobil-uygulama') ?>">Uygulama Tasarlat</a><a class="ao-content-btn secondary" href="<?= url('urun-grubu/android-uygulamalari') ?>">Paketleri Gör</a></div></div>
</div></section>
