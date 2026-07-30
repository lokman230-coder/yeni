<section class="ao-site-content ao-error-page">
  <div class="ao-content-shell narrow">
    <div class="ao-content-hero"><span class="ao-content-kicker">Ahost One 404 Pro</span><h1>Kaybolmadınız, sizi doğru hizmete yönlendirelim.</h1><p>Aradığınız sayfa taşınmış, silinmiş veya bağlantı hatalı yazılmış olabilir.</p></div>
    <div class="ao-content-panel">
      <form class="ao-content-form ao-404-domain" action="<?= url('domain') ?>" method="get"><input name="q" placeholder="Domain adınızı sorgulayın: heryertatil.com"><button class="ao-content-btn">Sorgula</button></form>
      <div class="ao-content-actions"><a class="ao-content-btn secondary" href="<?= url('') ?>">Ana Sayfa</a><a class="ao-content-btn secondary" href="<?= url('domain') ?>">Domain Sorgula</a><a class="ao-content-btn secondary" href="<?= url('hosting') ?>">Hosting Paketleri</a><a class="ao-content-btn secondary" href="<?= url('marketplace') ?>">Marketplace</a><a class="ao-content-btn secondary" href="<?= url('client') ?>">Müşteri Paneli</a></div>
      <div class="ao-content-pills"><span class="ao-content-pill active">Domain sorgulama</span><span class="ao-content-pill">Hosting</span><span class="ao-content-pill">VPS</span><span class="ao-content-pill">Destek</span><span class="ao-content-pill">Bilgi Bankası</span></div>
      <?php if(function_exists('current_admin') && current_admin()): ?><div class="ao-content-empty">Admin notu: Bu 404 loglanabilir. URL: <?= e($_SERVER['REQUEST_URI'] ?? '') ?></div><?php endif; ?>
    </div>
  </div>
</section>
