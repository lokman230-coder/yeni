<?php
require_once __DIR__ . '/../shared/content-renderer.php';

$company = function_exists('admin_setting') ? admin_setting('company_name', admin_setting('site_name', 'Ahost One')) : 'Ahost One';
$email = function_exists('admin_setting') ? admin_setting('company_email', admin_setting('contact_email', 'iletisim@example.com')) : 'iletisim@example.com';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Son güncelleme</strong><span>•</span><span><?= e(date('d.m.Y')) ?></span></div>
  <h2>Çerezleri neden kullanıyoruz?</h2>
  <p><?= e($company) ?> web sitesi; oturum güvenliği, dil/para birimi tercihleri, sepet işlemleri, hizmet kalitesi ve kullanıcı deneyimini geliştirmek için çerez kullanır. Analitik çerezler yalnızca onay verdiğinizde etkinleşir.</p>
  <p>Çerezler; ziyaret tercihlerinizi hatırlamak, site performansını ölçmek, hangi hizmetlerin daha fazla ilgi gördüğünü anlamak ve destek süreçlerini iyileştirmek için kullanılır.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">Zorunlu</span>
    <h3>Oturum ve güvenlik çerezleri</h3>
    <p>Giriş, sepet, CSRF güvenliği, dil ve para birimi gibi temel işlevler için gerekir. Bu çerezler siteyi çalıştırmak için zorunludur.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Tercih</span>
    <h3>Kişisel tercih çerezleri</h3>
    <p>Dil, para birimi, tema veya benzer arayüz tercihlerinizi hatırlamak için kullanılır.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Analitik</span>
    <h3>Kullanım analizi</h3>
    <p>Onay verdiğinizde hangi sayfaların, ürünlerin ve aksiyonların daha çok ilgi gördüğünü anonim ziyaretçi kimliğiyle ölçeriz.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Reklam</span>
    <h3>Kampanya ölçümü</h3>
    <p>Onay verilen durumlarda kampanya ve yönlendirme kaynaklarının performansı ölçülerek hizmet ve iletişim içerikleri geliştirilebilir.</p>
  </article>
</section>

<section class="ao-content-panel">
  <h2>Toplanan kullanım verileri</h2>
  <p>Analitik onayı sonrası sayfa görüntüleme, tıklama, ziyaret yolu, tarayıcı bilgisi, yaklaşık IP bilgisi ve anonim ziyaretçi kimliği kaydedilebilir. Bu veriler siteyi geliştirmek, ürünleri iyileştirmek ve müşteri ihtiyaçlarını anlamak için kullanılır.</p>
  <p>Şifre, ödeme kartı bilgisi veya özel mesaj içerikleri çerez analitiği kapsamında toplanmaz.</p>
</section>

<section class="ao-content-panel">
  <h2>Çerez tercihlerinizi nasıl yönetirsiniz?</h2>
  <p>Siteye ilk girişte çerez bannerı üzerinden analitik çerezleri kabul edebilir ya da reddedebilirsiniz. Reddetmeniz halinde zorunlu çerezler çalışmaya devam eder; analitik ve kampanya ölçümü yapılmaz.</p>
  <p>Tarayıcı ayarlarınızdan çerezleri silebilir, engelleyebilir veya hangi sitelerin çerez kullanabileceğini yönetebilirsiniz.</p>
  <p>Talep ve sorularınız için <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> adresinden bize ulaşabilirsiniz.</p>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Çerez Politikası',
  'kicker'=>'Gizlilik ve Analitik',
  'summary'=>'Çerezlerin nasıl kullanıldığını, hangi verilerin toplandığını ve tercihlerinizi nasıl yönetebileceğinizi açıklar.',
  'actions'=>[
    ['label'=>'Gizlilik Politikası','href'=>url('gizlilik-politikasi')],
    ['label'=>'İletişime Geç','href'=>url('iletisim'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Çerez Politikası']]
]);
