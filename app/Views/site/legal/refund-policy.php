<?php
require_once __DIR__ . '/../shared/content-renderer.php';

ob_start();
?>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>İade</strong><span>•</span><span>Memnuniyet ve ürün istisnaları</span></div>
  <h2>İade şartları</h2>
  <p>Müşteri memnuniyeti Ahost Bilişim için önceliklidir. Uygun hizmetlerde, satın alma tarihinden itibaren 14 gün içinde memnuniyetsizlik bildirilmesi halinde iade talebi değerlendirilebilir.</p>
  <p>İade talepleri müşteri paneli veya destek kanalları üzerinden iletilmelidir. İptal sebebinin paylaşılması hizmet kalitesini geliştirmemize yardımcı olur.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card">
    <span class="ao-content-badge">14 Gün</span>
    <h3>Koşullu memnuniyet iadesi</h3>
    <p>Uygun paketlerde, aynı ürün için bir defaya mahsus olmak üzere 14 gün içinde iade talebi oluşturulabilir.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">İstisna</span>
    <h3>Kurulum ve özel işlem</h3>
    <p>Müşteri talebiyle özel yazılım kurulumu, sunucu müdahalesi veya kişiye özel teslim yapılan hizmetlerde iade uygulanmayabilir.</p>
  </article>
  <article class="ao-content-card">
    <span class="ao-content-badge">Yasal</span>
    <h3>Sözleşmeye aykırı kullanım</h3>
    <p>Hizmet sözleşmesine aykırı kullanım, kötüye kullanım veya yasaklı içerik nedeniyle kapatılan hizmetlerde ücret iadesi yapılmayabilir.</p>
  </article>
</section>

<section class="ao-content-panel">
  <h2>İade kapsamı dışında kalan ürünler</h2>
  <p>Alan adı kayıtları, toplu mail paketleri, yazılım ve lisanslar, kontrol paneli lisansları, SSL sertifikaları, dedicated IP, arama motoru kaydı ve kişiye özel ek servisler genel olarak iade kapsamı dışındadır.</p>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'İade Şartları',
  'kicker'=>'Yasal',
  'summary'=>'Hangi hizmetlerde iade talebi oluşturulabileceğini ve hangi ürünlerin kapsam dışında olduğunu açıklar.',
  'actions'=>[
    ['label'=>'Kullanım Şartları','href'=>url('kullanim-sartlari')],
    ['label'=>'Destek Talebi','href'=>url('iletisim'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'İade Şartları']]
]);
