<?php
$aoDomainPriceRows = [];
try {
  $aoDomainPriceRows = db()->query("SELECT tld, register_price, transfer_price, renew_price, restore_price, currency, is_active FROM tld_pricing WHERE is_active=1 ORDER BY tld ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(Throwable $e) {}
if (!$aoDomainPriceRows && function_exists('ao_domain_sale_price')) {
  foreach (['com','net','org','com.tr','net.tr','info'] as $aoTld) {
    $aoPrice = (float)ao_domain_sale_price($aoTld);
    $aoDomainPriceRows[] = ['tld'=>'.'.$aoTld,'register_price'=>$aoPrice,'transfer_price'=>$aoPrice,'renew_price'=>$aoPrice,'restore_price'=>0,'currency'=>'TRY','is_active'=>1];
  }
}
$aoPopularTlds = ['com','com.tr','net','org','net.tr','org.tr','info','biz','co','io','ai','app','dev','shop','store','online','site','tech','web.tr','gen.tr','tv','me'];
$aoTurkeyTlds = ['tr','com.tr','net.tr','org.tr','web.tr','gen.tr','biz.tr','info.tr','av.tr','dr.tr','name.tr','tel.tr','tv.tr','bel.tr','edu.tr','gov.tr','k12.tr','kep.tr','pol.tr','tsk.tr'];
$aoGlobalTlds = ['com','net','org','info','biz','co','io','ai','app','dev','shop','store','online','site','tech','xyz','pro','cloud','digital','agency','media','company'];
$aoEuropeTlds = ['eu','de','fr','it','es','nl','be','at','ch','pl','se','no','dk','fi','ie','pt','gr','cz','ro','hu','bg','sk','si','hr','lt','lv','ee'];
$aoCountryTlds = array_unique(array_merge($aoTurkeyTlds, $aoEuropeTlds, ['us','uk','co.uk','ca','au','com.au','br','in','jp','kr','cn','sg','ae','sa','qa']));
$aoTldCountryKeywords = ['tr'=>'turkiye turkey türkiye','com.tr'=>'turkiye turkey türkiye','net.tr'=>'turkiye turkey türkiye','org.tr'=>'turkiye turkey türkiye','web.tr'=>'turkiye turkey türkiye','gen.tr'=>'turkiye turkey türkiye','de'=>'almanya germany avrupa europe','fr'=>'fransa france avrupa europe','it'=>'italya italy avrupa europe','es'=>'ispanya spain avrupa europe','nl'=>'hollanda netherlands avrupa europe','eu'=>'avrupa europe','uk'=>'ingiltere united kingdom britanya','co.uk'=>'ingiltere united kingdom britanya','us'=>'amerika usa abd united states','ca'=>'kanada canada','au'=>'avustralya australia','com.au'=>'avustralya australia','br'=>'brezilya brazil','in'=>'hindistan india','jp'=>'japonya japan','kr'=>'kore korea','cn'=>'cin china çin','sg'=>'singapur singapore','ae'=>'dubai bae emirates','sa'=>'suudi arabistan saudi','qa'=>'katar qatar'];
if (!function_exists('ao_domain_public_price_html_v2710')) {
  function ao_domain_public_price_html_v2710($amount, $currency) {
    $amount=(float)$amount; $currency=strtoupper((string)$currency ?: 'TRY');
    if (function_exists('ao_price_html')) return ao_price_html($amount, $currency);
    return e(number_format($amount,2,',','.').' '.$currency);
  }
}
$aoPopularIndex = array_flip($aoPopularTlds);
usort($aoDomainPriceRows, function($a, $b) use ($aoPopularIndex) {
  $ta = ltrim(strtolower((string)($a['tld'] ?? '')), '.');
  $tb = ltrim(strtolower((string)($b['tld'] ?? '')), '.');
  $pa = array_key_exists($ta, $aoPopularIndex) ? $aoPopularIndex[$ta] : 9999;
  $pb = array_key_exists($tb, $aoPopularIndex) ? $aoPopularIndex[$tb] : 9999;
  if ($pa !== $pb) return $pa <=> $pb;
  return strnatcasecmp($ta, $tb);
});
$aoBackorderNotifyFee = admin_setting('domain_backorder_notify_fee','99.00');
$aoBackorderPreorderFee = admin_setting('domain_backorder_preorder_fee','249.00');
$aoBackorderCurrency = admin_setting('domain_backorder_currency','TRY');
$aoCurrentCustomer = function_exists('current_customer') ? current_customer() : null;
$aoBackorderName = trim((string)(($aoCurrentCustomer['first_name']??'').' '.($aoCurrentCustomer['last_name']??'')));
$aoBackorderEmail = (string)($aoCurrentCustomer['email'] ?? '');
$aoBackorderPhone = (string)($aoCurrentCustomer['phone'] ?? '');
$aoDomainHero = function_exists('ao_site_hero_find') ? (ao_site_hero_find('domain') ?: []) : [];
?>
<section class="ao-public-page ao-domain-page" data-domain-widget>
  <div class="ao-public-shell">
    <div class="ao-domain-tabs" data-domain-tabs>
      <div class="ao-domain-tabs-nav" role="tablist">
        <button type="button" class="ao-domain-tab-btn is-active" data-domain-tab="whois" role="tab" aria-selected="true">Domain Sorgula &amp; WHOIS</button>
        <button type="button" class="ao-domain-tab-btn" data-domain-tab="transfer" role="tab" aria-selected="false">Domain Transferi</button>
        <button type="button" class="ao-domain-tab-btn" data-domain-tab="prices" role="tab" aria-selected="false">Domain Fiyatları</button>
        <button type="button" class="ao-domain-tab-btn" data-domain-tab="backorder" role="tab" aria-selected="false">Backorder</button>
      </div>

      <div class="ao-domain-tab-panel is-active" data-domain-panel="whois">
        <section class="ao-domain-hero-card ao-managed-hero"<?= function_exists('ao_site_hero_style') ? ao_site_hero_style($aoDomainHero) : '' ?>>
          <span class="ao-kicker"><?= e($aoDomainHero['kicker'] ?? 'Domain Center UX Pro') ?></span>
          <h1><?= e($aoDomainHero['title'] ?? 'Markanız için doğru domaini bulun.') ?></h1>
          <p><?= e($aoDomainHero['description'] ?? 'Domain sorgulayın, uygun olanı sepete ekleyin, WHOIS/DNS/SSL araçlarıyla teknik durumu analiz edin ve domain değerlemesi alın.') ?></p>
          <div class="ao-domain-search-pro">
            <input data-domain-input placeholder="ornekdomain.com" value="<?= e($_GET['domain'] ?? $_GET['q'] ?? '') ?>">
            <button type="button" data-domain-search>Sorgula</button>
          </div>
          <div class="ao-domain-tool-grid" id="whois">
            <button type="button" data-domain-tool="whois"><span></span><b>Detaylı WHOIS</b><small>Kayıt, bitiş ve durum bilgisi</small></button>
            <button type="button" data-domain-tool="dns"><span></span><b>DNS Kayıtları</b><small>A, MX, TXT, NS, CAA kontrolü</small></button>
            <button type="button" data-domain-tool="ssl"><span></span><b>SSL Kontrol</b><small>Sertifika ve güvenlik durumu</small></button>
            <button type="button" data-domain-tool="valuation"><span></span><b>Domain Değerleme</b><small>Marka, SEO ve ticari puan</small></button>
          </div>
          <div class="ao-domain-search-result" data-domain-search-result></div>
          <a class="ao-content-btn secondary" href="<?= url('client/domains') ?>">Müşteri Domainlerim</a>
        </section>
      </div>

      <div class="ao-domain-tab-panel" data-domain-panel="transfer">
        <section class="ao-domain-hero-card ao-domain-transfer-card" id="transfer">
          <div>
            <span class="ao-kicker">Domain Transferi</span>
            <h2>Domaininizi Ahost One'a taşıyın.</h2>
            <p>Domain adını ve mevcut firmanızdan aldığınız EPP / Transfer kodunu girin. Transfer kilidi kapalıysa uyarı gösterilmez; kilitliyse gerekli şartlar belirtilir.</p>
          </div>
          <form method="post" action="<?= url('domain-transfer/start') ?>" class="ao-domain-transfer-form">
            <?= csrf_field() ?>
            <label>Domain Adı<input name="domain" placeholder="ornekdomain.com" required></label>
            <label>EPP / Transfer Kodu<input name="epp_code" placeholder="Transfer kodunuzu girin" required autocomplete="off"></label>
            <div class="ao-transfer-lock-warning" data-transfer-lock-warning><strong>Transfer kilidi kapalı olmalı</strong><span></span></div>
            <button type="submit">Transferi Sepete Ekle</button>
            <small>Not: EPP kodu, mevcut domain firmanızdan alınan transfer yetkilendirme kodudur.</small>
          </form>
        </section>
      </div>

      <div class="ao-domain-tab-panel" data-domain-panel="prices">
        <section class="ao-domain-hero-card ao-domain-prices-card">
          <span class="ao-kicker">Domain Fiyatları</span>
          <h2>Uzantı fiyatlarını karşılaştırın.</h2>
          <p>Popüler, Türkiye, global ve ülke uzantılarını filtreleyin; uzantı yazarak kayıt, transfer ve yenileme fiyatını hızlıca görün.</p>
          <div class="ao-domain-price-search"><label>Domain uzantısı ara<input type="search" data-domain-price-filter placeholder=".com, net, com.tr"></label><div class="ao-domain-price-preview" data-domain-price-preview>Uzantı yazarak fiyatı hızlıca bulun.</div></div>
          <div class="ao-domain-price-segments" role="tablist" aria-label="Domain fiyat kategorileri">
            <button type="button" class="is-active" data-domain-price-segment="popular">Popüler</button><button type="button" data-domain-price-segment="turkey">Türkiye</button><button type="button" data-domain-price-segment="global">Global</button><button type="button" data-domain-price-segment="europe">Avrupa</button><button type="button" data-domain-price-segment="country">Ülke Uzantıları</button><button type="button" data-domain-price-segment="all">Tümü</button>
          </div>
          <div class="ao-domain-price-table-wrap"><table class="ao-domain-price-table"><thead><tr><th>Uzantı</th><th>Kayıt</th><th>Transfer</th><th>Yenileme</th><th>Kurtarma</th></tr></thead><tbody>
            <?php foreach($aoDomainPriceRows as $row): $cur=$row['currency'] ?: 'TRY'; $plainTld=ltrim(strtolower((string)$row['tld']),'.'); $cats=[]; if(in_array($plainTld,$aoPopularTlds,true)) $cats[]='popular'; if(in_array($plainTld,$aoTurkeyTlds,true)) $cats[]='turkey'; if(in_array($plainTld,$aoGlobalTlds,true)) $cats[]='global'; if(in_array($plainTld,$aoEuropeTlds,true)) $cats[]='europe'; if(in_array($plainTld,$aoCountryTlds,true)) $cats[]='country'; $cats[]='all'; $countryKeys=$aoTldCountryKeywords[$plainTld] ?? ''; ?>
              <tr data-domain-price-row data-tld="<?= e($plainTld) ?>" data-country="<?= e($countryKeys) ?>" data-category="<?= e(implode(' ', array_unique($cats))) ?>" data-label="<?= e('.'.$plainTld) ?>" data-register="<?= e(strip_tags(ao_domain_public_price_html_v2710((float)$row['register_price'], $cur))) ?>" data-transfer="<?= e(strip_tags(ao_domain_public_price_html_v2710((float)$row['transfer_price'], $cur))) ?>" data-renew="<?= e(strip_tags(ao_domain_public_price_html_v2710((float)$row['renew_price'], $cur))) ?>" data-restore="<?= e(strip_tags(ao_domain_public_price_html_v2710((float)($row['restore_price']??0), $cur))) ?>"><td><strong><?= e('.'.ltrim((string)$row['tld'],'.')) ?></strong></td><td><?= ao_domain_public_price_html_v2710((float)$row['register_price'], $cur) ?></td><td><?= ao_domain_public_price_html_v2710((float)$row['transfer_price'], $cur) ?></td><td><?= ao_domain_public_price_html_v2710((float)$row['renew_price'], $cur) ?></td><td><?= (float)($row['restore_price']??0)>0 ? ao_domain_public_price_html_v2710((float)$row['restore_price'], $cur) : '-' ?></td></tr>
            <?php endforeach; ?><?php if(!$aoDomainPriceRows): ?><tr><td colspan="5">Henüz domain fiyat kaydı yok.</td></tr><?php endif; ?>
          </tbody></table></div>
        </section>
      </div>

      <div class="ao-domain-tab-panel" data-domain-panel="backorder">
        <section class="ao-domain-hero-card ao-domain-backorder-card" data-domain-backorder>
          <span class="ao-kicker">Backorder &amp; Bildirim</span>
          <h2>Alınmış domain için takip talebi oluşturun.</h2>
          <p>Domain boşa düşerse e-posta, SMS veya WhatsApp ile haberdar olun. Dilerseniz ön sipariş/backorder talebiyle yakalama sürecini ayrıca başlatabilirsiniz.</p>
          <form method="post" action="<?= url('domain/backorder-create') ?>" class="ao-backorder-form">
            <?= csrf_field() ?>
            <div class="ao-backorder-form-head"><div><strong>Bildirim talebi</strong><span>Domain boşa düştüğünde seçtiğiniz kanallardan haber verelim.</span></div><button type="submit">Talep Oluştur</button></div>
            <div class="ao-backorder-fees"><div><b>Düşünce Bildir</b><span><?= e(number_format((float)$aoBackorderNotifyFee,2,',','.').' '.$aoBackorderCurrency) ?></span></div><div><b>Ön Sipariş / Backorder</b><span><?= e(number_format((float)$aoBackorderPreorderFee,2,',','.').' '.$aoBackorderCurrency) ?></span></div></div>
            <label>Domain Adı<input name="domain_name" data-backorder-domain-input placeholder="example.com" value="<?= e($_GET['domain'] ?? $_GET['q'] ?? '') ?>" required></label>
            <label>Talep Türü<select name="request_type"><option value="notify">Düşünce Bildir / Bildirim Al</option><option value="preorder">Ön Sipariş / Backorder</option></select></label>
            <label>Ad Soyad<input name="contact_name" value="<?= e($aoBackorderName) ?>" required></label>
            <label>E-posta<input name="email" type="email" value="<?= e($aoBackorderEmail) ?>" required></label>
            <label>Telefon<input name="phone" value="<?= e($aoBackorderPhone) ?>" placeholder="+905..."></label>
            <div class="ao-backorder-channels" aria-label="Bildirim kanalları"><label><input type="checkbox" name="notify_email" checked> E-posta</label><label><input type="checkbox" name="notify_sms" <?= admin_setting('domain_backorder_notify_sms','1')==='1'?'checked':'' ?>> SMS</label><label><input type="checkbox" name="notify_whatsapp" <?= admin_setting('domain_backorder_notify_whatsapp','1')==='1'?'checked':'' ?>> WhatsApp</label></div>
            <label class="ao-backorder-wide">Not<textarea name="notes" rows="3" placeholder="Özel takip notu veya satın alma önceliği"></textarea></label>
          </form>
        </section>
      </div>
    </div>

    <section class="ao-feature-strip"><div><strong>DNS Yönetimi</strong><span>A, AAAA, CNAME, MX, TXT</span></div><div><strong>Güvenli İşlem</strong><span>Transfer, DNS ve sahiplik kontrolleri</span></div><div><strong>AI Analiz</strong><span>Marka ve SEO değeri</span></div></section>
  </div>
</section>
<script>
(function(){
  var widget=document.querySelector('[data-domain-widget]');
  var input=widget ? widget.querySelector('[data-domain-input]') : null;
  var searchBtn=widget ? widget.querySelector('[data-domain-search]') : null;
  if(input && searchBtn && input.value.trim()) setTimeout(function(){ searchBtn.click(); }, 150);
  var root=document.querySelector('[data-domain-tabs]'); if(!root) return;
  var btns=root.querySelectorAll('[data-domain-tab]'); var panels=root.querySelectorAll('[data-domain-panel]');
  function activate(name, doScroll){ var found=false; btns.forEach(function(b){var on=b.dataset.domainTab===name; if(on) found=true; b.classList.toggle('is-active',on); b.setAttribute('aria-selected',on?'true':'false');}); if(!found) return; panels.forEach(function(p){p.classList.toggle('is-active',p.dataset.domainPanel===name);}); if(doScroll) root.scrollIntoView({behavior:'smooth',block:'start'}); }
  btns.forEach(function(b){b.addEventListener('click',function(){activate(b.dataset.domainTab,false); if(history.replaceState) history.replaceState(null,'','#'+b.dataset.domainTab);});});
  function fromHash(){var h=(location.hash||'').replace('#',''); if(['transfer','whois','prices','backorder'].indexOf(h)!==-1) activate(h,true);} fromHash(); window.addEventListener('hashchange',fromHash);
  var backorderInput=root.querySelector('[data-backorder-domain-input]'); if(input&&backorderInput) input.addEventListener('input',function(){ if(!backorderInput.value.trim()) backorderInput.value=input.value; });
  function aoBackorderFromSearch(domain){ if(!domain) return; activate('backorder',true); var bi=root.querySelector('[data-backorder-domain-input]'); if(bi){bi.value=domain; bi.focus();} }
  document.addEventListener('click',function(event){var link=event.target.closest('[data-domain-backorder-link]'); if(!link) return; event.preventDefault(); aoBackorderFromSearch(link.dataset.domainBackorder||'');});
  (function(){root.querySelectorAll('.ao-domain-transfer-form').forEach(function(form){var di=form.querySelector('input[name="domain"]'); var box=form.querySelector('[data-transfer-lock-warning]'); var text=box?box.querySelector('span'):null; if(!di||!box||!text)return; var timer=null; function hide(){box.classList.remove('is-visible'); text.textContent='';} function readJsonSafe(response){return response.text().then(function(raw){return JSON.parse(String(raw||'').replace(/^[\uFEFF\u200B\s]+/,''));});} function check(){var d=(di.value||'').trim(); if(!d||d.indexOf('.')===-1){hide();return;} fetch((window.AHOST_BASE_URL||'').replace(/\/$/,'')+'/api/domain-transfer-lock?domain='+encodeURIComponent(d),{headers:{Accept:'application/json'}}).then(readJsonSafe).then(function(data){if(data&&data.locked){text.textContent=data.message||'Bu domain transfer kilitli görünüyor.'; box.classList.add('is-visible');}else hide();}).catch(hide);} di.addEventListener('input',function(){window.clearTimeout(timer); timer=window.setTimeout(check,550);}); di.addEventListener('blur',check); if(di.value) check();});})();
  var priceInput=root.querySelector('[data-domain-price-filter]'); var pricePreview=root.querySelector('[data-domain-price-preview]'); var segmentBtns=[].slice.call(root.querySelectorAll('[data-domain-price-segment]')); var priceRows=[].slice.call(root.querySelectorAll('[data-domain-price-row]')); var activeSegment='popular';
  function normalizeTld(v){return String(v||'').toLowerCase().trim().replace(/^\.+/,'');}
  function renderPriceSearch(){var q=priceInput?normalizeTld(priceInput.value):''; var first=null,visible=0; priceRows.forEach(function(row){var tld=normalizeTld(row.dataset.tld); var cats=' '+(row.dataset.category||'all')+' '; var inSegment=activeSegment==='all'||cats.indexOf(' '+activeSegment+' ')!==-1; var country=String(row.dataset.country||'').toLowerCase(); var inQuery=!q||tld.indexOf(q)!==-1||country.indexOf(q)!==-1; var ok=inSegment&&inQuery; row.style.display=ok?'':'none'; if(ok){visible++; if(!first) first=row;}}); if(!pricePreview)return; if(!q){pricePreview.classList.remove('has-result'); pricePreview.textContent=visible?(visible+' uzantı listeleniyor. Uzantı yazarak fiyatı hızlıca bulun.'):'Bu kategoride gösterilecek fiyat kaydı yok.'; return;} if(first){pricePreview.classList.add('has-result'); pricePreview.innerHTML='<strong>'+first.dataset.label+'</strong><span>Kayıt: '+first.dataset.register+'</span><span>Transfer: '+first.dataset.transfer+'</span><span>Yenileme: '+first.dataset.renew+'</span><span>Kurtarma: '+first.dataset.restore+'</span>';} else {pricePreview.classList.remove('has-result'); pricePreview.textContent='Bu uzantı için fiyat kaydı bulunamadı.';}}
  segmentBtns.forEach(function(btn){btn.addEventListener('click',function(){activeSegment=btn.dataset.domainPriceSegment||'popular'; segmentBtns.forEach(function(item){item.classList.toggle('is-active',item===btn);}); renderPriceSearch();});}); if(priceInput){priceInput.addEventListener('input',renderPriceSearch); renderPriceSearch();}
})();
</script>
