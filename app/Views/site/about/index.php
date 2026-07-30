<?php
require_once __DIR__ . '/../shared/content-renderer.php';

$company = function_exists('admin_setting') ? admin_setting('company_name', admin_setting('site_name', 'Ahost One')) : 'Ahost One';
$phone = function_exists('admin_setting') ? admin_setting('company_phone', '+90(544) 471 65 41') : '+90(544) 471 65 41';
$email = function_exists('admin_setting') ? admin_setting('company_email', admin_setting('contact_email', 'iletisim@example.com')) : 'iletisim@example.com';
$address = function_exists('admin_setting') ? admin_setting('company_address', admin_setting('contact_address', '')) : '';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Kuruluş</strong><span>•</span><span>2011 yılından bu yana dijital altyapı hizmetleri</span></div>
  <h2>Firmamız ve yaptığımız iş hakkında daha fazlasını öğrenin</h2>
  <p>Kuruluşumuzdan bu yana alan adı, web hosting, reseller hosting, radyo hosting, kiralık sunucu, web tasarım, mobil uygulama ve dijital reklam çözümleri sunuyoruz. Oluşturduğumuz AR-GE yapısıyla altyapımızı sürekli geliştiriyor, verdiğimiz hizmetlerde sürdürülebilir kalite ve takip edilebilir servis yaklaşımı benimsiyoruz.</p>
  <p>Türkiye pazarında bireysel ve kurumsal müşterilere hizmet verirken, farklı ülkelerden müşterilerin de dijital projelerini yayına alma ve yönetme süreçlerine destek oluyoruz.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">Kalite</span>
    <h3>Altyapıya sürekli yatırım</h3>
    <p>Geleceğe dönük hizmet kalitesi için sunucu, donanım, yazılım ve operasyon altyapısına düzenli yatırım yapıyoruz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Performans</span>
    <h3>Güçlü network ve sunucu mimarisi</h3>
    <p>Network altyapısında yüksek kapasiteli bağlantılar, hızlı disk yapısı ve ölçeklenebilir servis mimarisiyle performansı odağa alıyoruz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Uptime</span>
    <h3>Kesintisiz hizmet hedefi</h3>
    <p>Web sunucularında minimum %99.9 erişilebilirlik hedefiyle çalışıyor, izleme ve bakım süreçlerini düzenli olarak takip ediyoruz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Destek</span>
    <h3>7/24 takip edilebilir destek</h3>
    <p>Destek talebi, telefon ve WhatsApp kanallarıyla müşterilerin doğru paketi seçmesine ve hizmetlerini yönetmesine yardımcı oluyoruz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Güvenlik</span>
    <h3>Veri ve hesap güvenliği</h3>
    <p>Bilgi kaybını, yetkisiz erişimi ve izinsiz değişiklikleri önlemek için güvenlik önlemlerini süreçlerin merkezinde tutuyoruz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Değerler</span>
    <h3>Müşteri ihtiyacını doğru anlamak</h3>
    <p>Müşteri beklentilerini zamanında karşılamayı, iletişimi güçlendirmeyi ve uzun vadeli memnuniyet oluşturmayı temel değer kabul ediyoruz.</p>
  </article>
</section>

<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>İletişim</strong><span>•</span><span><?= e($company) ?></span></div>
  <div class="ao-content-grid">
    <div class="ao-page-card"><strong>Telefon</strong><p><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></p></div>
    <div class="ao-page-card"><strong>E-posta</strong><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p></div>
    <div class="ao-page-card"><strong>Adres</strong><p><?= e($address ?: 'Adres bilgisi yönetim panelinden düzenlenebilir.') ?></p></div>
  </div>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Ahost Bilişim hakkında',
  'kicker'=>'Kurumsal',
  'summary'=>'Alan adı, hosting, web tasarım, mobil uygulama ve teknik destek hizmetlerini 2011 yılından bu yana tek çatı altında sunuyoruz.',
  'actions'=>[
    ['label'=>'Hizmetleri İncele','href'=>url('urunler')],
    ['label'=>'İletişime Geç','href'=>url('iletisim'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Hakkımızda']]
]);
