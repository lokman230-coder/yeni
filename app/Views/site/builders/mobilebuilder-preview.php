<?php
$template = $template ?? ($_GET['template'] ?? 'business');
$apps = [
  'business' => ['icon' => 'KB', 'design' => 'soft-tech', 'name' => 'Kurumsal Uygulama', 'desc' => 'Hakkımızda, hizmetler, bildirim ve iletişim akışı.', 'accent' => 'Sakin teknik'],
  'radio' => ['icon' => 'RD', 'design' => 'neon-media', 'name' => 'Radyo Uygulaması', 'desc' => 'Canlı yayın, program rehberi, podcast ve WhatsApp.', 'accent' => 'Neon medya'],
  'shop' => ['icon' => 'MG', 'design' => 'clean-commerce', 'name' => 'Mağaza Uygulaması', 'desc' => 'Ürün kartları, sepet, ödeme ve müşteri hesabı.', 'accent' => 'Temiz satış'],
  'ecommerce' => ['icon' => 'ET', 'design' => 'clean-commerce', 'name' => 'E-Ticaret Uygulaması', 'desc' => 'Ürünler, kampanyalar, sepet ve müşteri hesabı akışı.', 'accent' => 'Temiz satış'],
  'restaurant' => ['icon' => 'RS', 'design' => 'warm-bistro', 'name' => 'Restoran Uygulaması', 'desc' => 'Menü, rezervasyon, paket servis ve konum ekranları.', 'accent' => 'Sıcak premium'],
  'realestate' => ['icon' => 'EM', 'design' => 'forest-luxe', 'name' => 'Emlak Uygulaması', 'desc' => 'İlan listesi, filtreleme, danışman ve teklif formu.', 'accent' => 'Doğal lüks'],
  'news' => ['icon' => 'HB', 'design' => 'paper-news', 'name' => 'Haber Uygulaması', 'desc' => 'Manşet, kategori, bildirim ve yazar sayfaları.', 'accent' => 'Editoryal haber'],
  'education' => ['icon' => 'EG', 'design' => 'academy-pastel', 'name' => 'Eğitim Uygulaması', 'desc' => 'Dersler, duyurular, eğitmenler ve öğrenci paneli.', 'accent' => 'Pastel akademi'],
  'blank' => ['icon' => 'BO', 'design' => 'neutral-studio', 'name' => 'Boş Uygulama', 'desc' => 'Kendi ekran akışınızı sıfırdan oluşturmak için boş başlangıç.', 'accent' => 'Nötr stüdyo'],
];
$active = $apps[$template] ?? $apps['business'];
$appName = trim((string)($_GET['appname'] ?? ''));
$aiPrompt = trim((string)($_GET['ai_prompt'] ?? ''));
if ($appName !== '') {
  $active['name'] = mb_substr($appName, 0, 50);
}
?>
<section class="ao-public-page builder-public-page ao-mobile-preview-page">
  <div class="ao-public-shell builder-shell">
    <div class="builder-head ao-builder-hero-card">
      <span class="builder-badge">MobileBuilder Önizleme</span>
      <h1>Ziyaretçi olarak mobil uygulama tasarımını deneyin</h1>
      <p>Telefon önizlemesi açıktır. APK, AAB, Android kaynak ZIP veya PWA export için kayıt olup paket satın almanız gerekir.</p>
      <?php if ($aiPrompt !== ''): ?>
        <div class="ao-ai-preview-brief"><b>AI isteği</b><span><?= e(mb_substr($aiPrompt, 0, 180)) ?></span></div>
      <?php endif; ?>
      <div class="builder-actions">
        <a class="site-btn" href="<?= url('mobilebuilder/create-demo') ?>">Uygulama Oluştur</a>
        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="APK çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-apk-output') ?>">APK Paketi</a>
        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="AAB çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-aab-output') ?>">AAB Paketi</a>
        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="mobile" data-package-title="Kaynak kod için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-source-code') ?>">Kaynak Kod Paketi</a>
        <a class="site-btn ghost" href="<?= url('urunler?group=mobilebuilder') ?>">Paketleri Gör</a>
      </div>
    </div>

    <div class="ao-builder-ai-assist">
      <div class="ao-ai-head">
        <div>
          <span class="ao-ai-badge">AI Yardımı</span>
          <h3>Uygulama fikrinizi AI ile ekrana dökün</h3>
          <p>Gemini, ChatGPT/OpenAI, OpenRouter veya Groq seçerek ekran akışı, menü ve özellik taslağını hesabınızdan oluşturabilirsiniz.</p>
        </div>
        <div class="ao-ai-provider-row">
          <span>Gemini</span><span>ChatGPT</span><span>OpenRouter</span><span>Groq</span>
        </div>
      </div>
      <div class="ao-ai-actions">
        <a class="primary" href="<?= url('client/mobile-builder#ai-yardimi') ?>">AI ile Uygulama Oluştur</a>
        <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="APK çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-apk-output') ?>">APK Paketi</a>
        <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="AAB çıktısı için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-aab-output') ?>">AAB Paketi</a>
        <a data-builder-package-alert data-builder-package-kind="mobile" data-package-title="Kaynak kod için paket almalısınız" href="<?= url('cart/add?product=mobilebuilder-source-code') ?>">Kaynak Kod</a>
      </div>
    </div>

    <div class="mobile-preview-layout ao-builder-workspace">
      <aside class="builder-panel ao-builder-side-panel">
        <div class="ao-panel-title-row">
          <span>MB</span>
          <div>
            <small>Uygulama şablonları</small>
            <h3>Uygulama Tipi</h3>
          </div>
        </div>
        <div class="ao-builder-template-list">
          <?php foreach ($apps as $key => $item): ?>
            <a class="ao-template-option <?= $key === $template ? 'active' : '' ?>" href="<?= url('mobilebuilder/preview-public?template=' . $key) ?>">
              <span><?= e($item['icon']) ?></span>
              <b><?= e($item['name']) ?></b>
              <small><?= e($item['accent']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="builder-note ao-builder-note">
          <b>Build kuyruğu</b>
          APK/AAB/PWA çıktısı paketli kullanıcılar için açılır.
        </div>
      </aside>

      <div class="phone-mock ao-phone-mock large" data-design="<?= e($active['design'] ?? 'soft-tech') ?>">
        <div class="radio-status-bar"><span>9:41</span><span>5G</span></div>
        <div class="phone-screen">
          <div class="phone-app-header">
            <span><?= e($active['icon']) ?></span>
            <div>
              <small><?= e($active['accent']) ?></small>
              <h3><?= e($active['name']) ?></h3>
            </div>
          </div>
          <div class="phone-hero">
            <b>Logo + Splash + Ana ekran</b>
            <span><?= e($active['desc']) ?></span>
          </div>
          <div class="phone-list">
            <span>Ana Sayfa</span>
            <span>Bildirimler</span>
            <span>İletişim</span>
          </div>
          <div class="phone-bottom"><b></b><b></b><b></b></div>
        </div>
      </div>
    </div>
  </div>
</section>
