<?php
$aoMobileBuilderTemplates = [
    'business' => ['label'=>'Kurumsal Uygulama','design'=>'soft-tech','tone'=>'Sakin teknik','icon'=>'KB','app'=>'Ahost Business','title'=>'Kurumsal','subtitle'=>'Hizmetler, duyurular ve hızlı iletişim','cards'=>[['Ana Sayfa','Kurumsal vitrin ve duyurular'],['Hizmetler','Hizmet kartları ve teklif akışı'],['İletişim','Harita, telefon ve form']], 'nav'=>['Ev','Hizmet','Mesaj','Profil'], 'flow'=>['Tanıtım','Teklif','Destek','Panel']],
    'realestate' => ['label'=>'Emlak Uygulaması','design'=>'forest-luxe','tone'=>'Doğal lüks','icon'=>'EM','app'=>'Ahost Realty','title'=>'Emlak','subtitle'=>'İlan, danışman ve randevu akışı','cards'=>[['Vitrin','Öne çıkan ilanlar'],['İlanlar','Filtreli portföy listesi'],['Randevu','Danışmanla görüşme']], 'nav'=>['Ev','İlan','Harita','Profil'], 'flow'=>['İlan','Filtre','Randevu','Danışman']],
    'restaurant' => ['label'=>'Restoran Uygulaması','design'=>'warm-bistro','tone'=>'Sıcak premium','icon'=>'RS','app'=>'Ahost Bistro','title'=>'Restoran','subtitle'=>'Menü, rezervasyon ve sipariş','cards'=>[['Menü','Kategori ve ürün listesi'],['Rezervasyon','Masa ve saat seçimi'],['Sipariş','Paket servis akışı']], 'nav'=>['Ev','Menü','Sepet','Profil'], 'flow'=>['Menü','Rezervasyon','Sipariş','Teslimat'], 'payments'=>['Kapıda','Online','Havale','Kupon']],
    'radio' => ['label'=>'Radyo Uygulaması','design'=>'neon-media','tone'=>'Neon medya','icon'=>'RD','app'=>'Ahost Radio','title'=>'Canlı Yayın','subtitle'=>'Program akışı, istek hattı ve sosyal medya','cards'=>[['Canlı Dinle','Yayın oynatıcı ve kapak'],['Program','Günlük yayın akışı'],['İstek Hattı','WhatsApp ve sosyal bağlantılar']], 'nav'=>['Ev','Yayın','Program','İstek'], 'flow'=>['Player','Program','DJ','İstek']],
    'ecommerce' => ['label'=>'E-Ticaret Uygulaması','design'=>'clean-commerce','tone'=>'Temiz satış','icon'=>'ET','app'=>'Ahost Shop','title'=>'Mağaza','subtitle'=>'Ürün, sepet ve ödeme akışı','cards'=>[['Ürünler','Kategori ve kampanyalar'],['Sepet','Alışveriş özeti'],['Ödeme','Güvenli ödeme adımı']], 'nav'=>['Ev','Ürün','Sepet','Profil'], 'flow'=>['Ürün','Sepet','Ödeme','Müşteri'], 'payments'=>['Kredi Kartı','Havale/EFT','PayTR','Sanal POS']],
    'news' => ['label'=>'Haber Uygulaması','design'=>'paper-news','tone'=>'Editoryal haber','icon'=>'HB','app'=>'Ahost News','title'=>'Haber','subtitle'=>'Manşet, kategori ve son dakika','cards'=>[['Manşet','Öne çıkan haberler'],['Kategoriler','Gündem ve spor akışı'],['Bildirim','Son dakika duyuruları']], 'nav'=>['Ev','Ara','Bildirim','Profil'], 'flow'=>['Manşet','Kategori','Yazar','Reklam']],
    'education' => ['label'=>'Eğitim Uygulaması','design'=>'academy-pastel','tone'=>'Pastel akademi','icon'=>'EG','app'=>'Ahost Academy','title'=>'Eğitim','subtitle'=>'Ders, sınav ve bildirim akışı','cards'=>[['Dersler','Video ve içerik listesi'],['Sınavlar','Quiz ve ölçme akışı'],['Duyuru','Öğrenci bildirimleri']], 'nav'=>['Ev','Ders','Takvim','Profil'], 'flow'=>['Ders','Sınav','Sertifika','Panel']],
    'blank' => ['label'=>'Boş Uygulama','design'=>'neutral-studio','tone'=>'Nötr stüdyo','icon'=>'BO','app'=>'Yeni Uygulama','title'=>'Boş Taslak','subtitle'=>'Kendi ekranlarınızı sıfırdan kurun','cards'=>[['Ekran 1','İlk özel ekran'],['Ekran 2','Liste veya form alanı'],['Ekran 3','CTA ve içerik alanı']], 'nav'=>['Ev','Liste','Form','Profil'], 'flow'=>['Ekran','Form','Bildirim','Panel']],
];
$aoMobileBuilderColors = [
    'blue'=>'#3B82F6',
    'green'=>'#22C55E',
    'purple'=>'#8B5CF6',
    'orange'=>'#F97316',
    'red'=>'#EF4444',
    'pink'=>'#E91E63',
    'custom'=>'#3B82F6',
];
$aoMobileBuilderAddons = [];
try {
    $q = db()->query("SELECT a.addon_key,a.name,a.description,a.price,a.currency
        FROM product_checkout_addons a
        JOIN products p ON p.id=a.product_id
        WHERE p.slug IN ('mobilebuilder-apk-output','mobilebuilder-aab-output','mobilebuilder-source-code')
          AND a.is_active=1
        ORDER BY a.sort_order,a.id");
    $seenMobileBuilderAddons = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) ?: [] as $addonRow) {
        $addonKey = (string)($addonRow['addon_key'] ?? '');
        if ($addonKey === '' || isset($seenMobileBuilderAddons[$addonKey])) continue;
        $seenMobileBuilderAddons[$addonKey] = true;
        $aoMobileBuilderAddons[] = $addonRow;
    }
} catch (Throwable $e) {
    $aoMobileBuilderAddons = [];
}
?>
<section class="builder-public-page">
    <div class="builder-shell">
        <div class="builder-head">
            <span class="builder-badge">MobileBuilder</span>
            <h1>Mobil uygulama oluştur</h1>
            <p>Ziyaretçi olarak şablon seçip mobil uygulama tasarımını önizleyebilirsiniz. APK/AAB ve kaynak kod çıktısı için kayıt + paket gerekir.</p>
        </div>
        
        <div class="template-cards-grid">
            <?php foreach ($aoMobileBuilderTemplates as $key => $item): ?>
            <a href="<?= url('mobilebuilder/preview-public?template=' . $key) ?>" class="template-card" data-design-card="<?= e($item['design'] ?? 'soft-tech') ?>">
                <span><?= e($item['icon'] ?? mb_substr((string)($item['label'] ?? 'MB'), 0, 2)) ?></span>
                <h3><?= e($item['label'] ?? '') ?></h3>
                <p><?= e(($item['tone'] ?? '') . (($item['tone'] ?? '') !== '' ? ' / ' : '') . ($item['title'] ?? '')) ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <form class="demo-form ao-builder-ai-public" method="get" action="<?= url('mobilebuilder/preview-public') ?>">
            <input type="hidden" name="ai_builder" value="1">
            <div class="demo-form-head">
                <span>AI</span>
                <div>
                    <h2>Yazarak mobil uygulama oluştur</h2>
                    <p>Bir kez ücretsiz deneyin. Devamında AI ile uygulama tasarlamak için paket seçebilir ya da AI olmadan şablonla devam edebilirsiniz.</p>
                </div>
            </div>
            <div class="demo-field-grid compact">
                <label for="ai_mobile_template">Başlangıç Şablonu
                    <select name="template" id="ai_mobile_template" data-mobilebuilder-template-select>
                        <?php foreach ($aoMobileBuilderTemplates as $key => $item): ?>
                            <option value="<?= e($key) ?>"><?= e($item['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="ai_appname">Uygulama Adı
                    <input type="text" id="ai_appname" name="appname" placeholder="Örn: Radyo Ahost" maxlength="50">
                </label>
                <label for="ai_mobile_color">Ana Renk
                    <div class="builder-color-control">
                    <select id="ai_mobile_color" name="color" data-mobilebuilder-color-select>
                        <option value="blue">Mavi (#3B82F6)</option>
                        <option value="green">Yeşil (#22C55E)</option>
                        <option value="purple">Mor (#8B5CF6)</option>
                        <option value="orange">Turuncu (#F97316)</option>
                        <option value="red">Kırmızı (#EF4444)</option>
                        <option value="pink">Pembe (#E91E63)</option>
                        <option value="custom">Özel renk</option>
                    </select>
                    <input type="color" name="custom_color" value="#3B82F6" aria-label="Özel renk seç" data-mobilebuilder-color-picker>
                    <input type="text" value="#3B82F6" maxlength="7" inputmode="text" aria-label="Renk kodu" data-mobilebuilder-color-code>
                    </div>
                </label>
            </div>
            <label for="ai_mobile_prompt">AI'ye ne yaptırmak istiyorsunuz?
                <textarea id="ai_mobile_prompt" name="ai_prompt" rows="4" required placeholder="Örn: Radyo uygulamam için canlı yayın, program akışı, WhatsApp istek hattı, sosyal medya ve bildirim ekranları olan modern bir mobil uygulama tasarla."></textarea>
            </label>
            <button type="submit" class="site-btn">AI ile Önizleme Oluştur</button>
        </form>
        
        <div class="demo-form-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;">
        <form class="demo-form" method="get" action="<?= url('mobilebuilder/preview-public') ?>">
            <h2>Şablon Seçin</h2>
            <label for="template">Uygulama Şablonu</label>
            <select name="template" id="template" data-mobilebuilder-template-select>
                <?php foreach ($aoMobileBuilderTemplates as $key => $item): ?>
                    <option value="<?= e($key) ?>"><?= e($item['label']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="appname">Uygulama Adı</label>
            <input type="text" id="appname" name="appname" placeholder="Örn: Ahost Cloud" maxlength="50">
            
            <label for="color">Ana Renk</label>
            <div class="builder-color-control">
            <select id="color" name="color" data-mobilebuilder-color-select>
                <option value="blue">Mavi (#3B82F6)</option>
                <option value="green">Yeşil (#22C55E)</option>
                <option value="purple">Mor (#8B5CF6)</option>
                <option value="orange">Turuncu (#F97316)</option>
                <option value="red">Kırmızı (#EF4444)</option>
                <option value="pink">Pembe (#E91E63)</option>
                <option value="custom">Özel renk</option>
            </select>
            <input type="color" name="custom_color" value="#3B82F6" aria-label="Özel renk seç" data-mobilebuilder-color-picker>
            <input type="text" value="#3B82F6" maxlength="7" inputmode="text" aria-label="Renk kodu" data-mobilebuilder-color-code>
            </div>
            
            <label for="mobile_menu">Alt Menü</label>
            <input type="text" id="mobile_menu" name="menu" placeholder="Örn: Ev, Yayın, Program, İstek" data-mobile-preview-field="menu">
            
            <label for="mobile_title">Ön Sayfa Başlığı</label>
            <input type="text" id="mobile_title" name="title" placeholder="Telefon içindeki ana başlık" maxlength="70" data-mobile-preview-field="title">
            
            <label for="mobile_subtitle">Ön Sayfa Açıklaması</label>
            <input type="text" id="mobile_subtitle" name="subtitle" placeholder="Kısa açıklama metni" maxlength="130" data-mobile-preview-field="subtitle">

            <label for="mobile_slider">Slider / Öne Çıkanlar</label>
            <input type="text" id="mobile_slider" name="slider" placeholder="Örn: Canlı Yayın, Program, İstek Hattı" data-mobile-preview-field="slider">

            <label for="mobile_cards">Ekran Kartları</label>
            <input type="text" id="mobile_cards" name="cards" placeholder="Örn: Canlı Dinle, Program, WhatsApp" data-mobile-preview-field="cards">

            <label for="mobile_live_person" data-mobile-template-scope="radio">DJ / Ekip / İçerik Sahibi</label>
            <input type="text" id="mobile_live_person" name="live_person" placeholder="Örn: DJ Mert yayında" maxlength="80" data-mobile-preview-field="person" data-mobile-template-scope="radio">

            <label for="mobile_live_time" data-mobile-template-scope="radio service commerce">Yayın / Servis Saati</label>
            <input type="text" id="mobile_live_time" name="live_time" placeholder="Örn: 20:00 - 24:00" maxlength="80" data-mobile-preview-field="time" data-mobile-template-scope="radio service commerce">

            <label for="mobile_player_title" data-mobile-template-scope="radio">Player / Aksiyon Başlığı</label>
            <input type="text" id="mobile_player_title" name="player_title" placeholder="Örn: Ahost Radio Canlı Yayın" maxlength="80" data-mobile-preview-field="player" data-mobile-template-scope="radio">

            <label for="mobile_campaign">Duyuru / Kampanya</label>
            <input type="text" id="mobile_campaign" name="campaign" placeholder="Örn: Yeni program yayında" maxlength="90" data-mobile-preview-field="campaign">

            <label for="mobile_social">Sosyal / İletişim</label>
            <input type="text" id="mobile_social" name="social" placeholder="Örn: WhatsApp, Instagram, YouTube" maxlength="90" data-mobile-preview-field="social">

            <label for="mobile_flow">Süreç / Panel Akışı</label>
            <input type="text" id="mobile_flow" name="flow" placeholder="Örn: Ürün, Sepet, Ödeme, Müşteri Paneli" data-mobile-preview-field="flow">

            <label for="mobile_payments" data-mobile-template-scope="commerce">Ödeme Yöntemleri</label>
            <input type="text" id="mobile_payments" name="payments" placeholder="Örn: Kart, Havale, PayTR, POS" data-mobile-preview-field="payments" data-mobile-template-scope="commerce">

            <?php if ($aoMobileBuilderAddons): ?>
            <div class="builder-module-picker" data-mobile-builder-module-picker>
                <strong>Ek Modüller</strong>
                <div class="builder-module-toolbar">
                    <select data-mobile-builder-addon-select aria-label="Ek modül seç">
                        <option value="">Modül seçin</option>
                <?php foreach ($aoMobileBuilderAddons as $addon): ?>
                        <option value="<?= e($addon['addon_key'] ?? '') ?>">
                            <?= e($addon['name'] ?? '') ?><?php if ((float)($addon['price'] ?? 0) > 0): ?> (+<?= e(strtoupper((string)($addon['currency'] ?? 'TRY'))) ?> <?= number_format((float)$addon['price'], 2, ',', '.') ?>)<?php endif; ?>
                        </option>
                <?php endforeach; ?>
                    </select>
                    <button type="button" class="site-btn secondary" data-mobile-builder-addon-add>Modül Ekle</button>
                </div>
                <div class="builder-selected-modules" data-mobile-builder-selected-modules aria-live="polite"></div>
            </div>
            <?php endif; ?>

            <label class="builder-check-pill" data-mobile-template-scope="radio"><input type="checkbox" data-mobile-preview-toggle="player"> <span>Player</span></label>
            <label class="builder-check-pill"><input type="checkbox" checked data-mobile-preview-toggle="whatsapp"> <span>WhatsApp</span></label>
            <label class="builder-check-pill"><input type="checkbox" checked data-mobile-preview-toggle="search"> <span>Arama</span></label>
            
            <button type="submit" class="site-btn">Önizleme Oluştur</button>
        </form>
        
        <div class="mobile-preview-container">
            <div class="mobile-preview-frame" data-mobilebuilder-demo-preview data-design="<?= e($aoMobileBuilderTemplates['business']['design'] ?? 'soft-tech') ?>">
                <div class="mobile-screen">
                    <div class="mobile-header">
                        <strong data-mobile-preview-app>Ahost Business</strong>
                        <small data-mobile-preview-label>Kurumsal Uygulama</small>
                        <div class="mobile-header-actions">
                            <button type="button" aria-label="Ara" data-mobile-preview-search><span>⌕</span></button>
                            <button type="button" aria-label="WhatsApp" data-mobile-preview-whatsapp><span>☎</span></button>
                        </div>
                    </div>
                    <div class="mobile-content">
                        <div class="mobile-card">
                            <h4 data-mobile-preview-title>Kurumsal</h4>
                            <p data-mobile-preview-subtitle>Hizmetler, duyurular ve hızlı iletişim</p>
                        </div>
                        <div class="mobile-live-strip">
                            <span data-mobile-preview-slider="0">Duyurular</span>
                            <span data-mobile-preview-slider="1">Kampanya</span>
                        </div>
                        <div class="mobile-live-meta">
                            <div><small data-mobile-preview-person-label>Öne Çıkan</small><b data-mobile-preview-person>Profesyonel ekip</b></div>
                            <div><small>Yayın / Servis Saati</small><b data-mobile-preview-time>Hafta içi 09:00 - 18:00</b></div>
                            <div><small>Duyuru / Kampanya</small><b data-mobile-preview-campaign>Yeni fırsatlar yayında</b></div>
                            <div><small>Sosyal / İletişim</small><b data-mobile-preview-social>WhatsApp, Instagram, YouTube</b></div>
                        </div>
                        <div class="mobile-player" data-mobile-preview-player hidden>
                            <i></i>
                            <div><b data-mobile-preview-player-title>Ahost Radio Canlı Yayın</b><small>Mini player ve hızlı aksiyon</small></div>
                        </div>
                        <div class="mobile-flow">
                            <span data-mobile-preview-flow="0">Tanıtım</span>
                            <span data-mobile-preview-flow="1">Teklif</span>
                            <span data-mobile-preview-flow="2">Destek</span>
                            <span data-mobile-preview-flow="3">Panel</span>
                        </div>
                        <div class="mobile-payments">
                            <span data-mobile-preview-payment="0">Kart</span>
                            <span data-mobile-preview-payment="1">Havale</span>
                            <span data-mobile-preview-payment="2">PayTR</span>
                            <span data-mobile-preview-payment="3">POS</span>
                        </div>
                        <div class="mobile-card">
                            <h4 data-mobile-preview-card-title="0">Ana Sayfa</h4>
                            <p data-mobile-preview-card-text="0">Sürükle-bırak bloklar ile hızlı tasarım</p>
                        </div>
                        <div class="mobile-card">
                            <h4 data-mobile-preview-card-title="1">Ürünler</h4>
                            <p data-mobile-preview-card-text="1">E-ticaret ve katalog yönetimi</p>
                        </div>
                        <div class="mobile-card">
                            <h4 data-mobile-preview-card-title="2">Sepet</h4>
                            <p data-mobile-preview-card-text="2">Alışveriş ve ödeme akışı</p>
                        </div>
                    </div>
                    <div class="mobile-bottom-nav">
                        <span data-mobile-preview-nav="0">Ev</span>
                        <span data-mobile-preview-nav="1">Hizmet</span>
                        <span data-mobile-preview-nav="2">Mesaj</span>
                        <span data-mobile-preview-nav="3">Profil</span>
                    </div>
                </div>
            </div>
            
            <div class="builder-actions" style="flex-direction: column; align-items: center; margin-top: 24px;">
                <h3 style="color: #fff; margin-bottom: 16px;">Önizlemede Ne Yapabilirsiniz?</h3>
                <div style="text-align: left; color: #94a3b8; line-height: 1.8;">
                    <p>✓ Şablon seçerek mobil uygulama tasarımını önizle</p>
                    <p>✓ Menü, bottom bar ve CTA tasarımlarını incele</p>
                    <p>✓ Renk ve tema değişikliklerini gör</p>
                    <p>✓ PWA ve Android görünümlerini test et</p>
                    <br>
                    <p style="color: #fbbf24;">⚠ APK/AAB çıktısı için kayıt + paket gerekir</p>
                </div>
                <div style="margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
                    <?php foreach (['apk'=>'APK Paketi','aab'=>'AAB Paketi','source'=>'Kaynak Kod'] as $formatKey => $formatLabel): ?>
                    <form method="post" action="<?= url('builder/package-checkout') ?>" data-mobile-builder-package-form data-output-format="<?= e($formatKey) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="builder_type" value="mobilebuilder">
                        <input type="hidden" name="output_format" value="<?= e($formatKey) ?>">
                        <button class="site-btn secondary" type="submit"><?= e($formatLabel) ?></button>
                    </form>
                    <?php endforeach; ?>
                    <a class="site-btn ghost" href="<?= url('mobil-uygulama') ?>">Mobil Hizmetleri Gör</a>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>
<script>
(function(){
  var templates = <?= json_encode($aoMobileBuilderTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var colors = <?= json_encode($aoMobileBuilderColors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var preview = document.querySelector('[data-mobilebuilder-demo-preview]');
  if (!preview) return;

  function text(selector, value) {
    var node = preview.querySelector(selector);
    if (node) node.textContent = value || '';
  }

  function normalizeHex(value) {
    value = String(value || '').trim();
    if (value.charAt(0) !== '#') value = '#' + value;
    return /^#[0-9a-f]{6}$/i.test(value) ? value.toUpperCase() : '';
  }

  function tint(hex, amount) {
    hex = normalizeHex(hex) || '#3B82F6';
    var num = parseInt(hex.slice(1), 16);
    var r = Math.min(255, Math.max(0, (num >> 16) + amount));
    var g = Math.min(255, Math.max(0, ((num >> 8) & 255) + amount));
    var b = Math.min(255, Math.max(0, (num & 255) + amount));
    return '#' + [r, g, b].map(function(v){ return v.toString(16).padStart(2, '0'); }).join('').toUpperCase();
  }

  function selectedTemplate() {
    var select = document.querySelector('#template') || document.querySelector('[data-mobilebuilder-template-select]');
    return select ? (select.value || 'business') : 'business';
  }

  function overrideValue(name) {
    var field = document.querySelector('[data-mobile-preview-field="' + name + '"]');
    if (field && (field.disabled || field.hidden || field.closest('[hidden]'))) return '';
    return field ? field.value.trim() : '';
  }

  function menuLabels(fallback) {
    var raw = overrideValue('menu');
    if (!raw) return fallback || [];
    return raw.split(',').map(function(item){ return item.trim(); }).filter(Boolean).slice(0, 4);
  }

  function listValue(name, fallback, limit) {
    var raw = overrideValue(name);
    var values = raw ? raw.split(',').map(function(item){ return item.trim(); }).filter(Boolean) : (fallback || []);
    return values.slice(0, limit || 3);
  }

  function templateKind(value) {
    if (value === 'radio') return 'radio';
    if (value === 'ecommerce' || value === 'restaurant') return 'commerce';
    if (value === 'blank') return 'blank';
    return 'service';
  }

  function scopeAllowed(scope, kind) {
    if (!scope) return true;
    return scope.split(/\s+/).filter(Boolean).indexOf(kind) !== -1;
  }

  function applyTemplateScopes(value) {
    var kind = templateKind(value);
    document.querySelectorAll('[data-mobile-template-scope]').forEach(function(node){
      var show = scopeAllowed(node.dataset.mobileTemplateScope || '', kind);
      node.hidden = !show;
      if ('disabled' in node) node.disabled = !show;
      node.querySelectorAll('input,select,textarea,button').forEach(function(control){
        if (control.matches('[data-mobile-preview-toggle]')) return;
        control.disabled = !show;
      });
    });
  }

  function selectedColor() {
    var select = document.querySelector('#color') || document.querySelector('[data-mobilebuilder-color-select]');
    var picker = select ? select.closest('.builder-color-control').querySelector('[data-mobilebuilder-color-picker]') : document.querySelector('[data-mobilebuilder-color-picker]');
    var key = select ? select.value : 'blue';
    return key === 'custom' ? (normalizeHex(picker && picker.value) || '#3B82F6') : (colors[key] || '#3B82F6');
  }

  function syncTemplateSelects(value) {
    document.querySelectorAll('[data-mobilebuilder-template-select]').forEach(function(select){
      if (select.value !== value && templates[value]) select.value = value;
    });
  }

  function syncColors(hex, source) {
    document.querySelectorAll('[data-mobilebuilder-color-picker]').forEach(function(input){ if (input !== source) input.value = hex; });
    document.querySelectorAll('[data-mobilebuilder-color-code]').forEach(function(input){ if (input !== source) input.value = hex; });
  }

  function applyColor(hex) {
    hex = normalizeHex(hex) || '#3B82F6';
    preview.style.setProperty('--builder-accent', hex);
    preview.style.setProperty('--builder-accent-soft', tint(hex, 34));
    preview.style.setProperty('--builder-accent-wash', tint(hex, 178));
    preview.style.setProperty('--builder-accent-border', tint(hex, 136));
    syncColors(hex, null);
  }

  function appName() {
    var field = document.querySelector('#appname');
    var aiField = document.querySelector('#ai_appname');
    return (field && field.value.trim()) || (aiField && aiField.value.trim()) || '';
  }

  function render(value) {
    var item = templates[value] || templates.business;
    if (!item) return;
    preview.dataset.design = item.design || 'soft-tech';
    syncTemplateSelects(value);
    applyTemplateScopes(value);
    text('[data-mobile-preview-app]', appName() || item.app);
    text('[data-mobile-preview-label]', item.label);
    text('[data-mobile-preview-title]', overrideValue('title') || item.title);
    text('[data-mobile-preview-subtitle]', overrideValue('subtitle') || item.subtitle);
    listValue('slider', ['Duyurular', 'Kampanya'], 2).forEach(function(label, index){ text('[data-mobile-preview-slider="' + index + '"]', label); });
    text('[data-mobile-preview-person]', overrideValue('person') || (value === 'radio' ? 'Canlı yayın ekibi' : 'Profesyonel ekip'));
    text('[data-mobile-preview-time]', overrideValue('time') || (value === 'radio' ? '20:00 - 24:00' : 'Hafta içi 09:00 - 18:00'));
    text('[data-mobile-preview-campaign]', overrideValue('campaign') || (value === 'radio' ? 'Yeni program yayında' : 'Yeni fırsatlar yayında'));
    text('[data-mobile-preview-social]', overrideValue('social') || (value === 'radio' ? 'WhatsApp, Instagram, YouTube' : 'Form, WhatsApp, Telefon'));
    listValue('flow', item.flow || [], 4).forEach(function(label, index){ text('[data-mobile-preview-flow="' + index + '"]', label); });
    listValue('payments', item.payments || [], 4).forEach(function(label, index){ text('[data-mobile-preview-payment="' + index + '"]', label); });
    var kind = templateKind(value);
    var flow = preview.querySelector('.mobile-flow');
    var payments = preview.querySelector('.mobile-payments');
    if (flow) flow.hidden = !(item.flow && item.flow.length);
    if (payments) payments.hidden = !(item.payments && item.payments.length);
    text('[data-mobile-preview-player-title]', overrideValue('player') || (value === 'radio' ? 'Ahost Radio Canlı Yayın' : 'Hızlı aksiyon'));
    var player = preview.querySelector('[data-mobile-preview-player]');
    var playerToggle = document.querySelector('[data-mobile-preview-toggle="player"]');
    if (player && playerToggle && value === 'radio' && !playerToggle.checked && !overrideValue('player')) playerToggle.checked = true;
    if (kind !== 'radio') {
      if (playerToggle) playerToggle.checked = false;
      if (player) player.hidden = true;
    } else if (player && playerToggle) {
      player.hidden = !playerToggle.checked;
    }
    var cardTitles = listValue('cards', [], 3);
    (item.cards || []).slice(0, 3).forEach(function(card, index){
      text('[data-mobile-preview-card-title="' + index + '"]', cardTitles[index] || card[0]);
      text('[data-mobile-preview-card-text="' + index + '"]', card[1]);
    });
    menuLabels(item.nav || []).forEach(function(label, index){ text('[data-mobile-preview-nav="' + index + '"]', label); });
    applyColor(selectedColor());
  }

  function selectedAddonValues() {
    return Array.prototype.slice.call(document.querySelectorAll('[data-mobile-builder-selected-modules] [data-mobile-builder-addon-value]'))
      .map(function(input){ return input.value; })
      .filter(Boolean);
  }

  function addSelectedAddon() {
    var select = document.querySelector('[data-mobile-builder-addon-select]');
    var list = document.querySelector('[data-mobile-builder-selected-modules]');
    if (!select || !list || !select.value) return;
    if (selectedAddonValues().indexOf(select.value) !== -1) {
      select.value = '';
      return;
    }
    var option = select.options[select.selectedIndex];
    var item = document.createElement('span');
    item.className = 'builder-module-chip';
    item.innerHTML = '<input type="hidden" data-mobile-builder-addon-value value=""> <b></b><button type="button" aria-label="Modülü kaldır" data-mobile-builder-addon-remove>×</button>';
    item.querySelector('input').value = select.value;
    item.querySelector('b').textContent = option ? option.textContent.trim() : select.value;
    list.appendChild(item);
    select.value = '';
  }

  document.addEventListener('change', function(event){
    var templateSelect = event.target.closest('[data-mobilebuilder-template-select]');
    if (templateSelect) render(templateSelect.value);
    var colorSelect = event.target.closest('[data-mobilebuilder-color-select]');
    if (colorSelect) {
      var hex = colorSelect.value === 'custom'
        ? normalizeHex(colorSelect.closest('.builder-color-control').querySelector('[data-mobilebuilder-color-picker]').value)
        : colors[colorSelect.value];
      applyColor(hex || '#3B82F6');
    }
  });

  document.addEventListener('click', function(event){
    var addAddon = event.target.closest('[data-mobile-builder-addon-add]');
    if (addAddon) {
      addSelectedAddon();
      return;
    }
    var removeAddon = event.target.closest('[data-mobile-builder-addon-remove]');
    if (removeAddon) {
      var chip = removeAddon.closest('.builder-module-chip');
      if (chip) chip.remove();
    }
  });

  document.addEventListener('input', function(event){
    if (event.target && (event.target.id === 'appname' || event.target.id === 'ai_appname')) {
      var item = templates[selectedTemplate()] || templates.business;
      text('[data-mobile-preview-app]', event.target.value.trim() || (item ? item.app : 'Uygulama'));
    }
    if (event.target && event.target.matches('[data-mobile-preview-field]')) {
      render(selectedTemplate());
    }
    if (event.target && event.target.matches('[data-mobilebuilder-color-picker],[data-mobilebuilder-color-code]')) {
      var hex = normalizeHex(event.target.value);
      if (!hex) return;
      var wrap = event.target.closest('.builder-color-control');
      if (wrap) {
        var select = wrap.querySelector('[data-mobilebuilder-color-select]');
        if (select) select.value = 'custom';
      }
      applyColor(hex);
    }
  });

  document.addEventListener('change', function(event){
    var toggle = event.target.closest('[data-mobile-preview-toggle]');
    if (!toggle) return;
    if (toggle.dataset.mobilePreviewToggle === 'player') {
      var player = preview.querySelector('[data-mobile-preview-player]');
      if (player) player.hidden = !toggle.checked;
    }
    if (toggle.dataset.mobilePreviewToggle === 'whatsapp') {
      var whatsapp = preview.querySelector('[data-mobile-preview-whatsapp]');
      if (whatsapp) whatsapp.hidden = !toggle.checked;
    }
    if (toggle.dataset.mobilePreviewToggle === 'search') {
      var search = preview.querySelector('[data-mobile-preview-search]');
      if (search) search.hidden = !toggle.checked;
    }
  });

  document.addEventListener('submit', function(event){
    var form = event.target.closest('[data-mobile-builder-package-form]');
    if (!form) return;
    form.querySelectorAll('[data-builder-generated]').forEach(function(input){ input.remove(); });
    var fields = {
      template: selectedTemplate(),
      appname: appName(),
      color: (document.querySelector('#color') || {}).value || 'blue',
      custom_color: selectedColor(),
      menu: overrideValue('menu'),
      title: overrideValue('title'),
      subtitle: overrideValue('subtitle'),
      slider: overrideValue('slider'),
      cards: overrideValue('cards'),
      live_person: overrideValue('person'),
      live_time: overrideValue('time'),
      player_title: overrideValue('player'),
      campaign: overrideValue('campaign'),
      social: overrideValue('social'),
      flow: overrideValue('flow'),
      payments: overrideValue('payments')
    };
    Object.keys(fields).forEach(function(name){
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = fields[name] || '';
      input.setAttribute('data-builder-generated', '1');
      form.appendChild(input);
    });
    selectedAddonValues().forEach(function(value){
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'builder_addons[]';
      input.value = value;
      input.setAttribute('data-builder-generated', '1');
      form.appendChild(input);
    });
  });

  render(selectedTemplate());
})();
</script>
