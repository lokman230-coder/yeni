<?php
require_once __DIR__ . '/../shared/content-renderer.php';

ob_start();
?>
<section class="ao-content-panel">
  <h2>Misyonumuz</h2>
  <p>Ahost Bilişim olarak misyonumuz; müşterilerimizin dijital ihtiyaçlarına doğru zamanda, doğru altyapı ve anlaşılır destek süreciyle cevap vermektir. Hosting, domain, web tasarım, mobil uygulama ve teknik destek hizmetlerini tek merkezden yönetilebilir hale getiriyoruz.</p>
  <p>Karmaşık teknik süreçleri sadeleştirerek işletmelerin internette güvenilir, hızlı ve sürdürülebilir şekilde yer almasını sağlıyoruz.</p>
</section>

<section class="ao-content-grid">
  <article class="ao-content-card"><span class="ao-content-badge">Hız</span><h3>Hızlı yayına alma</h3><p>Projelerin domain, hosting, SSL ve yayın süreçlerini gecikmeden devreye almak.</p></article>
  <article class="ao-content-card"><span class="ao-content-badge">Güven</span><h3>Takip edilebilir hizmet</h3><p>Destek, fatura, yenileme ve operasyon süreçlerini kayıtlı ve şeffaf yürütmek.</p></article>
  <article class="ao-content-card"><span class="ao-content-badge">Memnuniyet</span><h3>Müşteri odağı</h3><p>Müşterinin beklentisini doğru anlayıp uzun vadeli dijital çözüm ortağı olmak.</p></article>
</section>
<?php
$content = ob_get_clean();
ao_site_content_page([
  'content'=>$content,
  'heroTitle'=>'Misyonumuz',
  'kicker'=>'Kurumsal',
  'summary'=>'Dijital hizmetleri anlaşılır, güvenilir ve sürdürülebilir biçimde sunmak.',
  'actions'=>[
    ['label'=>'Vizyonumuz','href'=>url('vizyon')],
    ['label'=>'Hakkımızda','href'=>url('hakkimizda'),'secondary'=>true],
  ],
  'breadcrumbs'=>[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Misyonumuz']]
]);
