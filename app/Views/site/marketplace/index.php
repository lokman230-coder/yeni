<?php
if (function_exists('ao_schema_ensure_v1200')) {
  try { ao_schema_ensure_v1200(); } catch(Throwable $e) {}
}

$currentCustomer = function_exists('current_customer') ? current_customer() : null;
$featured = [];
$listings = [];
$categories = [];
$stats = ['active'=>0,'featured'=>0,'premium'=>0,'volume'=>0];
$q = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$budget = trim((string)($_GET['budget'] ?? ''));
$commissionDefault = (float)admin_setting('marketplace_default_commission_percent', '5');

try {
  $featured = db()->query("SELECT * FROM marketplace_listings WHERE status='active' AND is_featured=1 ORDER BY featured_until DESC, id DESC LIMIT 6")->fetchAll();
  $row = db()->query("SELECT COUNT(*) active, SUM(is_featured=1) featured, SUM(is_premium=1) premium, COALESCE(SUM(price),0) volume FROM marketplace_listings WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
  if ($row) $stats = array_merge($stats, $row);

  $where = ["status='active'"];
  $params = [];
  if ($q !== '') {
    $where[] = "(title LIKE ? OR domain_name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $like = '%'.$q.'%';
    array_push($params, $like, $like, $like, $like);
  }
  if ($type !== '') {
    $where[] = "listing_type=?";
    $params[] = $type;
  }
  if ($category !== '') {
    $where[] = "category=?";
    $params[] = $category;
  }
  if ($budget === '0-5000') {
    $where[] = 'price BETWEEN 0 AND 5000';
  } elseif ($budget === '5000-25000') {
    $where[] = 'price BETWEEN 5000 AND 25000';
  } elseif ($budget === '25000+') {
    $where[] = 'price >= 25000';
  }
  $sql = 'SELECT * FROM marketplace_listings WHERE '.implode(' AND ', $where).' ORDER BY is_featured DESC,is_premium DESC,id DESC LIMIT 48';
  $st = db()->prepare($sql);
  $st->execute($params);
  $listings = $st->fetchAll();
} catch(Throwable $e) {}

try {
  $categories = db()->query("SELECT * FROM marketplace_categories WHERE is_active=1 ORDER BY sort_order,id LIMIT 12")->fetchAll();
} catch(Throwable $e) {}

$catFallback = [
  ['name'=>'Domain','subtitle'=>'Alan adı alım satımı','icon'=>'🌐','listing_type'=>'domain'],
  ['name'=>'Web Tasarım','subtitle'=>'Kurumsal site hizmetleri','icon'=>'🎨','listing_type'=>'web_design'],
  ['name'=>'Logo Tasarım','subtitle'=>'Marka kimliği','icon'=>'✒️','listing_type'=>'logo_design'],
  ['name'=>'Mobil Uygulama','subtitle'=>'Android/iOS projeleri','icon'=>'📱','listing_type'=>'mobile_app'],
  ['name'=>'Hosting Hizmeti','subtitle'=>'Barındırma ve taşıma','icon'=>'🖥️','listing_type'=>'hosting'],
  ['name'=>'Yazılım / Script','subtitle'=>'Hazır yazılım ürünleri','icon'=>'💻','listing_type'=>'software'],
  ['name'=>'SEO Paketi','subtitle'=>'Büyüme ve trafik','icon'=>'📈','listing_type'=>'seo'],
  ['name'=>'Dijital İçerik','subtitle'=>'Metin, görsel ve kampanya','icon'=>'✨','listing_type'=>'digital_content'],
];

function ao_market_icon_rc14($type) {
  $type = (string)$type;
  if ($type === 'domain') return '🌐';
  if ($type === 'web_design') return '🎨';
  if ($type === 'mobile_app') return '📱';
  if ($type === 'software') return '💻';
  if ($type === 'hosting') return '🖥️';
  return '🛍️';
}
function ao_market_desc_rc14($l) {
  $d = trim((string)($l['description'] ?? ''));
  if ($d === '') $d = trim((string)($l['domain_name'] ?? 'Premium dijital ilan'));
  return mb_substr(strip_tags($d), 0, 150);
}
function ao_market_money_rc14($amount, $currency) {
  $currency = strtoupper((string)$currency);
  if ($currency === 'USD') return '$'.number_format((float)$amount, 2, '.', ',');
  if ($currency === 'EUR') return '€'.number_format((float)$amount, 2, '.', ',');
  return number_format((float)$amount, 2, ',', '.').' ₺';
}
function ao_market_category_subtitle_rc14($category) {
  $subtitle = trim((string)($category['subtitle'] ?? ''));
  if ($subtitle !== '') return $subtitle;
  $type = trim((string)($category['listing_type'] ?? ''));
  $labels = [
    'domain' => 'Alan adı alım satımı',
    'web_design' => 'Kurumsal site hizmetleri',
    'logo_design' => 'Marka kimliği tasarımı',
    'digital_content' => 'Metin, görsel ve kampanya',
    'mobile_app' => 'Android ve iOS projeleri',
    'hosting' => 'Barındırma ve taşıma',
    'software' => 'Yazılım ve script ürünleri',
    'seo' => 'Büyüme ve trafik paketleri',
    'service' => 'Dijital hizmet ilanları',
  ];
  return $labels[$type] ?? 'Dijital ürün ve hizmetler';
}
?>
<section class="ao-public-page ao-marketplace-page">
  <div class="ao-public-shell">
    <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('marketplace', ['title'=>'Domain, yazılım, tasarım ve dijital hizmetler için kullanıcı ilan pazarı.']) : ''; ?>
    <?php if($managedHero): ?>
      <?= $managedHero ?>
    <?php else: ?>
    <section class="ao-market-hero">
      <span class="ao-kicker">Ahost Marketplace Pro</span>
      <h1>Domain, yazılım, tasarım ve dijital hizmetler için kullanıcı ilan pazarı.</h1>
      <p>Domain, tema, script, tasarım ve dijital hizmet ilanlarını keşfedin; uygun ürünler için teklif verin veya hızlıca satın alın.</p>
      <form class="ao-market-filter" method="get" action="<?= url('marketplace') ?>#ilanlar">
        <input name="q" value="<?= e($q) ?>" placeholder="İlan, domain, script, tasarım veya hizmet ara...">
        <select name="type">
          <option value="">Tüm türler</option>
          <?php foreach(['domain'=>'Domain','web_design'=>'Web Tasarım','seo'=>'SEO','logo_design'=>'Logo','digital_content'=>'Dijital İçerik','mobile_app'=>'Mobil Uygulama','hosting'=>'Hosting','software'=>'Yazılım / Script','service'=>'Hizmet'] as $k=>$v): ?>
            <option value="<?= e($k) ?>" <?= $type===$k?'selected':'' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="budget">
          <option value="">Bütçe</option>
          <option value="0-5000" <?= $budget==='0-5000'?'selected':'' ?>>0 - 5.000 ₺</option>
          <option value="5000-25000" <?= $budget==='5000-25000'?'selected':'' ?>>5.000 - 25.000 ₺</option>
          <option value="25000+" <?= $budget==='25000+'?'selected':'' ?>>25.000 ₺+</option>
        </select>
        <button type="submit">İlan Ara</button>
      </form>
      <div class="ao-market-stats">
        <div><strong><?= number_format((int)$stats['active'],0,',','.') ?></strong><span>Aktif ilan</span></div>
        <div><strong><?= number_format((int)$stats['featured'],0,',','.') ?></strong><span>Öne çıkan</span></div>
        <div><strong><?= number_format((int)$stats['premium'],0,',','.') ?></strong><span>Premium ilan</span></div>
        <div><strong><?= number_format((float)$stats['volume'],0,',','.') ?> ₺</strong><span>Vitrin değeri</span></div>
      </div>
      <div class="ao-content-actions"><a class="ao-content-btn" href="#ilan-olustur">İlan Oluştur</a><a class="ao-content-btn secondary" href="#ilanlar">İlanları Keşfet</a></div>
    </section>
    <?php endif; ?>

    <details id="ilan-olustur" class="ao-market-seller-panel">
      <summary class="ao-market-seller-summary">
        <span>
          <small class="ao-kicker">Satıcı Alanı</small>
          <strong>Marketplace ilanı oluştur</strong>
          <em>İlan formu kapalı gelir; oluşturmak istediğinizde buradan açılır.</em>
        </span>
        <b>İlan Oluştur</b>
      </summary>
      <div class="ao-market-seller-body">
        <div>
          <span class="ao-kicker">Satıcı Alanı</span>
          <h2>Marketplace ilanı oluştur</h2>
          <p>Ürünün/hizmetin özelliklerini, satış modelini, fiyatını ve teslim süresini yaz. Komisyon varsayılan olarak %<?= e(number_format($commissionDefault, 2, ',', '.')) ?> hesaplanır.</p>
        </div>
        <?php if($currentCustomer): ?>
        <form method="post" action="<?= url('marketplace/listing-save') ?>" class="ao-market-create-form">
          <?= csrf_field() ?>
          <label>İlan Başlığı<input name="title" required placeholder="Premium domain, web tasarım paketi, mobil uygulama..."></label>
          <label>İlan Türü<select name="listing_type"><option value="domain">Domain</option><option value="web_design">Web Tasarım</option><option value="seo">SEO Paketi</option><option value="logo_design">Logo Tasarımı</option><option value="digital_content">Dijital İçerik</option><option value="mobile_app">Mobil Uygulama</option><option value="hosting">Hosting Hizmeti</option><option value="software">Yazılım / Script</option><option value="service">Diğer Hizmet</option></select></label>
          <label>Domain / Ürün Kodu<input name="domain_name" placeholder="domain.com veya ürün referansı"></label>
          <label>Kategori<input name="category" placeholder="Örn: Kurumsal site, Android, SEO"></label>
          <label>Satış Fiyatı<input name="price" type="number" min="1" step="0.01" required placeholder="15000"></label>
          <label>Para Birimi<select name="currency"><option value="TRY">TRY ₺</option><option value="USD">USD $</option><option value="EUR">EUR €</option></select></label>
          <label>Satış Modeli<select name="sale_model"><option value="fixed">Sabit Fiyat</option><option value="offer">Teklif Al</option><option value="auction">Açık Artırma</option></select></label>
          <label>Teslim Süresi<input name="delivery_days" type="number" min="1" max="365" value="7"></label>
          <label class="full">Özellikler / Açıklama<textarea name="description" rows="4" placeholder="Neler dahil, teslim şartları, lisans, destek, teknik özellikler..."></textarea></label>
          <button class="ao-content-btn" type="submit">İlanı Onaya Gönder</button>
        </form>
        <?php else: ?>
        <div class="ao-market-login-box">
          <h3>İlan oluşturmak için müşteri girişi gerekli</h3>
          <p>Satıcı bilgisi ve ödeme/komisyon takibi müşteri hesabına bağlanır.</p>
          <a class="ao-content-btn" href="<?= url('client/login') ?>">Müşteri Girişi</a>
        </div>
        <?php endif; ?>
      </div>
    </details>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('ilan-olustur');
        if (!panel) return;
        document.querySelectorAll('a[href="#ilan-olustur"]').forEach(function (link) {
          link.addEventListener('click', function () {
            panel.open = true;
            window.setTimeout(function () { panel.scrollIntoView({behavior:'smooth', block:'start'}); }, 20);
          });
        });
      });
    </script>

    <section class="ao-section-head"><h2>Kategoriler</h2><p>Domain, tasarım, yazılım ve dijital hizmetleri tek merkezden yönetin.</p></section>
    <section class="ao-category-grid">
      <?php $catRows = $categories ?: $catFallback; foreach($catRows as $i=>$c): $ico=$c['icon'] ?? ($catFallback[$i%count($catFallback)]['icon'] ?? '✨'); $sub=ao_market_category_subtitle_rc14($c); ?>
        <a href="<?= url('marketplace?category='.rawurlencode((string)$c['name'])) ?>#ilanlar">
          <span class="ao-market-category-icon"><?= e($ico) ?></span>
          <b><?= e($c['name']) ?></b>
          <small><?= e($sub) ?></small>
        </a>
      <?php endforeach; ?>
    </section>

    <section id="ilanlar" class="ao-section-head"><h2>Tüm İlanlar</h2><p>Aktif marketplace ilanları, komisyon bilgisi ve teklif formları.</p></section>
    <?php if($listings): ?>
      <div class="ao-market-listings">
        <?php foreach($listings as $l): $price=(float)($l['price'] ?? 0); $cur=$l['currency'] ?? 'TRY'; $comm=(float)($l['commission_percent'] ?? $commissionDefault); $fee=$price*$comm/100; ?>
        <article class="ao-market-card">
          <div class="top"><span><?= e(ao_market_icon_rc14($l['listing_type'] ?? 'service')) ?></span><em><?= e($l['category'] ?: ($l['listing_type'] ?? 'İlan')) ?></em></div>
          <h3><?= e($l['title']) ?></h3>
          <p><?= e(ao_market_desc_rc14($l)) ?></p>
          <div class="meta"><span><?= e($l['listing_type'] ?? 'service') ?></span><span><?= e($l['sale_model'] ?? 'fixed') ?></span><span><?= (int)($l['delivery_days'] ?? 7) ?> gün teslim</span><?= !empty($l['is_premium'])?'<span>🏆 Premium</span>':'' ?><?= !empty($l['is_urgent'])?'<span>⚡ Hızlı satış</span>':'' ?></div>
          <div class="ao-market-price-box">
            <strong class="price"><?= e(ao_market_money_rc14($price, $cur)) ?></strong>
            <span>Komisyon %<?= e(number_format($comm,2,',','.')) ?>: <?= e(ao_market_money_rc14($fee, $cur)) ?></span>
          </div>
          <form method="post" action="<?= url('marketplace/offer') ?>" class="ao-market-offer-form">
            <?= csrf_field() ?>
            <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
            <input name="name" placeholder="Adınız">
            <input name="email" type="email" placeholder="E-posta">
            <input name="offer_amount" type="number" min="1" step="0.01" placeholder="Teklif">
            <button type="submit">Teklif Ver</button>
          </form>
        </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="ao-empty-premium"><h3>Uygun ilan bulunamadı.</h3><p>Arama filtresini değiştirin veya ilk marketplace ilanını oluşturun.</p><a class="ao-content-btn" href="#ilan-olustur">İlan Oluştur</a></div>
    <?php endif; ?>
  </div>
</section>
