<?php
require_once __DIR__ . '/../shared/content-renderer.php';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Hizmet sözleşmesi</strong><span>•</span><span>Kullanım koşulları ve operasyon politikaları</span></div>
  <h2>Kullanım koşulları ve hizmet sözleşmesi</h2>
  <p>Bu sayfa; Ahost Bilişim üzerinden alınan alan adı, hosting, sunucu, SSL, lisans, web tasarım, mobil uygulama ve ek dijital hizmetlerin kullanım şartlarını açıklar. Hizmet satın alan kullanıcılar, ilgili ürünün teknik sınırları ve yasal kullanım koşullarına uymayı kabul eder.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">Çalışma Saatleri</span>
    <h3>Destek ve muhasebe</h3>
    <p>Telefon ve muhasebe işlemleri hafta içi mesai saatlerinde yürütülür. Teknik destek talepleri destek sistemi üzerinden 7/24 alınır ve önceliğe göre yanıtlanır.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Sipariş</span>
    <h3>Onay ve hesap açılışı</h3>
    <p>Kredi kartı ödemelerinde uygun ürünler otomatik olarak aktifleşebilir. Havale/EFT ödemelerinde ödeme bildirimi ve kontrol süreci tamamlandıktan sonra hizmet açılır.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Kampanya</span>
    <h3>Promosyon koşulları</h3>
    <p>Kampanyalı ürün fiyatları kampanya koşullarına bağlıdır. Süre sonunda paketler güncel fiyatlardan yenilenebilir.</p>
  </article>
</section>

<section class="ao-content-panel">
  <h2>İzin verilmeyen içerik ve kullanım</h2>
  <p>T.C. kanunlarına aykırı içerik, lisanssız yazılım, spam gönderimi, zararlı yazılım, yasa dışı dijital materyal paylaşımı, müstehcen içerik, kumar, uyuşturucuya yönlendirme ve 5651 sayılı kanunda belirtilen yasaklı faaliyetler barındırılamaz.</p>
  <p>Kurallara aykırı kullanım tespit edildiğinde kullanıcı uyarılabilir, içerik kaldırılabilir, hizmet geçici olarak durdurulabilir veya hesap kapatılabilir.</p>
</section>

<section class="ao-content-panel">
  <h2>Domain, yenileme ve transfer</h2>
  <p>Alan adı kayıtlarında yenileme tarihleri müşteriye bildirilir. Süresi dolan domainlerde registrar kuralları, yenileme/restore ücretleri ve transfer sınırlamaları geçerlidir. Transfer işlemleri için EPP/Auth kodu, transfer kilidinin kapalı olması ve ilgili domainin transfer koşullarını sağlaması gerekir.</p>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Kullanım Şartları',
  'kicker'=>'Şartlar ve Koşullar',
  'summary'=>'Hizmet kullanımı, sipariş, kampanya, yenileme, domain ve kabul edilmeyen içerik politikalarını açıklar.',
  'actions'=>[
    ['label'=>'İade Şartları','href'=>url('iade-sartlari')],
    ['label'=>'Gizlilik Politikası','href'=>url('gizlilik-politikasi'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Kullanım Şartları']]
]);
