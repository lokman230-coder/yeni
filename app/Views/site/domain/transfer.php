<?php
$domain = trim((string)($_GET['domain'] ?? ''));
?>
<section class="ao-public-page ao-domain-page ao-domain-transfer-page">
  <div class="ao-public-shell">
    <section class="ao-domain-hero-card ao-domain-transfer-card">
      <div>
        <span class="ao-kicker">Domain Transferi</span>
        <h1>Domaininizi Ahost One’a taşıyın.</h1>
        <p>Transfer işlemi için domain adını ve mevcut registrar’dan aldığınız EPP / Transfer kodunu girin. Transfer kilidi kapalı olmalıdır.</p>
      </div>
      <form method="post" action="<?= url('domain-transfer/start') ?>" class="ao-domain-transfer-form">
        <?= csrf_field() ?>
        <label>Domain Adı
          <input name="domain" placeholder="ornekdomain.com" value="<?= e($domain) ?>" required>
        </label>
        <label>EPP / Transfer Kodu
          <input name="epp_code" placeholder="Transfer kodunuzu girin" required autocomplete="off">
        </label>
        <button type="submit">Transferi Sepete Ekle</button>
        <small>Not: EPP kodu, mevcut domain firmanızdan alınan transfer yetkilendirme kodudur.</small>
      </form>
    </section>

    <section class="ao-feature-strip">
      <div><strong>1</strong><span>Domain adınızı yazın</span></div>
      <div><strong>2</strong><span>EPP kodunu girin</span></div>
      <div><strong>3</strong><span>Sepetten transferi tamamlayın</span></div>
      <div><strong>4</strong><span>Transfer sürecini panelden takip edin</span></div>
    </section>
  </div>
</section>
<script>
(function(){
  document.querySelectorAll('.ao-domain-transfer-form').forEach(function(form){
    var input=form.querySelector('input[name="domain"]');
    var box=form.querySelector('[data-transfer-lock-warning]');
    var text=box ? box.querySelector('span') : null;
    if(!input || !box || !text) return;
    var timer=null;
    function hide(){ box.classList.remove('is-visible'); text.textContent=''; }
    function readJsonSafe(response){ return response.text().then(function(raw){ return JSON.parse(String(raw||'').replace(/^[\uFEFF\u200B\s]+/,'')); }); }
    function check(){
      var domain=(input.value||'').trim();
      if(!domain || domain.indexOf('.')===-1){ hide(); return; }
      fetch((window.AHOST_BASE_URL||'').replace(/\/$/,'') + '/api/domain-transfer-lock?domain=' + encodeURIComponent(domain), {headers:{Accept:'application/json'}})
        .then(readJsonSafe).then(function(data){
          if(data && data.locked){ text.textContent=data.message || 'Bu domain transfer kilitli görünüyor.'; box.classList.add('is-visible'); }
          else hide();
        }).catch(hide);
    }
    input.addEventListener('input', function(){ window.clearTimeout(timer); timer=window.setTimeout(check, 550); });
    input.addEventListener('blur', check);
    if(input.value) check();
  });
})();
</script>
