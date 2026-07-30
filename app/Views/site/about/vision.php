<?php
require_once __DIR__ . '/../shared/content-renderer.php';

ob_start();
?>
<section class="ao-content-panel">
  <h2>Vizyonumuz</h2>
  <p>Vizyonumuz; Türkiye'de ve uluslararası pazarda güvenilir, yenilikçi ve performans odaklı dijital altyapı sağlayıcılarından biri olmaktır. Sürekli gelişen teknolojileri takip ederek müşterilerimize daha güçlü, daha hızlı ve daha kolay yönetilebilir hizmetler sunmayı hedefliyoruz.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card"><span class="ao-content-badge">Teknoloji</span><h3>Sürekli gelişim</h3><p>Sunucu, yazılım, güvenlik ve otomasyon altyapısını güncel ihtiyaçlara göre geliştirmek.</p></article>
  <article class="ao-content-card"><span class="ao-content-badge">Pazar</span><h3>Türkiye ve dünya</h3><p>Türkiye pazarında güçlü kalırken farklı ülkelerdeki müşterilere de kaliteli hizmet sunmak.</p></article>
  <article class="ao-content-card"><span class="ao-content-badge">Deneyim</span><h3>Tek panel yaklaşımı</h3><p>Domain, hosting, tasarım, mobil uygulama ve destek süreçlerini tek deneyimde birleştirmek.</p></article>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Vizyonumuz',
  'kicker'=>'Kurumsal',
  'summary'=>'Güvenilir, yenilikçi ve performans odaklı dijital altyapı markası olmak.',
  'actions'=>[
    ['label'=>'Misyonumuz','href'=>url('misyon')],
    ['label'=>'Hakkımızda','href'=>url('hakkimizda'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Vizyonumuz']]
]);
