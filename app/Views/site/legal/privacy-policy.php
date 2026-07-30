<?php
require_once __DIR__ . '/../shared/content-renderer.php';

$company = function_exists('admin_setting') ? admin_setting('company_name', admin_setting('site_name', 'Ahost Bilişim')) : 'Ahost Bilişim';
$email = function_exists('admin_setting') ? admin_setting('company_email', admin_setting('contact_email', 'iletisim@ahost.web.tr')) : 'iletisim@ahost.web.tr';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Sürüm</strong><span>•</span><span>v2 02/2025 kaynak metnine göre düzenlendi</span></div>
  <h2>Verilerinizi nasıl kullanıyoruz?</h2>
  <p><?= e($company) ?>, müşteri ve ziyaretçi mahremiyetini korumayı temel sorumluluk kabul eder. Bu politika; web sitesini kullanan kişilerden hangi bilgilerin toplandığını, bu bilgilerin hangi amaçlarla işlendiğini ve hangi koşullarda paylaşılabileceğini açıklar.</p>
  <p>Kişisel veriler; sipariş, üyelik, destek, faturalama, domain/hosting işlemleri, güvenlik ve hizmet kalitesini artırma süreçleri için işlenebilir.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">Gizlilik</span>
    <h3>Verilerin korunması</h3>
    <p>Kullanıcıların bize emanet ettiği bilgileri korumayı, yalnızca gerekli amaçlarla işlemeyi ve yetkisiz erişime karşı güvenlik önlemleri almayı taahhüt ederiz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Veri Toplama</span>
    <h3>Formlar ve sipariş süreçleri</h3>
    <p>Ürün ve hizmet siparişleri, üyelik formları, destek talepleri ve iletişim formları üzerinden ad, soyad, e-posta, telefon, adres ve hizmet bilgileri alınabilir.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Analitik</span>
    <h3>Kullanım bilgileri</h3>
    <p>Web sitesinin hangi sayfalarının ziyaret edildiği, hangi alanlardan giriş yapıldığı ve sitenin nasıl kullanıldığı çerezler ve anonim analitik kayıtlarla ölçülebilir.</p>
  </article>
</section>

<section class="ao-content-panel">
  <h2>Veri kullanımı ve paylaşımı</h2>
  <p>Toplanan bilgiler; hizmet sunumu, müşteri iletişimi, teknik destek, faturalama, güvenlik, yasal yükümlülüklerin yerine getirilmesi ve kullanıcı deneyiminin geliştirilmesi amacıyla kullanılır.</p>
  <p>Kullanıcı veri tabanları pazarlama veya toplu posta amacıyla üçüncü taraflara satılmaz. Kişisel bilgiler, yasal zorunluluklar veya hizmetin yürütülmesi için gerekli güvenilir iş ortakları dışında üçüncü taraflarla paylaşılmaz.</p>
</section>

<section class="ao-content-panel">
  <h2>Haklarınız ve iletişim</h2>
  <p>Kişisel verilerinizle ilgili bilgi talep etmek, güncelleme istemek veya gizlilik politikası hakkında soru sormak için <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> adresinden bize ulaşabilirsiniz.</p>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Gizlilik Politikası',
  'kicker'=>'Yasal',
  'summary'=>'Kişisel verilerin hangi amaçlarla toplandığını, nasıl kullanıldığını ve hangi koşullarda paylaşıldığını açıklar.',
  'actions'=>[
    ['label'=>'Çerez Politikası','href'=>url('cerez-politikasi')],
    ['label'=>'İletişime Geç','href'=>url('iletisim'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Gizlilik Politikası']]
]);
