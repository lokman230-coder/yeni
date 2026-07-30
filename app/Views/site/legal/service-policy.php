<?php
require_once __DIR__ . '/../shared/content-renderer.php';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Hizmet Politikası</strong><span>•</span><span>Destek, aktivasyon, yenileme ve operasyon kuralları</span></div>
  <h2>Hizmet politikası</h2>
  <p>Hizmet politikası; siparişten aktivasyona, destekten yenilemeye kadar Ahost Bilişim hizmetlerinin nasıl yürütüldüğünü açıklar. Amaç, her müşteriye takip edilebilir ve anlaşılır bir operasyon deneyimi sunmaktır.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">Destek</span>
    <h3>Teknik destek akışı</h3>
    <p>Teknik destek talepleri destek sistemi üzerinden alınır. Kritik işlemler kayıt altına alınarak çözüm süreci müşteriyle paylaşılır.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Aktivasyon</span>
    <h3>Hesap açılışı</h3>
    <p>Otomatik kuruluma uygun hizmetler ödeme sonrası kısa sürede aktifleşir. Manuel teslim gerektiren ürünlerde kontrol ve kurulum süreci uygulanır.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Yenileme</span>
    <h3>Alan adı ve hosting yenileme</h3>
    <p>Domain ve hosting süreleri müşteri panelinden takip edilir. Otomatik yenileme tercihleri, ödeme yöntemi ve registrar kuralları doğrultusunda işlem yapılır.</p>
  </article>
</section>

<section class="ao-content-panel">
  <h2>Hesap limitlemeleri ve kaynak kullanımı</h2>
  <p>Hosting hesaplarında disk, trafik, e-posta, veritabanı ve CPU kullanımı paket limitleri dahilindedir. Paylaşımlı kaynakları aşırı zorlayan veya diğer müşterilerin hizmet kalitesini etkileyen kullanımda geçici sınırlandırma uygulanabilir.</p>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Hizmet Politikası',
  'kicker'=>'Operasyon',
  'summary'=>'Destek saatleri, aktivasyon, yenileme ve hizmet kullanım kurallarını açıklar.',
  'actions'=>[
    ['label'=>'Kullanım Şartları','href'=>url('kullanim-sartlari')],
    ['label'=>'İletişim','href'=>url('iletisim'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Hizmet Politikası']]
]);
