<?php
$homeGroups = [];
$homeProducts = [];
try {
    $homeGroups = function_exists('ao_v2335_product_groups') ? ao_v2335_product_groups() : [];
    $homeProducts = function_exists('ao_v2335_products') ? ao_v2335_products() : [];
} catch (Throwable $e) {
    $homeGroups = [];
    $homeProducts = [];
}

if (!function_exists('ao_home_product_icon')) {
    function ao_home_product_icon($type) {
        $type = mb_strtolower((string)$type, 'UTF-8');
        if (str_contains($type, 'hosting')) return 'H';
        if (str_contains($type, 'server') || str_contains($type, 'vps')) return 'VPS';
        if (str_contains($type, 'domain')) return 'DNS';
        if (str_contains($type, 'ssl')) return 'SSL';
        if (str_contains($type, 'mobile') || str_contains($type, 'mobil')) return 'APP';
        if (str_contains($type, 'web') || str_contains($type, 'site')) return 'WEB';
        if (str_contains($type, 'seo')) return 'SEO';
        if (str_contains($type, 'market')) return 'PRO';
        return 'SaaS';
    }
}

if (!function_exists('ao_home_feature_list')) {
    function ao_home_feature_list($product) {
        $raw = '';
        foreach (['features', 'features_json', 'short_features', 'description', 'short_description'] as $key) {
            if (!empty($product[$key])) {
                $raw = (string)$product[$key];
                break;
            }
        }

        $features = [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item)) $item = $item['text'] ?? $item['title'] ?? '';
                $item = trim(strip_tags((string)$item));
                if ($item !== '') $features[] = $item;
            }
        } else {
            $parts = preg_split('/[\r\n•\|]+|(?<=[.!?])\s+/u', strip_tags($raw)) ?: [];
            foreach ($parts as $part) {
                $part = trim((string)$part, " \t\n\r\0\x0B-");
                if ($part !== '' && mb_strlen($part, 'UTF-8') > 4) $features[] = $part;
            }
        }

        if (!$features) {
            $features = ['Hızlı kurulum', 'Yönetilebilir panel', '7/24 destek', 'Güvenli altyapı'];
        }
        return array_slice(array_values(array_unique($features)), 0, 4);
    }
}

if (!function_exists('ao_home_inline_price_try')) {
    function ao_home_inline_price_try($tryAmount) {
        $tryAmount = (float)$tryAmount;
        $current = function_exists('ao_current_currency') ? ao_current_currency() : 'TRY';
        $options = function_exists('ao_currency_options') ? ao_currency_options() : ['TRY' => ['symbol' => '₺', 'rate' => 1.0]];
        if (!isset($options[$current])) $current = 'TRY';
        $rate = (float)($options[$current]['rate'] ?? 1.0);
        $symbol = $options[$current]['symbol'] ?? $current;
        if ($current === 'TRY') {
            $display = number_format($tryAmount, 2, ',', '.') . ' ' . $symbol;
        } else {
            $shown = $rate > 0 ? ($tryAmount / $rate) : $tryAmount;
            $display = $symbol . number_format($shown, 2, '.', ',');
        }
        return '<span class="ao-price ao-home-domain-start-price" data-price-base="' . e(number_format($tryAmount, 2, '.', '')) . '">' . e($display) . '</span>';
    }
}

$homeFeaturedProducts = array_slice($homeProducts, 0, 6);
$domainPrice = static function($tld) {
    return function_exists('ao_domain_sale_price') ? (float)ao_domain_sale_price($tld) : 0.0;
};
$homeDomainStartingUsd = 0.0;
try {
    $homeDomainStartingUsd = (float)db()->query('SELECT MIN(sale_usd) FROM domain_price_cache WHERE sale_usd > 0')->fetchColumn();
} catch (Throwable $e) {
    $homeDomainStartingUsd = 0.0;
}
if ($homeDomainStartingUsd <= 0 && function_exists('ao_domain_sale_price')) {
    $homeDomainUsdPrices = [];
    foreach (['com', 'net', 'org', 'com.tr', 'net.tr'] as $homeDomainTld) {
        $homeDomainUsdPrice = (float)ao_domain_sale_price($homeDomainTld, 'USD');
        if ($homeDomainUsdPrice > 0) $homeDomainUsdPrices[] = $homeDomainUsdPrice;
    }
    if ($homeDomainUsdPrices) $homeDomainStartingUsd = min($homeDomainUsdPrices);
}
if ($homeDomainStartingUsd <= 0) $homeDomainStartingUsd = 0.99;
$homeDomainStartingTry = $homeDomainStartingUsd;
if (function_exists('ao_currency_rate')) {
    $homeDomainStartingTry = round($homeDomainStartingUsd * (float)ao_currency_rate('USD', 'TRY'), 2);
}
$homeDomainStartingPriceHtml = ao_home_inline_price_try($homeDomainStartingTry);
?>
<section class="ao-site-content ao-home-page">
  <div class="ao-content-shell">
    <div class="e-domain-card ao-home-domain-card" data-domain-widget data-domain-compact>
      <h2><?= $homeDomainStartingPriceHtml ?>'dan başlayan fiyatlarla harika bir domain kaydedin</h2>
      <form method="get" action="<?= url('domain') ?>" data-domain-search-form>
        <div class="searchline">
          <input name="domain" data-domain-input placeholder="ornekdomain.com" autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault();this.closest('form').querySelector('[data-domain-search]').click();return false;}">
          <button type="submit" data-domain-search>Sorgula</button>
        </div>
        <div class="e-tld-row">
          <?php foreach (['com', 'net', 'com.tr', 'org'] as $tld): $price = $domainPrice($tld); ?>
            <span>.<?= e($tld) ?> <span class="ao-price" data-price-base="<?= e(number_format($price, 2, '.', '')) ?>">₺<?= e(number_format($price, 2, ',', '.')) ?></span></span>
          <?php endforeach; ?>
        </div>
        <details class="home-domain-tools" data-home-domain-tools>
          <summary>Araçlar</summary>
          <div class="home-domain-tools-menu">
            <button type="button" data-domain-tool="whois">WHOIS</button>
            <button type="button" data-domain-tool="dns">DNS</button>
            <button type="button" data-domain-tool="ssl">SSL</button>
            <button type="button" data-domain-tool="valuation">Değerleme</button>
          </div>
        </details>
      </form>
      <div class="ao-domain-search-result" data-domain-search-result></div>
    </div>

    <div class="ao-home-slider-hero-row">
      <?php if (function_exists('ao_prism_render_site_slider')) echo ao_prism_render_site_slider(); ?>

      <div class="e-site-hero">
        <div class="e-site-hero-copy">
          <h1>İşinizi büyüten tüm dijital çözümler <span class="ao-gradient-text">tek merkezde</span>.</h1>
          <p>Hosting, domain, sunucu, web tasarım, mobil uygulama, SEO ve dijital hizmetleri modern bir satın alma ve yönetim deneyimiyle keşfedin.</p>
          <div class="hero-actions">
            <a class="u2-btn" href="#tum-urunler">Ürünleri Keşfet</a>
            <a class="u2-btn dark" href="<?= url('referanslar') ?>">Referansları İncele</a>
            <a class="u2-btn soft" href="<?= url('domain') ?>">Domain Sorgula</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ao-site-content ao-home-metrics">
  <div class="ao-content-shell">
    <section class="e-stats-strip">
      <div><strong><?= count($homeProducts) ?>+</strong><span>Düzenlenebilir ürün</span></div>
      <div><strong><?= count($homeGroups) ?></strong><span>Çözüm kategorisi</span></div>
      <div><strong>7/24</strong><span>Merkezi yönetim</span></div>
      <div><strong>Premium</strong><span>SaaS müşteri deneyimi</span></div>
    </section>

    <section class="home-packages-showcase" id="tum-urunler">
      <div class="u2-section-title">
        <div>
          <span class="u2-kicker">Ürün ve hizmet paketleri</span>
          <h2>İhtiyacınıza uygun çözümleri karşılaştırın.</h2>
        </div>
        <a class="u2-btn soft" href="<?= url('urunler') ?>">Tüm Ürünler</a>
      </div>

      <?php if (!$homeFeaturedProducts): ?>
        <div class="ao-empty">Henüz öne çıkarılacak paket bulunamadı.</div>
      <?php else: ?>
        <div class="home-package-grid">
          <?php foreach ($homeFeaturedProducts as $product): ?>
            <?php
              $price = function_exists('ao_v2335_primary_price') ? ao_v2335_primary_price($product) : ['amount' => 0, 'cycle' => 'monthly'];
              $amount = (float)($price['amount'] ?? 0);
              $features = ao_home_feature_list($product);
            ?>
            <article class="home-package-card" data-product-card data-product-id="<?= (int)($product['id'] ?? 0) ?>" data-builder-block="home-product-<?= (int)($product['id'] ?? 0) ?>">
              <div class="home-package-top">
                <span class="home-product-icon"><?= e(ao_home_product_icon((string)($product['type'] ?? $product['name'] ?? ''))) ?></span>
                <small><?= e($product['group_name'] ?? $product['type'] ?? 'Paket') ?></small>
              </div>
              <h3 data-product-field="name"><?= e($product['name'] ?? 'Paket') ?></h3>
              <p data-product-field="short_description"><?= e(mb_substr(strip_tags((string)($product['short_description'] ?? $product['description'] ?? '')), 0, 120, 'UTF-8')) ?></p>
              <ul>
                <?php foreach ($features as $feature): ?><li><?= e(mb_substr($feature, 0, 80, 'UTF-8')) ?></li><?php endforeach; ?>
              </ul>
              <div class="home-package-price" data-product-field="price" data-product-price-label>
                <?= function_exists('ao_format_price_try') ? ao_format_price_try($amount, $amount > 0 && function_exists('ao_v2335_cycle_label') ? ao_v2335_cycle_label($price['cycle'] ?? 'monthly') : null) : e(number_format($amount, 2, ',', '.').' ₺') ?>
              </div>
              <div class="home-product-actions">
                <a class="inspect" href="<?= url('urun/'.($product['slug'] ?? '')) ?>">İncele</a>
                <a class="buy" href="<?= url('urun/'.($product['slug'] ?? '').'#siparis-bilgileri') ?>">Sepete Ekle</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="home-reference-cta">
      <div>
        <span class="u2-kicker">Web ve Android projeleri</span>
        <h2>Fikirden yayına uzanan seçili çalışmalar</h2>
        <p>Web sitesi ve Android uygulama referanslarını ayrı portföy vitrininde inceleyin.</p>
      </div>
      <a class="u2-btn" href="<?= url('referanslar') ?>">Referansları Gör</a>
    </section>

    <section class="e-section">
      <div class="u2-section-title">
        <div>
          <span class="u2-kicker">Premium müşteri deneyimi</span>
          <h2>İncelemeden satın almaya kadar sade bir yolculuk</h2>
        </div>
      </div>
      <div class="e-testimonials">
        <div class="u2-card e-service"><h3>Şeffaf katalog</h3><p>Ürün grupları, içerikler ve güncel fiyatlar tek sayfada karşılaştırılır.</p></div>
        <div class="u2-card e-service"><h3>Detaylı inceleme</h3><p>Her ürünün açıklaması, özellikleri ve fiyat seçenekleri ayrı detay sayfasında sunulur.</p></div>
        <div class="u2-card e-service"><h3>Kolay takip</h3><p>Sipariş, yenileme ve destek adımları müşteriye anlaşılır bir akışla sunulur.</p></div>
      </div>
    </section>
  </div>
</section>

