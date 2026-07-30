<?php
$product=$product ?? []; $pricing=$pricing ?? []; $configOptions=$configOptions ?? []; $customFields=$customFields ?? [];
$plainDesc = trim(function_exists('ao_v2400_plain_from_html') ? ao_v2400_plain_from_html($product['description'] ?? '', 800) : strip_tags((string)($product['description'] ?? '')));
$name = $product['name'] ?? 'Ürün'; $type=strtolower((string)($product['type'] ?? $product['group_name'] ?? ''));
if($plainDesc===''){
  if(str_contains($type,'mobile') || str_contains(mb_strtolower($name,'UTF-8'),'android')){
    $defaultHtml='<h2>'.e($name).' ile mobilde güçlü başlangıç</h2><p>Bu paket, markanız için profesyonel Android uygulama başlangıcı, yayınlama hazırlığı ve yönetilebilir içerik yapısı sunar.</p><div class="ao-product-feature-grid"><div><b>Modern Arayüz</b><p>Markaya uygun açılış, menü, içerik ve iletişim ekranları.</p></div><div><b>Kolay Güncelleme</b><p>İçerik, görsel ve bağlantılar için pratik düzenleme süreci.</p></div><div><b>Yayın Hazırlığı</b><p>Google Play sürecine uygun temel paketleme ve kontrol listesi.</p></div></div><h3>Kimler için uygun?</h3><ul><li>Kurumsal uygulama başlangıcı isteyen firmalar</li><li>Radyo, haber, hizmet veya katalog uygulaması isteyen markalar</li><li>Sonradan geliştirilebilir mobil altyapı isteyen işletmeler</li></ul><h3>Kurulum ve teslimat</h3><p>Sipariş sonrası ihtiyaç analizi yapılır, marka bilgileri alınır ve uygulama iskeleti hazırlanır. Ek özellikler ürün yapılandırmasından seçilebilir.</p><div class="ao-product-faq"><b>Sık Sorulan Soru:</b> İçerikleri sonradan değiştirebilir miyim?<br>Evet, uygun paketlerde içerikler kolayca güncellenebilir.</div>';
  } elseif(str_contains($type,'hosting') || str_contains(mb_strtolower($name,'UTF-8'),'linux')){
    $defaultHtml='<h2>'.e($name).' hosting paketi</h2><p>Hızlı, güvenli ve yönetilebilir web hosting altyapısı ile sitenizi yayına alın. İhtiyacınız arttığında üst pakete kolayca geçebilirsiniz.</p><div class="ao-product-feature-grid"><div><b>SSD Altyapı</b><p>Web siteleri için hızlı disk ve optimize edilmiş sunucu ortamı.</p></div><div><b>Kolay Yönetim</b><p>Panel, e-posta, veritabanı ve dosya yönetimi için pratik yapı.</p></div><div><b>Güvenli Başlangıç</b><p>SSL, yedekleme ve güvenlik ek paketleriyle genişletilebilir.</p></div></div><h3>Kimler için uygun?</h3><ul><li>Kurumsal web sitesi sahipleri</li><li>Blog, tanıtım sitesi ve küçük işletmeler</li><li>Başlangıç seviyesinde ekonomik hosting arayanlar</li></ul><h3>Teknik bilgiler</h3><table><tr><th>Kurulum</th><td>Ödeme sonrası otomatik veya ekip onayı ile</td></tr><tr><th>Yönetim</th><td>Hizmet takibi ve panel bilgileri hesabınızda görünür</td></tr><tr><th>Yükseltme</th><td>Üst pakete geçiş desteklenir</td></tr></table>';
  } else {
    $defaultHtml='<h2>'.e($name).' paketi</h2><p>Bu ürün, profesyonel hizmet ihtiyacınızı net paket yapısıyla karşılamak için hazırlanmıştır.</p><div class="ao-product-feature-grid"><div><b>Net İçerik</b><p>Paket kapsamı, fiyat ve teslim detayları anlaşılır şekilde sunulur.</p></div><div><b>Esnek Yapı</b><p>Ek paket, yapılandırma ve özel alanlarla genişletilebilir.</p></div><div><b>Siparişe Hazır</b><p>Sepet, domain ve ödeme akışıyla uyumlu çalışır.</p></div></div><h3>Kimler için uygun?</h3><ul><li>Hızlı sipariş vermek isteyen işletmeler</li><li>Teklif veya abonelik modeliyle hizmet almak isteyenler</li><li>Sonradan özelleştirilebilir dijital çözüm arayan ekipler</li></ul><h3>Kurulum / teslimat</h3><p>Sipariş sonrası ürün türüne göre otomatik veya ekip onaylı teslimat akışı başlatılır.</p>';
  }
} else {
  $defaultHtml = function_exists('ao_v2400_sanitize_product_html') ? ao_v2400_sanitize_product_html($product['description'] ?? '') : $product['description'];
}
$typeProbe = mb_strtolower(($product['type'] ?? '').' '.$name.' '.($product['group_name'] ?? ''), 'UTF-8');
$isHostingProduct = str_contains($typeProbe,'hosting') || str_contains($typeProbe,'cpanel') || str_contains($typeProbe,'reseller') || str_contains($typeProbe,'mail');
$isMobileProduct = str_contains($typeProbe,'mobile') || str_contains($typeProbe,'android') || str_contains($typeProbe,'apk') || str_contains($typeProbe,'aab');
$isRadioProduct = str_contains($typeProbe,'radio') || str_contains($typeProbe,'radyo') || str_contains($typeProbe,'shoutcast') || str_contains($typeProbe,'icecast');
$isBuilderProduct = str_contains($typeProbe,'builder') || str_contains($typeProbe,'kaynak') || str_contains($typeProbe,'zip');
$rawSummary = trim($product['hero_subtitle'] ?? '') ?: trim($product['short_description'] ?? '');
if ($rawSummary === '') $rawSummary = $plainDesc;
if (mb_strlen($rawSummary, 'UTF-8') < 70) {
  if ($isHostingProduct) $rawSummary = trim($rawSummary.' Güvenli hosting altyapısı, yönetilebilir panel ve sipariş sonrası destek akışıyla yayına hızlı başlamak isteyenler için hazırlanmıştır.');
  elseif ($isRadioProduct) $rawSummary = trim($rawSummary.' Canlı yayın, dinleyici kapasitesi ve yayın yönetimi ihtiyacını tek paket altında toplamak isteyen radyolar için uygundur.');
  elseif ($isMobileProduct) $rawSummary = trim($rawSummary.' Mobil uygulama çıktısı, teslim hazırlığı ve yayın süreci için net kapsamlı bir başlangıç sunar.');
  elseif ($isBuilderProduct) $rawSummary = trim($rawSummary.' Hazırladığınız tasarımı teslim edilebilir kaynak kod veya yayın paketine dönüştürmek için kullanılır.');
}
$summary = $rawSummary ?: 'Bu paket, profesyonel hizmet yapısı ve yönetilebilir ürün içeriğiyle siparişe hazırdır.';
$detailLeadTitle = 'Bu paket size ne kazandırır?';
$detailLeadText = 'Paket kapsamı, teslim süreci ve ek seçenekleri tek ekranda netleştirerek sipariş öncesi karar vermeyi kolaylaştırır.';
$detailBenefits = ['Net paket kapsamı', 'Sepette seçilebilir periyot ve ek seçenekler', 'Sipariş sonrası takip edilebilir teslimat'];
if ($isHostingProduct) {
  $detailLeadTitle = 'Yayına güvenli ve hızlı başlayın';
  $detailLeadText = 'Web sitesi, e-posta veya panel ihtiyacınızı yönetilebilir hosting altyapısıyla başlatır; domain ve ek hizmetlerle birlikte siparişe hazır çalışır.';
  $detailBenefits = ['Panel ve hizmet bilgileri müşteri hesabında görünür', 'İhtiyaca göre yıllık/aylık periyot seçilebilir', 'Ek disk, trafik, yedekleme veya SSL hizmetleriyle genişletilebilir'];
} elseif ($isRadioProduct) {
  $detailLeadTitle = 'Canlı yayın altyapısını net paketle alın';
  $detailLeadText = 'Dinleyici kapasitesi, yayın kalitesi ve medya alanı gibi karar noktalarını sadeleştirir; radyo projenizi hızlıca yayına hazırlamanıza yardımcı olur.';
  $detailBenefits = ['Yayın kapasitesi ve kalite bilgileri açık görünür', 'Radyo paneli veya ek özellikler ürün içeriğine bağlanabilir', 'Sipariş sonrası yayın bilgileri müşteri panelinde takip edilir'];
} elseif ($isMobileProduct) {
  $detailLeadTitle = 'Mobil çıktıyı teslimata hazır hale getirin';
  $detailLeadText = 'APK, AAB veya kaynak kod ihtiyacını seçilebilir paket yapısıyla netleştirir; uygulama adı, logo ve yayın bilgileri sipariş sırasında alınabilir.';
  $detailBenefits = ['Teslim formatı açık şekilde seçilir', 'Logo ve uygulama bilgileri özel alanlarla toplanabilir', 'Yayın veya geliştirici teslimi için düzenli çıktı hazırlanır'];
} elseif ($isBuilderProduct) {
  $detailLeadTitle = 'Tasarımınızı teslim edilebilir pakete çevirin';
  $detailLeadText = 'SiteBuilder veya MobileBuilder ile hazırladığınız çalışmayı ZIP, kaynak kod veya yayınlanabilir çıktı olarak sepete ekleyip ödeme akışına taşırsınız.';
  $detailBenefits = ['Seçilen modüller sepette fiyatlandırılır', 'Silinen modüller çıktı kapsamından çıkarılır', 'Teslim notları ve paket içeriği siparişle birlikte saklanır'];
}
$detailIntroHtml = '';
if ($plainDesc !== '') {
  $detailIntroHtml = '<div class="ao-product-detail-lead"><div><span>Paket Rehberi</span><h3>'.e($detailLeadTitle).'</h3><p>'.e($detailLeadText).'</p></div><ul>';
  foreach($detailBenefits as $benefit) $detailIntroHtml .= '<li>'.e($benefit).'</li>';
  $detailIntroHtml .= '</ul></div>';
}
$priceOptions=function_exists('ao_v2524_product_price_options') ? ao_v2524_product_price_options($product) : [ao_v2335_primary_price($product)];
$selectedPrice=function_exists('ao_v2524_selected_price_option') ? ao_v2524_selected_price_option($product, $_GET['cycle'] ?? '') : ($priceOptions[0] ?? ao_v2335_primary_price($product));
$selectedCycle=(string)($selectedPrice['cycle'] ?? 'monthly');
$selectedAmount=(float)($selectedPrice['amount'] ?? 0);
?>

<?php ob_start(); ?>
<section class="ao-content-grid two">
  <div class="ao-content-card ao-product-summary-card" data-product-card data-product-id="<?= (int)($product['id'] ?? 0) ?>" data-builder-block="product-summary-<?= (int)($product['id'] ?? 0) ?>"<?= !empty($product['hero_background']) ? ' style="background:'.e($product['hero_background']).'"' : '' ?>>
    <span class="ao-content-badge"><?= e($product['hero_kicker'] ?: ($product['group_name'] ?? 'Ürün')) ?></span>
    <h3 data-product-field="name"><?= e($name) ?></h3>
    <p data-product-field="short_description"><?= e($summary) ?></p>
    <?php if(!empty($product['hero_image_url'])): ?><div class="ao-product-hero-media" style="background-image:url('<?= e($product['hero_image_url']) ?>')"></div><?php endif; ?>
    <div class="ao-content-actions">
      <?php
        $heroButtons=[
          [trim($product['hero_primary_label'] ?? '') ?: 'Satın Al', trim($product['hero_primary_url'] ?? '') ?: '#siparis-bilgileri', false],
          [trim($product['hero_secondary_label'] ?? '') ?: 'Teklif İste', trim($product['hero_secondary_url'] ?? '') ?: 'teklif?product='.urlencode((string)($product['slug'] ?? '')), true],
          [trim($product['hero_tertiary_label'] ?? '') ?: 'Tüm Ürünler', trim($product['hero_tertiary_url'] ?? '') ?: 'urunler', true],
        ];
        foreach($heroButtons as $hb):
          if($hb[0]==='') continue;
          $href=str_starts_with($hb[1],'#') || preg_match('#^https?://#i',$hb[1]) ? $hb[1] : url($hb[1]);
      ?>
        <a class="ao-content-btn<?= $hb[2] ? ' secondary' : '' ?>" href="<?= e($href) ?>"><?= e($hb[0]) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="ao-content-card" data-product-card data-product-id="<?= (int)($product['id'] ?? 0) ?>" data-builder-block="product-pricing-<?= (int)($product['id'] ?? 0) ?>">
    <span class="ao-content-badge">Fiyatlandırma</span>
    <?php if(!$pricing): $price=ao_v2335_primary_price($product); ?>
      <div class="ao-content-price" data-product-field="price" data-product-price-label><?= ao_format_price_try((float)($price['amount'] ?? 0), ($price['amount'] ?? 0) > 0 ? ao_v2335_cycle_label($price['cycle'] ?? 'monthly') : null) ?></div>
    <?php else: ?>
      <div class="ao-content-table" style="grid-template-columns:1.2fr 1fr 1fr 1fr">
        <div class="head">Periyot</div><div class="head">USD</div><div class="head">TRY</div><div class="head">Kurulum</div>
        <?php foreach($pricing as $r):
          $usd=(float)($r['price_usd'] ?? 0); $try=(float)($r['price_try'] ?? 0);
          if($try<=0 && $usd>0) $try=ao_v23_price_try($usd,'USD');
          if($try<=0 && (float)($r['price'] ?? 0)>0) $try=ao_v23_price_try((float)$r['price'], (string)($r['currency'] ?? 'TRY'));
          $setupTry=(float)($r['setup_fee_try'] ?? ($r['setup_fee'] ?? 0));
        ?>
          <div><?= e(ao_v2335_cycle_label($r['cycle'] ?? 'monthly')) ?></div><div><?= $usd>0 ? '$'.number_format($usd,2,'.','') : '-' ?></div><div><strong<?= ($r['cycle'] ?? '') === $selectedCycle ? ' data-product-field="price" data-product-price-label' : '' ?>><?= $try>0 ? number_format($try,2,',','.').' ₺' : 'Teklif' ?></strong></div><div><?= $setupTry>0 ? number_format($setupTry,2,',','.').' ₺' : '-' ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<section class="ao-content-panel ao-product-order-panel" id="siparis-bilgileri">
  <div class="ao-content-meta"><strong>Sipariş Bilgileri</strong><span></span><span>Ürüne özel alanlar</span></div>
  <form class="ao-product-order-form" method="post" action="<?= url('cart/add') ?>" enctype="multipart/form-data">
    <input type="hidden" name="product" value="<?= e($product['slug'] ?? '') ?>">
    <input type="hidden" name="base_price" value="<?= e((string)$selectedAmount) ?>" data-base-price>
    <?php if(count($priceOptions)>1): ?>
      <label class="ao-product-cycle-field">Fatura periyodu
        <select name="cycle" data-product-cycle-select>
          <?php foreach($priceOptions as $opt):
            $cycle=(string)($opt['cycle'] ?? 'monthly');
            $amount=(float)($opt['amount'] ?? 0);
            $amountLabel=trim(preg_replace('~\s+~',' ',strip_tags(ao_format_price_try($amount, null))));
          ?>
            <option value="<?= e($cycle) ?>" data-cycle-price="<?= e((string)$amount) ?>" <?= $cycle===$selectedCycle ? 'selected' : '' ?>><?= e(ao_v2335_cycle_label($cycle)) ?> - <?= e($amountLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php else: ?>
      <input type="hidden" name="cycle" value="<?= e($selectedCycle) ?>">
    <?php endif; ?>
    <?php if(!empty($product['is_custom_build_enabled']) && $configOptions): ?>
      <div class="ao-package-builder" data-package-builder>
        <div class="ao-package-head">
          <div><span class="ao-content-badge">Paket Oluşturucu</span><h3>Kendi paketini oluştur</h3><p>Disk, trafik, ek hizmet, tasarım veya mobil seçenekleri ihtiyacına göre seç.</p></div>
          <strong data-package-total><?= ao_format_price_try($selectedAmount, null) ?></strong>
        </div>
        <div class="ao-package-options">
          <?php foreach($configOptions as $opt): $type=$opt['option_type'] ?? 'dropdown'; $oid=(int)$opt['id']; ?>
            <div class="ao-package-option">
              <label><?= e($opt['name']) ?><?= !empty($opt['required']) ? ' *' : '' ?></label>
              <?php if($type==='quantity'): $unit=(float)($opt['values'][0]['price_monthly'] ?? 0); ?>
                <input type="number" min="0" value="0" name="config_options[<?= $oid ?>]" data-option-price="<?= e((string)$unit) ?>">
                <small>Birim fiyat: <?= number_format($unit,2,',','.') ?> ₺</small>
              <?php elseif($type==='radio'): ?>
                <div class="ao-package-choice-grid">
                  <?php foreach(($opt['values'] ?? []) as $v): ?>
                    <label><input type="radio" name="config_options[<?= $oid ?>]" value="<?= (int)$v['id'] ?>" data-option-price="<?= e((string)(float)$v['price_monthly']) ?>"> <span><?= e($v['label']) ?><small><?= (float)$v['price_monthly']>0 ? '+ '.number_format((float)$v['price_monthly'],2,',','.').' ₺' : 'Dahil' ?></small></span></label>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <select name="config_options[<?= $oid ?>]" data-package-select>
                  <option value="" data-option-price="0">Seçiniz</option>
                  <?php foreach(($opt['values'] ?? []) as $v): ?>
                    <option value="<?= (int)$v['id'] ?>" data-option-price="<?= e((string)(float)$v['price_monthly']) ?>"><?= e($v['label']) ?><?= (float)$v['price_monthly']>0 ? ' + '.number_format((float)$v['price_monthly'],2,',','.').' ₺' : '' ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="ao-order-grid">
      <?php foreach($customFields as $field):
        $fieldKey=(string)($field['field_key'] ?? '');
        $fieldType=(string)($field['field_type'] ?? 'text');
        $fieldName='custom_fields['.$fieldKey.']';
        $required=!empty($field['is_required']);
        $label=(string)($field['label'] ?? $fieldKey);
        $options=preg_split('~[\r\n,]+~', (string)($field['options'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      ?>
        <?php if($fieldType === 'textarea'): ?>
          <label class="full"><?= e($label) ?><?= $required ? ' *' : '' ?><textarea name="<?= e($fieldName) ?>" rows="3" <?= $required ? 'required' : '' ?>></textarea></label>
        <?php elseif($fieldType === 'select'): ?>
          <label><?= e($label) ?><?= $required ? ' *' : '' ?><select name="<?= e($fieldName) ?>" <?= $required ? 'required' : '' ?>><option value="">Seçiniz</option><?php foreach($options as $opt): $opt=trim((string)$opt); ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?></select></label>
        <?php elseif($fieldType === 'file'): ?>
          <label><?= e($label) ?><?= $required ? ' *' : '' ?><input type="file" name="custom_files[<?= e($fieldKey) ?>]" <?= $required ? 'required' : '' ?> accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.pdf,image/*,application/pdf"></label>
        <?php else: $inputType=in_array($fieldType,['url','tel','email','number'],true) ? $fieldType : 'text'; ?>
          <label><?= e($label) ?><?= $required ? ' *' : '' ?><input type="<?= e($inputType) ?>" name="<?= e($fieldName) ?>" <?= $required ? 'required' : '' ?>></label>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if(!$customFields): ?>
        <label class="full">Sipariş Notu <textarea name="panel_note" rows="3" placeholder="Sipariş için eklemek istediğiniz not"></textarea></label>
      <?php endif; ?>
    </div>
    <div class="ao-content-actions"><button class="ao-content-btn">Sepete Ekle</button></div>
  </form>
</section>
<section class="ao-content-panel">
  <div class="ao-content-meta"><strong>Ürün Açıklaması</strong><span></span><span>Paket kapsamı ve teslim bilgileri</span></div>
  <div class="ao-content-rich ao-product-rich-content"><?= $detailIntroHtml ?><?= $defaultHtml ?></div>
</section>
<?php
$content=ob_get_clean();
$heroTitle=trim($product['hero_title'] ?? '') ?: $name;
$kicker=trim($product['hero_kicker'] ?? '') ?: ($product['group_name'] ?? 'Ürün');
$summary=$summary;
$productBuilderId=(int)($product['id'] ?? 0);
$class='ao-product-detail-page';
$shellClass='ao-product-detail-shell';
$breadcrumbs=[['label'=>'Ana Sayfa','href'=>url('')],['label'=>'Ürünler','href'=>url('urunler')],['label'=>$name]];
$actions=[];
foreach([
  [trim($product['hero_primary_label'] ?? '') ?: 'Satın Al', trim($product['hero_primary_url'] ?? '') ?: '#siparis-bilgileri', false],
  [trim($product['hero_secondary_label'] ?? '') ?: 'Teklif İste', trim($product['hero_secondary_url'] ?? '') ?: 'teklif?product='.urlencode((string)($product['slug'] ?? '')), true],
  [trim($product['hero_tertiary_label'] ?? '') ?: 'Tüm Ürünler', trim($product['hero_tertiary_url'] ?? '') ?: 'urunler', true],
] as $hb){
  if($hb[0]==='') continue;
  $href=str_starts_with($hb[1],'#') || preg_match('#^https?://#i',$hb[1]) ? $hb[1] : url($hb[1]);
  $actions[]=['label'=>$hb[0],'href'=>$href,'secondary'=>$hb[2]];
}
require __DIR__.'/../shared/content-page.php';
?>
<script>
(function(){
  var select=document.querySelector('[data-install-select]');
  function sync(){
    document.querySelectorAll('[data-panel-field]').forEach(function(row){ row.style.display = select.value === 'evet' ? '' : 'none'; });
  }
  if(select){
    select.addEventListener('change', sync);
    sync();
  }
  var builder=document.querySelector('[data-package-builder]');
  var cycleSelect=document.querySelector('[data-product-cycle-select]');
  var baseInput=document.querySelector('[data-base-price]');
  function currentBase(){ return parseFloat((baseInput||{}).value||'0')||0; }
  function syncCycle(){
    if(!cycleSelect || !baseInput) return;
    var option=cycleSelect.options[cycleSelect.selectedIndex];
    baseInput.value=(option && option.dataset.cyclePrice) ? option.dataset.cyclePrice : baseInput.value;
  }
  if(builder){
    var totalEl=builder.querySelector('[data-package-total]');
    function money(n){ return n.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺'; }
    function calc(){
      syncCycle();
      var total=currentBase();
      builder.querySelectorAll('select').forEach(function(sel){ var op=sel.options[sel.selectedIndex]; total += parseFloat((op&&op.dataset.optionPrice)||'0')||0; });
      builder.querySelectorAll('input[type="radio"]:checked').forEach(function(inp){ total += parseFloat(inp.dataset.optionPrice||'0')||0; });
      builder.querySelectorAll('input[type="number"][data-option-price]').forEach(function(inp){ total += (parseFloat(inp.dataset.optionPrice||'0')||0) * (parseInt(inp.value||'0',10)||0); });
      if(totalEl) totalEl.textContent=money(total);
    }
    builder.addEventListener('input', calc);
    builder.addEventListener('change', calc);
    if(cycleSelect) cycleSelect.addEventListener('change', calc);
    calc();
  } else {
    if(cycleSelect) cycleSelect.addEventListener('change', syncCycle);
    syncCycle();
  }
})();
</script>
