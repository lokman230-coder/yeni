<?php
$groups = $groups ?? [];
$products = $products ?? [];
$selectedGroup = $selectedGroup ?? null;
function ao_v2339_product_icon($type){
  $type = mb_strtolower((string)$type,'UTF-8');
  if(str_contains($type,'hosting')) return '☁️';
  if(str_contains($type,'server') || str_contains($type,'vps')) return '🖥️';
  if(str_contains($type,'domain')) return '🌐';
  if(str_contains($type,'ssl')) return '🔒';
  if(str_contains($type,'web')) return '🎨';
  if(str_contains($type,'mobile')) return '📱';
  if(str_contains($type,'seo')) return '📈';
  return '🚀';
}
function ao_v2339_product_style($p){
  $styles = [];
  if(!empty($p['card_background'])) $styles[] = '--ao-product-card-bg:' . e($p['card_background']);
  if(!empty($p['card_text_color'])) $styles[] = '--ao-product-card-text:' . e($p['card_text_color']);
  if(!empty($p['card_button_color'])) $styles[] = '--ao-product-btn-bg:' . e($p['card_button_color']);
  if(!empty($p['card_button_text_color'])) $styles[] = '--ao-product-btn-text:' . e($p['card_button_text_color']);
  return $styles ? ' style="' . implode(';', $styles) . '"' : '';
}
function ao_v2339_product_features($p){
  $text = trim(strip_tags((string)($p['short_description'] ?? $p['description'] ?? '')));
  $features = [];
  $patterns = [
    'DISK\\s+([^A-Z]+?)(?=\\s+(TRAF|TRAFİK|BANDWIDTH|DATABASE|EMAIL|TLD|$))' => '$1 SSD Disk',
    '(TRAF|TRAFİK|BANDWIDTH)\\s+([^A-Z]+?)(?=\\s+(DATABASE|EMAIL|TLD|DISK|$))' => '$2 Trafik',
    'DATABASE\\s+([^A-Z]+?)(?=\\s+(EMAIL|TLD|DISK|TRAF|TRAFİK|$))' => '$1 Veritabanı',
    'EMAIL\\s+([^A-Z]+?)(?=\\s+(TLD|DISK|TRAF|TRAFİK|DATABASE|$))' => '$1 E-posta Hesabı',
    'TLD\\s+([^A-Z]+?)(?=\\s+(DISK|TRAF|TRAFİK|DATABASE|EMAIL|$))' => '$1 Alt Domain',
  ];
  foreach($patterns as $rx=>$fmt){
    if(preg_match('~'.$rx.'~iu', $text, $m)){
      $val = trim($m[count($m)-1]);
      $val = preg_replace('~\\s+~',' ',$val);
      $features[] = trim(str_replace(['$1','$2'], [$m[1]??$val,$m[2]??$val], $fmt));
    }
  }
  $features = array_values(array_filter(array_unique($features)));
  if(count($features) >= 2) return array_slice($features,0,5);
  $type = mb_strtolower((string)($p['type'] ?? $p['group_name'] ?? ''),'UTF-8');
  if(str_contains($type,'hosting')) return ['NVMe SSD altyapı','Ücretsiz SSL','7/24 destek','Kolay panel yönetimi'];
  if(str_contains($type,'server') || str_contains($type,'vps')) return ['Yüksek performans','Ölçeklenebilir kaynak','Yönetim desteği','Anlık teslimat'];
  if(str_contains($type,'domain')) return ['Hızlı kayıt','DNS yönetimi','Transfer desteği'];
  if(str_contains($type,'ssl')) return ['Güvenli bağlantı','SEO uyumlu','Kolay kurulum'];
  return ['Kurumsal çözüm','Hızlı teslimat','Uzman destek'];
}
function ao_v2339_is_hosting_group($g){
  $hay = mb_strtolower(trim(($g['name'] ?? '').' '.($g['slug'] ?? '').' '.($g['type'] ?? '')),'UTF-8');
  foreach(['hosting','host','cpanel','linux','radyo','radio','e-mail','email','mail','sunucu kurulumu','server kurulumu'] as $needle){
    if(str_contains($hay,$needle)) return true;
  }
  return false;
}
?>
<?php
$aoProductHeroKey = $selectedGroup ? ('urun-grubu/' . ($selectedGroup['slug'] ?? '')) : 'urunler';
$aoProductHeroTitle = $selectedGroup ? ($selectedGroup['name'] ?? 'Ürünler') : 'Ahost One Ürünleri';
$aoProductHeroDescription = $selectedGroup ? ($selectedGroup['description'] ?? 'Bu gruba ait aktif ürünler.') : 'Hosting, VPS, domain, Site Builder, Mobile Builder, web tasarım ve dijital hizmet paketlerini tek vitrinde inceleyin.';
$aoProductManagedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render($aoProductHeroKey, [
  'kicker' => 'Ürün Merkezi',
  'title' => $aoProductHeroTitle,
  'description' => $aoProductHeroDescription,
  'primary_label' => 'Sipariş / Teklif Başlat',
  'primary_url' => 'teklif',
  'secondary_label' => 'Domain Sorgula',
  'secondary_url' => 'domain',
]) : '';
?><section class="platform-page product-catalog-page">
  <?php if($aoProductManagedHero): ?>
    <?= $aoProductManagedHero ?>
  <?php else: ?>
  <div class="platform-hero product-catalog-hero">
    <div>
      <span class="badge">Ürün Merkezi</span>
      <h1><?= $selectedGroup ? e($selectedGroup['name']) : 'Ahost One Ürünleri' ?></h1>
      <p><?= $selectedGroup ? e($selectedGroup['description'] ?? 'Bu gruba ait aktif ürünler.') : 'Hosting, VPS, domain, Site Builder, Mobile Builder, web tasarım ve dijital hizmet paketlerini tek vitrinde inceleyin.' ?></p>
      <div class="hero-actions"><a class="site-btn" href="<?= url('teklif') ?>">Sipariş / Teklif Başlat</a><a class="site-btn secondary ao-order-btn" href="<?= url('domain') ?>">Domain Sorgula</a></div>
    </div>
    <div class="platform-visual"></div>
  </div>
  <?php endif; ?>

  <?php if($groups): ?>
  <div class="ao-product-group-pills" data-builder-block="product-category-filter">
    <label class="ao-product-group-select">
      <select aria-label="Ürün kategorisi" onchange="if(this.value) window.location.href=this.value">
        <option value="<?= e(url('urunler')) ?>" <?= !$selectedGroup ? 'selected' : '' ?>>Tüm Ürünler</option>
        <?php foreach($groups as $g): if((int)($g['product_count'] ?? 0) <= 0) continue; ?>
          <option value="<?= e(url('urun-grubu/'.$g['slug'])) ?>" <?= ($selectedGroup && $selectedGroup['slug']===$g['slug']) ? 'selected' : '' ?>><?= e($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <?php endif; ?>

  <?php if(!$products): ?>
    <div class="platform-card ao-empty-products"><h3>Henüz yayında ürün yok</h3><p>Yeni paketler hazırlandığında bu alanda listelenecek.</p><a href="<?= url('teklif') ?>">Bilgi Al →</a></div>
  <?php else: ?>
    <div class="platform-grid ao-product-grid">
      <?php foreach($products as $p):
        $priceOptions=function_exists('ao_v2524_product_price_options') ? ao_v2524_product_price_options($p) : [ao_v2335_primary_price($p)];
        $price=$priceOptions[0] ?? ao_v2335_primary_price($p);
        $features=ao_v2339_product_features($p);
        $detailUrl=url('urun/'.$p['slug']);
      ?>
        <div class="platform-card ao-product-card" data-product-card data-product-id="<?= (int)$p['id'] ?>" data-builder-block="product-<?= (int)$p['id'] ?>"<?= ao_v2339_product_style($p) ?>>
          <?php if(!empty($p['card_image_url'])): ?><div class="ao-product-image" style="background-image:url('<?= e($p['card_image_url']) ?>')"></div><?php endif; ?>
          <div class="ao-product-icon"><?= e($p['card_icon'] ?: ao_v2339_product_icon($p['type'] ?? $p['group_name'] ?? '')) ?></div>
          <span class="badge"><?= e($p['group_name'] ?? 'Ürün') ?></span>
          <h3 data-product-field="name"><?= e($p['name']) ?></h3>
          <p data-product-field="short_description"><?= e(mb_substr(strip_tags((string)($p['short_description'] ?? $p['description'] ?? '')),0,150)) ?></p>
          <ul class="ao-product-features"><?php foreach($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
          <div class="ao-product-price" data-product-field="price" data-product-price-label>
            <?= ao_format_price_try((float)($price['amount'] ?? 0), ($price['amount'] ?? 0) > 0 ? ao_v2335_cycle_label($price['cycle'] ?? 'monthly') : null) ?>
          </div>
          <?php if(count($priceOptions)>1): ?>
            <label class="ao-product-cycle-picker">
              <span>Fatura periyodu</span>
              <select data-product-cycle-select>
                <?php foreach($priceOptions as $opt):
                  $cycle=(string)($opt['cycle'] ?? 'monthly');
                  $label=ao_format_price_try((float)($opt['amount'] ?? 0), ($opt['amount'] ?? 0) > 0 ? ao_v2335_cycle_label($cycle) : null);
                  $labelPlain=trim(preg_replace('~\s+~',' ',strip_tags($label)));
                ?>
                  <option value="<?= e($cycle) ?>" data-price-label="<?= e($labelPlain) ?>"><?= e(ao_v2335_cycle_label($cycle)) ?> - <?= e($labelPlain) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php endif; ?>
          <div class="hero-actions ao-product-actions"><a class="site-btn" href="<?= e($detailUrl) ?>">İncele</a><a class="site-btn secondary ao-order-btn" data-product-order-link data-base-url="<?= e($detailUrl) ?>" href="<?= e($detailUrl.'?cycle='.rawurlencode((string)($price['cycle'] ?? 'monthly')).'#siparis-bilgileri') ?>">Satın Al</a></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<script>
(function(){
  document.querySelectorAll('.ao-product-card').forEach(function(card){
    var select=card.querySelector('[data-product-cycle-select]');
    var link=card.querySelector('[data-product-order-link]');
    var price=card.querySelector('[data-product-price-label]');
    if(!select || !link) return;
    function sync(){
      var option=select.options[select.selectedIndex];
      var cycle=select.value || 'monthly';
      var base=link.dataset.baseUrl || link.getAttribute('href').split('?')[0];
      link.href=base+'?cycle='+encodeURIComponent(cycle)+'#siparis-bilgileri';
      if(price && option && option.dataset.priceLabel) price.textContent=option.dataset.priceLabel;
    }
    select.addEventListener('change', sync);
    sync();
  });
})();
</script>
