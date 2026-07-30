<?php
$template = $template ?? ($_GET['template'] ?? 'hosting');
$templates = [
  'hosting' => ['icon' => 'HC', 'design' => 'tech-dark', 'name' => 'Hosting Firması', 'tag' => 'Hosting SaaS', 'title' => 'Hosting satış sitenizi SiteBuilder ile oluşturun', 'desc' => 'Domain, hosting, SSL, sunucu ve destek bloklarını tek ekranda düzenleyin.', 'brand' => 'Ahost Cloud'],
  'agency' => ['icon' => 'AJ', 'design' => 'vivid-agency', 'name' => 'Ajans / Kurumsal', 'tag' => 'Kurumsal', 'title' => 'Kurumsal ajans sitenizi dakikalar içinde kurun', 'desc' => 'Hizmetler, portfolyo, referans ve teklif formlarını sürükle-bırak mantığıyla hazırlayın.', 'brand' => 'Ahost Agency'],
  'landing' => ['icon' => 'LP', 'design' => 'electric-launch', 'name' => 'Landing Page', 'tag' => 'Landing', 'title' => 'Yeni ürününüz için hızlı landing page', 'desc' => 'Hero, özellik, fiyatlandırma, SSS ve iletişim bölümlerini paket halinde yönetin.', 'brand' => 'Ahost Launch'],
  'corporate' => ['icon' => 'KR', 'design' => 'editorial-corporate', 'name' => 'Kurumsal Şirket', 'tag' => 'Kurumsal Site', 'title' => 'Markanızı anlatan güçlü bir kurumsal site kurun', 'desc' => 'Hakkımızda, hizmetler, ekip, referans ve iletişim akışını tek düzende hazırlayın.', 'brand' => 'Ahost Corporate'],
  'software' => ['icon' => 'YZ', 'design' => 'indigo-saas', 'name' => 'Yazılım Firması', 'tag' => 'SaaS', 'title' => 'Yazılım ürününüz için net ve dönüşüm odaklı sayfalar', 'desc' => 'Özellikler, demo talebi, fiyatlandırma, entegrasyon ve destek bloklarını yönetin.', 'brand' => 'Ahost Software'],
  'ecommerce' => ['icon' => 'ET', 'design' => 'clean-commerce', 'name' => 'E-Ticaret Ön Site', 'tag' => 'E-Ticaret', 'title' => 'Satışa hazır ürün vitrini ve kampanya sayfaları', 'desc' => 'Kategori, ürün, kampanya, sepet yönlendirme ve müşteri güven alanlarını oluşturun.', 'brand' => 'Ahost Shop'],
  'restaurant' => ['icon' => 'RS', 'design' => 'warm-bistro', 'name' => 'Restoran', 'tag' => 'Restoran', 'title' => 'Menü, rezervasyon ve paket servis akışını tek sitede toplayın', 'desc' => 'Menü, galeri, çalışma saatleri, konum ve hızlı iletişim bloklarını kullanın.', 'brand' => 'Ahost Bistro'],
  'realestate' => ['icon' => 'EM', 'design' => 'forest-luxe', 'name' => 'Emlak', 'tag' => 'Emlak', 'title' => 'İlanlarınızı güven veren modern bir vitrinde yayınlayın', 'desc' => 'İlan kartları, lokasyon, danışman profili ve teklif formu alanlarını hazırlayın.', 'brand' => 'Ahost Realty'],
  'radio' => ['icon' => 'RD', 'design' => 'neon-media', 'name' => 'Radyo / Medya', 'tag' => 'Medya', 'title' => 'Canlı yayın ve program akışını öne çıkaran medya sitesi', 'desc' => 'Canlı dinle, yayın akışı, podcast, sosyal medya ve WhatsApp istek hattı blokları.', 'brand' => 'Ahost Radio'],
  'news' => ['icon' => 'HB', 'design' => 'paper-news', 'name' => 'Haber Portali', 'tag' => 'Haber', 'title' => 'Gündemi hızlı yayınlayan düzenli bir haber portalı', 'desc' => 'Manşet, kategori, yazar, son dakika ve reklam alanlarını birlikte yönetin.', 'brand' => 'Ahost News'],
];
$active = $templates[$template] ?? $templates['hosting'];
$siteName = trim((string)($_GET['sitename'] ?? ''));
$aiPrompt = trim((string)($_GET['ai_prompt'] ?? ''));
if ($siteName !== '') {
  $active['brand'] = mb_substr($siteName, 0, 50);
}
?>
<section class="ao-public-page builder-public-page ao-builder-preview-page ao-sitebuilder-preview-page">
  <div class="ao-public-shell builder-shell">
    <div class="builder-head ao-builder-hero-card">
      <span class="builder-badge">SiteBuilder Önizleme</span>
      <h1>Ziyaretçi olarak site tasarımını deneyin</h1>
      <p>Şablon seçebilir, canlı önizleme görebilir ve tasarım akışını inceleyebilirsiniz. ZIP export ve proje kaydetme için kayıt + paket gerekir.</p>
      <?php if ($aiPrompt !== ''): ?>
        <div class="ao-ai-preview-brief"><b>AI isteği</b><span><?= e(mb_substr($aiPrompt, 0, 180)) ?></span></div>
      <?php endif; ?>
      <div class="builder-actions">
        <a class="site-btn" href="<?= url('sitebuilder/create-demo') ?>">Site Oluştur</a>
        <a class="site-btn secondary" data-builder-package-alert data-builder-package-kind="site" href="<?= url('cart/add?product=sitebuilder-output-package') ?>">ZIP / Kaynak Kod Paketi</a>
        <a class="site-btn ghost" href="<?= url('urunler?group=sitebuilder') ?>">Paketleri Gör</a>
      </div>
    </div>

    <div class="ao-builder-ai-assist">
      <div class="ao-ai-head">
        <div>
          <span class="ao-ai-badge">AI Yardımı</span>
          <h3>Site fikrinizi AI ile taslağa çevirin</h3>
          <p>Gemini, ChatGPT/OpenAI, OpenRouter veya Groq seçerek ilk sayfa yapısını hesabınızdan oluşturabilirsiniz.</p>
        </div>
        <div class="ao-ai-provider-row">
          <span>Gemini</span><span>ChatGPT</span><span>OpenRouter</span><span>Groq</span>
        </div>
      </div>
      <div class="ao-ai-actions">
        <a class="primary" href="<?= url('client/site-builder#ai-yardimi') ?>">AI ile Site Oluştur</a>
        <a data-builder-package-alert data-builder-package-kind="site" href="<?= url('cart/add?product=sitebuilder-output-package') ?>">ZIP / Kaynak Kod Paketi</a>
      </div>
    </div>

    <div class="builder-preview-grid ao-builder-workspace">
      <aside class="builder-panel ao-builder-side-panel">
        <div class="ao-panel-title-row">
          <span>ŞB</span>
          <div>
            <small>Hazır tasarım akışı</small>
            <h3>Şablonlar</h3>
          </div>
        </div>
        <div class="ao-builder-template-list">
          <?php foreach ($templates as $key => $item): ?>
            <a class="ao-template-option <?= $key === $template ? 'active' : '' ?>" href="<?= url('sitebuilder/preview-public?template=' . $key) ?>">
              <span><?= e($item['icon']) ?></span>
              <b><?= e($item['name']) ?></b>
              <small><?= e($item['tag']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="builder-note ao-builder-note">
          <b>Çıktı kilidi</b>
          ZIP alma ve kalıcı proje kaydetme işlemleri paket satın alma sonrası aktif olur.
        </div>
      </aside>

      <div class="site-preview-frame ao-site-preview-frame" data-design="<?= e($active['design'] ?? 'tech-dark') ?>">
        <div class="preview-browser-bar">
          <span></span><span></span><span></span>
          <em>canli-onizleme.ahost</em>
        </div>
        <div class="preview-navbar">
          <strong><?= e($active['brand']) ?></strong>
          <nav>
            <span>Hizmetler</span>
            <span>Fiyatlar</span>
            <span>Referanslar</span>
            <span>İletişim</span>
          </nav>
        </div>
        <div class="preview-hero">
          <div>
            <small><?= e($active['tag']) ?> Şablonu</small>
            <h2><?= e($active['title']) ?></h2>
            <p><?= e($active['desc']) ?></p>
            <div class="preview-actions">
              <button type="button">Site Tasarlat</button>
              <button type="button" class="light">Blokları İncele</button>
            </div>
          </div>
          <div class="preview-card ao-score-card">
            <b>SEO Skoru</b>
            <strong>92</strong>
            <span>Mobil uyumlu</span>
          </div>
        </div>
        <div class="preview-cards">
          <div><span>01</span><b>Hızlı Kurulum</b><small>Hazır bloklar</small></div>
          <div><span>02</span><b>Tema Blokları</b><small>Renk ve font</small></div>
          <div><span>03</span><b>SEO Alanları</b><small>Başlık, açıklama</small></div>
        </div>
      </div>
    </div>
  </div>
</section>
