<?php
$cart = $_SESSION['ao_cart'] ?? $_SESSION['cart'] ?? [];
$total = 0.0;
$cycleLabels = [
  'one_time' => 'Tek Seferlik',
  'onetime' => 'Tek Seferlik',
  'monthly' => 'Aylık',
  'quarterly' => '3 Aylık',
  'semiannually' => '6 Aylık',
  'annually' => 'Yıllık',
  'biennially' => '2 Yıllık',
  'triennially' => '3 Yıllık',
];
$domainLabels = [
  'register' => ['Yeni domain kaydet', 'Boş bir domain seçip registrar üzerinden otomatik kaydedilsin.'],
  'transfer' => ['Domain transfer et', 'Transfer kodu girerek mevcut domaini taşıyın.'],
  'existing' => ['Mevcut domainimi kullan', 'Domain bende, hosting hizmetine tanımlamak istiyorum.'],
  'dns' => ['Mevcut domainimi bu hostinge yönlendireceğim', 'Domain sağlayıcımda nameserver veya DNS kayıtlarını bu hosting hizmetine göre güncelleyeceğim.'],
];
$hostingChoices = [
  'add_hosting' => ['Bu domain için yeni hosting alacağım', 'Ödeme öncesi hosting paketlerinden birini sepete ekleyerek domainle birlikte kullanacağım.'],
  'use_existing_service' => ['Mevcut Ahost hostingime bağla', 'Ahost hesabımdaki aktif hosting hizmetime bu domaini tanımlamak istiyorum.'],
  'external_dns' => ['Dış hostingime yönlendireceğim', 'Nameserver bilgilerini girerek domaini farklı bir hosting sağlayıcısına yönlendireceğim.'],
];
function ao_cart_cycles_v249($slug, $current) {
  if (str_starts_with((string)$slug, 'domain:') || str_starts_with((string)$slug, 'domain-transfer-')) return [['cycle' => $current ?: 'annually', 'price' => null, 'currency' => 'TRY']];
  try {
    $q = db()->prepare('SELECT pp.cycle, pp.price, pp.price_try, pp.price_usd, pp.currency FROM product_pricing pp JOIN products p ON p.id=pp.product_id WHERE p.slug=? AND pp.is_active=1 AND pp.price>=0 ORDER BY FIELD(pp.cycle,"monthly","annually","biennially","triennially","quarterly","semiannually","one_time","onetime"), pp.id');
    $q->execute([$slug]);
    $rows = $q->fetchAll();
    if ($rows) {
      foreach ($rows as &$row) {
        $try = (float)($row['price_try'] ?? 0);
        if ($try <= 0 && (float)($row['price_usd'] ?? 0) > 0 && function_exists('ao_v23_price_try')) $try = (float)ao_v23_price_try((float)$row['price_usd'], 'USD');
        if ($try <= 0 && (float)($row['price'] ?? 0) > 0) $try = strtoupper((string)($row['currency'] ?? 'TRY')) === 'TRY' || !function_exists('ao_v23_price_try') ? (float)$row['price'] : (float)ao_v23_price_try((float)$row['price'], (string)$row['currency']);
        $row['price'] = $try;
        $row['currency'] = 'TRY';
      }
      unset($row);
      return $rows;
    }
  } catch (Throwable $e) {}
  return [['cycle' => $current ?: 'monthly', 'price' => null, 'currency' => 'TRY']];
}
function ao_cart_addons_v249($slug) {
  if (str_starts_with((string)$slug, 'domain:') || str_starts_with((string)$slug, 'domain-transfer-')) return [];
  try {
    $q = db()->prepare('SELECT a.addon_key AS `key`, a.name, a.price, a.currency, a.description FROM product_checkout_addons a JOIN products p ON p.id=a.product_id WHERE p.slug=? AND a.is_active=1 ORDER BY a.sort_order,a.id');
    $q->execute([$slug]);
    $rows = $q->fetchAll();
    if ($rows) {
      foreach ($rows as &$row) {
        $price = (float)($row['price'] ?? 0);
        $currency = strtoupper((string)($row['currency'] ?? 'TRY'));
        if ($price > 0 && $currency !== 'TRY' && function_exists('ao_v23_price_try')) {
          $price = (float)ao_v23_price_try($price, $currency);
          $currency = 'TRY';
        }
        $row['price'] = $price;
        $row['currency'] = $currency;
      }
      unset($row);
      return $rows;
    }
  } catch (Throwable $e) {}
  return [];
}
function ao_cart_money_symbol_v249($currency = 'TRY') {
  $currency = strtoupper((string)$currency);
  return $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : '₺');
}
function ao_cart_is_domain_item_v249($slug, $item) {
  return str_starts_with((string)$slug, 'domain:') || str_starts_with((string)$slug, 'domain-transfer-') || (($item['meta']['type'] ?? '') === 'domain');
}
function ao_cart_is_hosting_item_v249($item) {
  $haystack = strtolower(($item['group'] ?? '') . ' ' . ($item['slug'] ?? '') . ' ' . ($item['name'] ?? ''));
  foreach (['hosting','sunucu','cpanel','wordpress','reseller','vps'] as $needle) if (str_contains($haystack, $needle)) return true;
  return false;
}
function ao_cart_active_promo_v249($code, $subtotal) {
  $code = strtoupper(trim((string)$code));
  if ($code === '') return ['code'=>'', 'valid'=>false, 'discount'=>0.0, 'message'=>''];
  try {
    $q = db()->prepare("SELECT * FROM product_promotions WHERE UPPER(code)=? AND is_active=1 LIMIT 1");
    $q->execute([$code]);
    $promo = $q->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$promo) return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon kodu geçerli değil.'];
    $today = date('Y-m-d');
    if (!empty($promo['starts_at']) && $promo['starts_at'] > $today) return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon henüz başlamadı.'];
    if (!empty($promo['ends_at']) && $promo['ends_at'] < $today) return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon süresi dolmuş.'];
    if ((float)($promo['min_total'] ?? 0) > $subtotal) return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon için minimum sepet tutarı karşılanmıyor.'];
    if ((int)($promo['usage_limit'] ?? 0) > 0 && (int)($promo['used_count'] ?? 0) >= (int)$promo['usage_limit']) return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon kullanım limiti dolmuş.'];
    $value = max(0, (float)($promo['discount_value'] ?? 0));
    $discount = strtolower((string)($promo['discount_type'] ?? 'percent')) === 'amount' ? $value : ($subtotal * min(100, $value) / 100);
    return ['code'=>$code, 'valid'=>true, 'discount'=>min($subtotal, round($discount, 2)), 'message'=>trim((string)($promo['title'] ?? 'Kupon indirimi')) ?: 'Kupon indirimi'];
  } catch (Throwable $e) {
    return ['code'=>$code, 'valid'=>false, 'discount'=>0.0, 'message'=>'Kupon şu anda kontrol edilemedi.'];
  }
}
function ao_cart_tax_rate_v249() {
  $enabled = strtolower((string)(function_exists('admin_setting') ? admin_setting('tax_enabled', 'on') : 'on'));
  if (in_array($enabled, ['0','off','false','no','disabled'], true)) return 0.0;
  return max(0.0, (float)(function_exists('admin_setting') ? admin_setting('vat_rate', '20') : 20));
}
$hasDomain = false;
$hasHosting = false;
foreach ($cart as $slug => $item) {
  if (ao_cart_is_domain_item_v249($slug, $item)) $hasDomain = true;
  if (ao_cart_is_hosting_item_v249($item)) $hasHosting = true;
}
$cartStartGroups = [];
$cartStartHostingProducts = [];
try {
  if (function_exists('ao_v2335_product_groups')) {
    $allGroups = ao_v2335_product_groups();
    $wanted = ['web-tasarim', 'mobil-uygulama', 'sitebuilder', 'mobilebuilder', 'seo', 'dijital-hizmetler'];
    foreach ($wanted as $wantedSlug) {
      foreach ($allGroups as $g) {
        if (($g['slug'] ?? '') === $wantedSlug) { $cartStartGroups[] = $g; break; }
      }
    }
  }
  if (function_exists('ao_v2335_products')) {
    $cartStartHostingProducts = array_slice(ao_v2335_products('hosting'), 0, 4);
  }
} catch (Throwable $e) { $cartStartGroups = []; $cartStartHostingProducts = []; }
if (!$cartStartGroups) {
  $cartStartGroups = [
    ['name' => 'Web Tasarım', 'slug' => 'web-tasarim', 'description' => 'Kurumsal web sitesi, e-ticaret ve özel web projeleri.'],
    ['name' => 'Mobil Uygulama', 'slug' => 'mobil-uygulama', 'description' => 'Android/iOS uygulama tasarım ve geliştirme hizmetleri.'],
    ['name' => 'Site Builder', 'slug' => 'sitebuilder', 'description' => 'Hazır site ve yayın paketleri.'],
  ];
}
$cartStartGroupLabels = [
  'web-tasarim' => 'Web projesi',
  'mobil-uygulama' => 'Mobil proje',
  'sitebuilder' => 'Hazır site',
  'mobilebuilder' => 'Mobil builder',
  'seo' => 'Büyüme',
  'dijital-hizmetler' => 'Dijital servis',
  'marketplace' => 'Pazar yeri',
];
?>
<section class="ao-site-content ao-cart-page">
  <div class="ao-content-shell">
    <?php if (!$cart): ?>
      <div class="ao-prism-empty-cart">
        <div class="ao-prism-empty-cart__steps" aria-label="Sipariş akışı">
          <span class="is-active"><b>1</b> Başlangıç</span>
          <span><b>2</b> Seçim</span>
          <span><b>3</b> Yapılandırma</span>
          <span><b>4</b> Ödeme</span>
        </div>

        <div class="ao-cart-start-panel">
          <section class="ao-cart-start-domain">
            <small>Domain</small>
            <h2>Alan adınızı sorgulayın</h2>
            <p>Boş domain sepete eklendiğinde sonraki adımda hosting ve ek paket seçenekleri açılır.</p>
            <form method="get" action="<?= url('domain') ?>">
              <input name="domain" placeholder="ornekdomain.com" autocomplete="off">
              <button type="submit">Sorgula</button>
            </form>
          </section>

          <section class="ao-cart-start-hosting">
            <div class="ao-cart-start-heading">
              <small>Hosting</small>
              <a href="<?= url('hosting') ?>">Tüm paketler</a>
            </div>
            <div class="ao-cart-hosting-list">
              <?php if ($cartStartHostingProducts): ?>
                <?php foreach ($cartStartHostingProducts as $hp): $price = function_exists('ao_v2335_primary_price') ? ao_v2335_primary_price($hp) : ['amount' => (float)($hp['price'] ?? 0), 'currency' => 'TRY', 'cycle' => $hp['billing_cycle'] ?? 'monthly']; ?>
                  <a class="ao-cart-hosting-card" href="<?= url('cart/add?product=' . rawurlencode((string)$hp['slug'])) ?>">
                    <span><?= e($hp['group_name'] ?? 'Hosting') ?></span>
                    <strong><?= e($hp['name'] ?? 'Hosting Paketi') ?></strong>
                    <em><?= e(mb_substr(strip_tags((string)($hp['short_description'] ?? 'Paket seçildikten sonra domain adımı açılır.')), 0, 82, 'UTF-8')) ?></em>
                    <b>₺<?= number_format((float)($price['amount'] ?? 0), 2, ',', '.') ?></b>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <a class="ao-cart-hosting-card" href="<?= url('hosting') ?>"><span>Hosting</span><strong>Hosting paketlerini inceleyin</strong><em>Paket seçildikten sonra domain adımı açılır.</em><b>Başla</b></a>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <div class="ao-prism-empty-cart__grid ao-prism-empty-cart__grid--services">
          <?php foreach ($cartStartGroups as $idx => $group): ?>
            <a class="<?= $idx === 0 ? 'primary' : '' ?>" href="<?= url('urun-grubu/' . ($group['slug'] ?? '')) ?>">
              <small><?= e($cartStartGroupLabels[$group['slug'] ?? ''] ?? 'Çözüm merkezi') ?></small>
              <strong><?= e($group['name'] ?? 'Hizmet') ?></strong>
              <em><?= e(mb_substr(strip_tags((string)($group['description'] ?? (($group['name'] ?? 'Hizmet') . ' paketlerini inceleyin.'))), 0, 110, 'UTF-8')) ?></em>
            </a>
          <?php endforeach; ?>
          <a href="<?= url('teklif') ?>">
            <small>Özel ihtiyaç</small>
            <strong>Teklif iste</strong>
            <em>Hazır paketler yetmiyorsa web, mobil veya özel yazılım talebinizi gönderin.</em>
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="cart-flow-steps" aria-label="Sipariş adımları">
        <a class="<?= $hasDomain ? 'is-done' : 'is-active' ?>" href="<?= url('domain') ?>"><span>1</span><b>Domain</b><small>Kayıt, transfer veya mevcut domain</small></a>
        <a class="<?= $hasHosting ? 'is-done' : 'is-active' ?>" href="<?= url('hosting') ?>"><span>2</span><b>Hosting</b><small>Paket ve kaynak seçimi</small></a>
        <a class="is-active" href="<?= url('cart') ?>"><span>3</span><b>Eklentiler</b><small>SSL, disk, trafik, yedek</small></a>
        <a href="<?= url('checkout') ?>"><span>4</span><b>Ödeme</b><small>Giriş, kayıt ve ödeme</small></a>
      </div>

      <form method="post" action="<?= url('cart/update') ?>" data-cart-form>
        <?= csrf_field() ?>
        <div class="cart-smart-list">
          <?php foreach ($cart as $slug => $item):
            $qty = max(1, (int)($item['qty'] ?? 1));
            $price = (float)($item['price'] ?? 0);
            $cycle = $item['cycle'] ?? 'monthly';
            $selectedAddons = is_array($item['addons'] ?? null) ? $item['addons'] : [];
            $addons = ao_cart_addons_v249($slug);
            $addonTotal = 0.0;
            foreach ($addons as $ad) if (in_array($ad['key'], $selectedAddons, true)) $addonTotal += (float)($ad['price'] ?? 0);
            $isDomainItem = ao_cart_is_domain_item_v249($slug, $item);
            $isHostingItem = ao_cart_is_hosting_item_v249($item);
            $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
            $years = max(1, min(10, (int)($meta['registration_years'] ?? 1)));
            $cycles = ao_cart_cycles_v249($slug, $cycle);
            if (!$isDomainItem && $cycles) {
              $cycleValues = array_map(static fn($r) => (string)($r['cycle'] ?? ''), $cycles);
              if (!in_array((string)$cycle, $cycleValues, true)) {
                $cycle = (string)($cycles[0]['cycle'] ?? $cycle);
                $price = (float)($cycles[0]['price'] ?? $price);
                $item['cycle'] = $cycle;
                $item['price'] = $price;
                $item['currency'] = 'TRY';
                $_SESSION['ao_cart'][$slug]['cycle'] = $cycle;
                $_SESSION['ao_cart'][$slug]['price'] = $price;
                $_SESSION['ao_cart'][$slug]['currency'] = 'TRY';
              }
            }
            $line = (($price * ($isDomainItem ? $years : 1)) + $addonTotal) * $qty;
            $total += $line;
            $domainAction = $item['domain_action'] ?? ($isDomainItem ? 'register' : 'existing');
            $hostingChoice = $meta['hosting_choice'] ?? 'add_hosting';
            $nameservers = is_array($meta['nameservers'] ?? null) ? $meta['nameservers'] : [];
          ?>
            <article class="cart-order-card" data-cart-item data-domain-item="<?= $isDomainItem ? '1' : '0' ?>" data-hosting-item="<?= $isHostingItem ? '1' : '0' ?>">
              <header class="cart-order-head">
                <div class="cart-order-title">
                  <small><?= e($item['group'] ?? ($isDomainItem ? 'Domain' : 'Ürün')) ?></small>
                  <h3><?= e($item['name'] ?? $slug) ?></h3>
                  <p><?= $isDomainItem ? e($years . ' yıl kayıt') : e($cycleLabels[$cycle] ?? $cycle) ?> · <?= e(ao_cart_money_symbol_v249($item['currency'] ?? 'TRY')) ?><?= number_format($price, 2, ',', '.') ?></p>
                </div>
                <div class="cart-order-price">
                  <span>Satır toplamı</span>
                  <strong><?= e(ao_cart_money_symbol_v249($item['currency'] ?? 'TRY')) ?><?= number_format($line, 2, ',', '.') ?></strong>
                  <a href="<?= url('cart/remove?product=' . rawurlencode($slug)) ?>">Sil</a>
                </div>
              </header>

              <?php if (!empty($meta['custom_fields']) && is_array($meta['custom_fields'])): ?>
                <div class="ao-cart-meta">
                  <?php foreach ($meta['custom_fields'] as $cf): ?>
                    <div>
                      <span><?= e($cf['label'] ?? $cf['key'] ?? 'Özel Alan') ?></span>
                      <strong><?= e((string)($cf['value'] ?? '-')) ?></strong>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="cart-config-grid">
                <?php if ($isDomainItem): ?>
                  <section class="cart-config-panel cart-domain-years">
                    <h2>Alan Adı Yapılandırma</h2>
                    <label>Kayıt süresi
                      <select name="registration_years[<?= e($slug) ?>]">
                        <?php for($y=1;$y<=10;$y++): ?><option value="<?= $y ?>" <?= $years===$y?'selected':'' ?>><?= $y ?> yıl</option><?php endfor; ?>
                      </select>
                    </label>
                    <p class="muted">Ödeme tamamlanınca domain registrar üzerinden otomatik kayıt için kuyruğa alınır.</p>
                  </section>
                  <section class="cart-config-panel">
                    <h2>Hosting Seçimi</h2>
                    <div class="cart-whmcs-choice-list">
                      <?php foreach($hostingChoices as $hk=>$row): ?>
                        <label class="cart-whmcs-choice"><input type="radio" name="hosting_choice[<?= e($slug) ?>]" value="<?= e($hk) ?>" <?= $hostingChoice===$hk?'checked':'' ?> data-hosting-choice><span><strong><?= e($row[0]) ?></strong><small><?= e($row[1]) ?></small></span></label>
                      <?php endforeach; ?>
                    </div>
                    <div class="cart-nameserver-fields" data-nameserver-fields>
                      <label>Nameserver 1<input name="nameservers[<?= e($slug) ?>][]" value="<?= e($nameservers[0] ?? '') ?>" placeholder="ns1.ornek.com"></label>
                      <label>Nameserver 2<input name="nameservers[<?= e($slug) ?>][]" value="<?= e($nameservers[1] ?? '') ?>" placeholder="ns2.ornek.com"></label>
                    </div>
                  </section>
                <?php else: ?>
                  <section class="cart-config-panel">
                    <h2>Fatura Periyodu</h2>
                    <?php if(count($cycles)>1): ?>
                      <label class="cart-compact-select">
                        <span>Ödeme periyodu</span>
                        <select name="cycle[<?= e($slug) ?>]">
                          <?php foreach ($cycles as $r): $c = $r['cycle'] ?? 'monthly'; ?>
                            <option value="<?= e($c) ?>" <?= $c === $cycle ? 'selected' : '' ?>><?= e($cycleLabels[$c] ?? $c) ?><?= isset($r['price']) ? ' - '.e(ao_cart_money_symbol_v249($r['currency'] ?? 'TRY')).number_format((float)$r['price'], 2, ',', '.') : '' ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>
                    <?php else: ?>
                      <?php foreach ($cycles as $r): $c = $r['cycle'] ?? 'monthly'; ?>
                        <input type="hidden" name="cycle[<?= e($slug) ?>]" value="<?= e($c) ?>">
                        <p class="cart-static-cycle"><strong><?= e($cycleLabels[$c] ?? $c) ?></strong><span><?= isset($r['price']) ? e(ao_cart_money_symbol_v249($r['currency'] ?? 'TRY')).number_format((float)$r['price'], 2, ',', '.') : 'Mevcut fiyat' ?></span></p>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </section>
                  <?php if ($isHostingItem): ?>
                    <section class="cart-config-panel">
                      <h2>Domain Seçimi</h2>
                      <div class="cart-whmcs-choice-list">
                        <?php foreach ($domainLabels as $dk => $dl): ?>
                          <label class="cart-whmcs-choice"><input type="radio" name="domain_action[<?= e($slug) ?>]" value="<?= e($dk) ?>" <?= $domainAction === $dk ? 'checked' : '' ?> data-domain-action><span><strong><?= e($dl[0]) ?></strong><small><?= e($dl[1]) ?></small></span></label>
                        <?php endforeach; ?>
                      </div>
                      <div class="ao-domain-inputs" data-domain-inputs>
                        <label>Domain adı<input name="domain_name[<?= e($slug) ?>]" value="<?= e($item['domain_name'] ?? '') ?>" placeholder="ornek.com"></label>
                        <label class="ao-epp-field" data-epp-field>EPP / Transfer kodu<input name="epp_code[<?= e($slug) ?>]" value="<?= e($item['epp_code'] ?? '') ?>" placeholder="Transfer kodu"></label>
                      </div>
                      <div class="cart-nameserver-fields" data-nameserver-fields>
                        <label>Nameserver 1<input name="nameservers[<?= e($slug) ?>][]" value="<?= e($nameservers[0] ?? '') ?>" placeholder="ns1.ornek.com"></label>
                        <label>Nameserver 2<input name="nameservers[<?= e($slug) ?>][]" value="<?= e($nameservers[1] ?? '') ?>" placeholder="ns2.ornek.com"></label>
                      </div>
                    </section>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if ($addons): ?>
                  <section class="cart-config-panel cart-addon-panel">
                    <h2>Ek Paketler</h2>
                    <div class="cart-addon-grid">
                      <?php foreach ($addons as $ad): ?>
                        <label class="ao-addon-row"><span><input type="checkbox" name="addons[<?= e($slug) ?>][]" value="<?= e($ad['key']) ?>" <?= in_array($ad['key'], $selectedAddons, true) ? 'checked' : '' ?>><strong><?= e($ad['name']) ?></strong><small><?= e($ad['description'] ?? 'Siparişe ek hizmet olarak bağlanır.') ?></small></span><em>+ ₺<?= number_format((float)($ad['price'] ?? 0), 2, ',', '.') ?></em></label>
                      <?php endforeach; ?>
                    </div>
                  </section>
                <?php endif; ?>                <input type="hidden" name="qty[<?= e($slug) ?>]" value="<?= $qty ?>">
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <?php
          $couponCode = strtoupper(trim((string)($_SESSION['ao_cart_coupon'] ?? '')));
          $promo = ao_cart_active_promo_v249($couponCode, $total);
          $discount = (float)($promo['discount'] ?? 0);
          $taxRate = ao_cart_tax_rate_v249();
          $taxableTotal = max(0, $total - $discount);
          $taxTotal = round($taxableTotal * $taxRate / 100, 2);
          $grandTotal = $taxableTotal + $taxTotal;
        ?>
        <aside class="cart-smart-summary">
          <h2>Sipariş Özeti</h2>
          <div><span>Ara Toplam</span><strong>₺<?= number_format($total, 2, ',', '.') ?></strong></div>
          <label class="cart-coupon-field"><span>Kupon / indirim kodu</span><input name="coupon_code" value="<?= e($couponCode) ?>" placeholder="KUPONKODU"></label>
          <?php if($couponCode !== ''): ?><p class="cart-coupon-note <?= !empty($promo['valid']) ? 'is-valid' : 'is-invalid' ?>"><?= e($promo['message'] ?? '') ?></p><?php endif; ?>
          <?php if($discount > 0): ?><div><span>İndirim</span><strong>-₺<?= number_format($discount, 2, ',', '.') ?></strong></div><?php endif; ?>
          <div><span>Vergi<?= $taxRate > 0 ? ' (%'.e(number_format($taxRate, 2, ',', '.')).')' : '' ?></span><strong>₺<?= number_format($taxTotal, 2, ',', '.') ?></strong></div>
          <div class="grand"><span>Genel Toplam</span><strong>₺<?= number_format($grandTotal, 2, ',', '.') ?></strong></div>
          <div class="cart-summary-actions">
            <button class="ao-content-btn secondary" type="submit">Sepeti Güncelle</button>
            <a class="ao-content-btn soft" href="<?= url('domain') ?>">Alışverişe Devam Et</a>
            <a class="ao-content-btn" href="<?= url('checkout') ?>">Ödeme Adımına Geç</a>
          </div>
        </aside>
      </form>
    <?php endif; ?>
  </div>
</section>
<script>
(function(){
  function syncCard(card){
    var domainAction = card.querySelector('[data-domain-action]:checked');
    var epp = card.querySelector('[data-epp-field]');
    if(epp){
      var show = domainAction && domainAction.value === 'transfer';
      epp.hidden = !show;
      var input = epp.querySelector('input');
      if(input){ input.required = !!show; if(!show) input.value = ''; }
    }
    var ns = card.querySelector('[data-nameserver-fields]');
    if(ns){
      var hostingChoice = card.querySelector('[data-hosting-choice]:checked');
      var showNs = (domainAction && domainAction.value === 'dns') || (hostingChoice && hostingChoice.value === 'external_dns');
      ns.hidden = !showNs;
    }
  }
  document.querySelectorAll('[data-cart-item]').forEach(function(card){
    syncCard(card);
    card.addEventListener('change', function(e){
      if(e.target.matches('[data-domain-action],[data-hosting-choice]')) syncCard(card);
    });
  });
})();
</script>










