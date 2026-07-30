<?php
/**
 * Ahost One - Customer Service Detail View
 * Path: app/Views/customer/services/view.php
 *
 * Amaç:
 * - Undefined array key "payment_method" hatasını düzeltir.
 * - Müşteri hizmet detayını Ahost One'a özel, scoped tasarımla gösterir.
 * - "SSL ekle / ek hizmet satın al" tarzı satış kartını kaldırır.
 * - SSL bilgisini ayrı satış kartı yerine hizmet güvenlik durumu içinde gösterir.
 */

$c = current_customer();
$serviceId = (int)($_GET['id'] ?? 0);
$service = null;
$hosting = null;
$error = '';

if (!function_exists('ao_sv_pick')) {
    function ao_sv_pick(array $row, array $keys, $default = '') {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return $default;
    }
}

if (!function_exists('ao_sv_panel_type')) {
    function ao_sv_panel_type(array $service = [], array $hosting = []): string {
        $sources = [
            ao_sv_pick($hosting, ['panel_type', 'server_panel_type'], ''),
            ao_sv_pick($service, ['product_module', 'module_name', 'product_type', 'type'], ''),
        ];

        foreach ($sources as $source) {
            $value = strtolower(trim((string)$source));
            if ($value === '') continue;
            if (str_contains($value, 'directadmin') || $value === 'da') return 'directadmin';
            if (str_contains($value, 'plesk')) return 'plesk';
            if (str_contains($value, 'cpanel') || str_contains($value, 'whm')) return 'cpanel';
        }

        if (ao_sv_pick($hosting, ['directadmin_url'], '') !== '') return 'directadmin';
        if (ao_sv_pick($hosting, ['plesk_url'], '') !== '') return 'plesk';
        if (ao_sv_pick($hosting, ['cpanel_url', 'whm_url'], '') !== '') return 'cpanel';

        return 'cpanel';
    }
}

if (!function_exists('ao_sv_money')) {
    function ao_sv_money($amount, $currency = 'TRY') {
        $currency = $currency ?: 'TRY';
        $symbol = $currency === 'TRY' ? '₺' : $currency;
        return number_format((float)$amount, 2, ',', '.') . ' ' . e($symbol);
    }
}

if (!function_exists('ao_sv_date')) {
    function ao_sv_date($date) {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        return e((string)$date);
    }
}

if (!function_exists('ao_sv_percent')) {
    function ao_sv_percent($used, $limit) {
        $used = (float)$used;
        $limit = (float)$limit;
        if ($limit <= 0) return 0;
        return max(0, min(100, (int)round(($used / $limit) * 100)));
    }
}
if (!function_exists('ao_sv_table_columns')) {
    function ao_sv_table_columns(string $table): array {
        try {
            $cols = [];
            foreach (db()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC) as $col) {
                $cols[$col['Field']] = true;
            }
            return $cols;
        } catch (Throwable $e) {
            return [];
        }
    }
}
if (!function_exists('ao_sv_has_text')) {
    function ao_sv_has_text($haystack, string $needle): bool {
        return strpos((string)$haystack, $needle) !== false;
    }
}

$statusColorMap = [
    'active' => 'green',
    'pending' => 'orange',
    'suspended' => 'red',
    'terminated' => 'dark',
    'cancelled' => 'dark',
    'fraud' => 'red',
];

try {
    if (!$c || $serviceId <= 0) {
        $error = 'Hizmet bulunamadı.';
    } else {
        $productCols = ao_sv_table_columns('products');
        $productNameExpr = !empty($productCols['name']) ? 'p.name' : 'NULL';
        $productTypeExpr = !empty($productCols['type']) ? 'p.type' : 'NULL';
        $productModuleExpr = !empty($productCols['module_name']) ? 'p.module_name' : (!empty($productCols['module']) ? 'p.module' : 'NULL');
        $q = db()->prepare('
            SELECT s.*, '.$productNameExpr.' AS product_name, '.$productTypeExpr.' AS product_type, '.$productModuleExpr.' AS product_module
            FROM services s
            LEFT JOIN products p ON p.id = s.product_id
            WHERE s.id = ? AND s.customer_id = ?
            LIMIT 1
        ');
        $q->execute([$serviceId, (int)$c['id']]);
        $service = $q->fetch() ?: null;

        if (!$service) {
            $error = 'Hizmet bulunamadı veya bu hizmeti görüntüleme yetkiniz yok.';
        } else {
            try {
                $serverPanelExpr = 'NULL';
                $serverJoin = '';
                $serverCols = ao_sv_table_columns('server_nodes');
                if ($serverCols) {
                    $serverPanelExpr = !empty($serverCols['panel_type']) ? 'srv.panel_type' : 'NULL';
                    $serverJoin = 'LEFT JOIN server_nodes srv ON srv.id = h.server_id';
                }
                $hq = db()->prepare('SELECT h.*, '.$serverPanelExpr.' AS server_panel_type FROM hosting_accounts h '.$serverJoin.' WHERE h.service_id = ? LIMIT 1');
                $hq->execute([(int)$service['id']]);
                $hosting = $hq->fetch() ?: null;
            } catch (Throwable $e) {
                $hosting = null;
            }
        }
    }
} catch (Throwable $e) {
    $error = 'Hizmet detayı okunurken bir hata oluştu.';
}

$statusKey = strtolower((string)($service['status'] ?? ''));
$statusLabel = function_exists('ao_service_status_label')
    ? ao_service_status_label($service['status'] ?? '')
    : ($service['status'] ?? __t('common.unknown', 'Bilinmiyor'));
$status = [$statusLabel, $statusColorMap[$statusKey] ?? 'orange'];

$currency = ao_sv_pick((array)$service, ['currency'], 'TRY');
$domain = ao_sv_pick((array)$service, ['domain', 'domain_name', 'service_domain'], '-');
$productName = ao_sv_pick((array)$service, ['product_name', 'name', 'package'], 'Hosting Hizmeti');
$billingCycle = strtolower((string)ao_sv_pick((array)$service, ['billing_cycle', 'cycle'], 'monthly'));
$cycleLabel = function_exists('ao_billing_cycle_label') ? ao_billing_cycle_label($billingCycle) : ucfirst($billingCycle);
$renewalAmount = ao_sv_pick((array)$service, ['recurring_amount', 'amount', 'price', 'renewal_amount'], 0);
$nextDueDate = ao_sv_pick((array)$service, ['next_due_date', 'next_payment_date', 'renewal_date'], '-');
$registrationDate = ao_sv_pick((array)$service, ['registration_date', 'created_at', 'created'], '-');

// Warning düzeltmesi: payment_method doğrudan okunmuyor, güvenli fallback kullanılıyor.
$paymentMethod = ao_sv_pick((array)$service, [
    'payment_method',
    'payment_gateway',
    'gateway',
    'paymentmethod',
    'pay_method'
], 'Belirtilmemiş');

$serverName = $hosting ? ao_sv_pick($hosting, ['server_name', 'hostname'], '-') : '-';
$serverIp = $hosting ? ao_sv_pick($hosting, ['server_ip', 'ip_address'], '-') : '-';
$panelUser = $hosting ? ao_sv_pick($hosting, ['whm_username', 'username', 'panel_username'], '-') : '-';
$panelPassword = $hosting ? ao_sv_pick($hosting, ['panel_password', 'password'], '') : '';
$packageName = $hosting ? ao_sv_pick($hosting, ['package_name', 'package'], '-') : '-';
$panelType = $hosting ? ao_sv_panel_type((array)$service, (array)$hosting) : '';
$panelActions = [];
if ($hosting) {
    $panelMap = [
        'cpanel' => ['panel' => 'cpanel', 'label' => 'cPanel', 'class' => 'ao-btn'],
        'directadmin' => ['panel' => 'directadmin', 'label' => 'DirectAdmin', 'class' => 'ao-btn soft'],
        'plesk' => ['panel' => 'plesk', 'label' => 'Plesk', 'class' => 'ao-btn soft'],
    ];
    $panelActions[] = $panelMap[$panelType] ?? $panelMap['cpanel'];
}
$hostingShortcuts = [];
if ($hosting) {
    $baseLogin = 'client/service-panel-login?service_id='.(int)($service['id'] ?? 0);
    $hostingShortcuts = [
        ['icon'=>'▦','label'=>'Kontrol Paneli','hint'=>'Ana yönetim ekranı','url'=>url($baseLogin.'&panel='.$panelType)],
        ['icon'=>'✉','label'=>'Webmail','hint'=>'E-posta girişi','url'=>url($baseLogin.'&panel=webmail')],
        ['icon'=>'📁','label'=>'Dosya Yöneticisi','hint'=>'Site dosyaları','url'=>url($baseLogin.'&panel='.$panelType.'&feature=filemanager')],
        ['icon'=>'@','label'=>'E-posta Hesapları','hint'=>'Mailbox yönetimi','url'=>url($baseLogin.'&panel='.$panelType.'&feature=email')],
        ['icon'=>'↻','label'=>'Yedekleme','hint'=>'Backup araçları','url'=>url($baseLogin.'&panel='.$panelType.'&feature=backup')],
        ['icon'=>'◉','label'=>'Alt Domainler','hint'=>'Subdomain işlemleri','url'=>url($baseLogin.'&panel='.$panelType.'&feature=subdomains')],
        ['icon'=>'▣','label'=>'MySQL Veritabanı','hint'=>'Veritabanları','url'=>url($baseLogin.'&panel='.$panelType.'&feature=mysql')],
        ['icon'=>'⚙','label'=>'PHPMyAdmin','hint'=>'DB düzenleme','url'=>url($baseLogin.'&panel='.$panelType.'&feature=phpmyadmin')],
        ['icon'=>'↗','label'=>'İstatistikler','hint'=>'Awstats / trafik','url'=>url($baseLogin.'&panel='.$panelType.'&feature=stats')],
        ['icon'=>'⏱','label'=>'Cron İşleri','hint'=>'Zamanlanmış görev','url'=>url($baseLogin.'&panel='.$panelType.'&feature=cron')],
        ['icon'=>'🌐','label'=>'Alan Adları','hint'=>'Domain paneli','url'=>url('client/domains')],
        ['icon'=>'🎧','label'=>'Destek Talebi','hint'=>'Yardım iste','url'=>url('client/support')],
    ];
}

$diskUsed = $hosting ? (float)ao_sv_pick($hosting, ['disk_used_mb'], 0) : 0;
$diskLimit = $hosting ? (float)ao_sv_pick($hosting, ['disk_mb', 'disk_limit_mb'], 0) : 0;
$bandUsed = $hosting ? (float)ao_sv_pick($hosting, ['bandwidth_used_mb'], 0) : 0;
$bandLimit = $hosting ? (float)ao_sv_pick($hosting, ['bandwidth_mb', 'bandwidth_limit_mb'], 0) : 0;
$mailUsed = $hosting ? (float)ao_sv_pick($hosting, ['mail_used'], 0) : 0;
$mailLimit = $hosting ? (float)ao_sv_pick($hosting, ['mail_limit'], 0) : 0;
$dbUsed = $hosting ? (float)ao_sv_pick($hosting, ['mysql_used'], 0) : 0;
$dbLimit = $hosting ? (float)ao_sv_pick($hosting, ['mysql_limit'], 0) : 0;

$diskPct = ao_sv_percent($diskUsed, $diskLimit);
$bandPct = ao_sv_percent($bandUsed, $bandLimit);
$mailPct = ao_sv_percent($mailUsed, $mailLimit);
$dbPct = ao_sv_percent($dbUsed, $dbLimit);

$sslRaw = $hosting ? ao_sv_pick($hosting, ['ssl_status', 'ssl_state', 'ssl_enabled'], '') : '';
$sslStatus = $sslRaw !== ''
    ? ((string)$sslRaw === '1' ? 'Aktif' : ucfirst((string)$sslRaw))
    : ($domain !== '-' ? 'Otomatik SSL hazır' : 'Alan adı bekleniyor');
$sslExpires = $hosting ? ao_sv_pick($hosting, ['ssl_expires_at', 'ssl_expiry', 'ssl_valid_until'], '') : '';
?>

<div class="ao-service-view">
  <div class="ao-service-hero">
    <div class="ao-service-hero-main">
      <div>
        <span class="ao-service-kicker">Ahost One Hizmet Merkezi</span>
        <h2><?= $service ? e($productName) : 'Hizmet Detayı' ?></h2>
        <p>
          <?= $service
            ? e($domain !== '-' ? $domain : 'Bu hizmete bağlı alan adı bulunmuyor.')
            : 'Hizmet bilgileri yüklenemedi.' ?>
        </p>
      </div>
      <div class="ao-hero-actions">
        <?php if ($service): ?>
          <span class="ao-status-pill <?= e($status[1]) ?>"><?= e($status[0]) ?></span>
        <?php endif; ?>
        <a class="ao-btn ghost" href="<?= url('client/services') ?>">← Hizmetlerim</a>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="ao-empty">
      <strong>Hizmet bulunamadı</strong>
      <p><?= e($error) ?></p>
      <a class="ao-btn" href="<?= url('client/services') ?>">Hizmetlerime Dön</a>
    </div>
  <?php else: ?>

    <div class="ao-service-grid">
      <div class="ao-service-card ao-mini-card">
        <strong>Paket</strong>
        <span><?= e($packageName !== '-' ? $packageName : $productName) ?></span>
        <small><?= e($cycleLabel) ?> yenileme</small>
      </div>
      <div class="ao-service-card ao-mini-card">
        <strong>Yenileme</strong>
        <span><?= ao_sv_money($renewalAmount, $currency) ?></span>
        <small>Sonraki tarih: <?= ao_sv_date($nextDueDate) ?></small>
      </div>
      <div class="ao-service-card ao-mini-card">
        <strong>Ödeme</strong>
        <span><?= e($paymentMethod) ?></span>
        <small>Hata veren payment_method alanı güvenli okundu</small>
      </div>
      <div class="ao-service-card ao-mini-card">
        <strong>SSL / Güvenlik</strong>
        <span><?= e($sslStatus) ?></span>
        <small><?= $sslExpires ? 'Bitiş: '.ao_sv_date($sslExpires) : 'Satış kartı kaldırıldı' ?></small>
      </div>
    </div>

    <div class="ao-two-col">
      <div class="ao-service-card">
        <div class="ao-section-title">
          <div>
            <h3>Hizmet Özeti</h3>
            <p>Alan adı, sunucu, paket ve hesap bilgileri.</p>
          </div>
        </div>

        <div class="ao-info-list">
          <div class="ao-info-row"><b>Alan Adı</b><span><?= e($domain) ?></span></div>
          <div class="ao-info-row"><b>Ürün/Hizmet</b><span><?= e($productName) ?></span></div>
          <div class="ao-info-row"><b>Durum</b><span><span class="ao-status-pill <?= e($status[1]) ?>"><?= e($status[0]) ?></span></span></div>
          <div class="ao-info-row"><b>Kayıt Tarihi</b><span><?= ao_sv_date($registrationDate) ?></span></div>
          <div class="ao-info-row"><b>Fatura Döngüsü</b><span><?= e($cycleLabel) ?></span></div>
          <div class="ao-info-row"><b>Yenileme Tarihi</b><span><?= ao_sv_date($nextDueDate) ?></span></div>
          <div class="ao-info-row"><b>Yenileme Tutarı</b><span><?= ao_sv_money($renewalAmount, $currency) ?></span></div>
          <div class="ao-info-row"><b>Ödeme Yöntemi</b><span><?= e($paymentMethod) ?></span></div>
          <div class="ao-info-row"><b>Sunucu</b><span><?= e($serverName) ?></span></div>
          <div class="ao-info-row"><b>Sunucu IP</b><span><?= e($serverIp) ?></span></div>
          <div class="ao-info-row"><b>Panel Kullanıcısı</b><span><?= e($panelUser) ?></span></div>
          <div class="ao-info-row"><b>Panel Şifresi</b><span>
            <?php if($panelPassword !== ''): ?>
              <span class="ao-secret-line">
                <input type="password" readonly value="<?= e($panelPassword) ?>" data-ao-secret-field>
                <button type="button" data-ao-reveal-secret>Göster</button>
                <button type="button" data-ao-copy-secret>Kopyala</button>
              </span>
            <?php else: ?>
              <span class="ao-note ao-note-inline">Henüz kaydedilmemiş. Yeni şifre üretip güncellediğinizde sunucuda değişir ve burada görünür.</span>
            <?php endif; ?>
          </span></div>
          <div class="ao-info-row"><b>Nameserver</b><span><?= e($hosting ? ao_sv_pick($hosting, ['ns1'], '-') : '-') ?> / <?= e($hosting ? ao_sv_pick($hosting, ['ns2'], '-') : '-') ?></span></div>
        </div>
      </div>

      <div class="ao-service-card">
        <div class="ao-section-title">
          <div>
            <h3>Panel Girişleri</h3>
            <p>Hosting paneline hızlı erişim.</p>
          </div>
        </div>

        <?php if ($hosting): ?>
          <div class="ao-panel-actions">
            <?php foreach($panelActions as $action): ?>
              <a class="<?= e($action['class']) ?>" target="_blank" rel="noopener" href="<?= url('client/service-panel-login?service_id='.(int)$service['id'].'&panel='.$action['panel']) ?>">Otomatik <?= e($action['label']) ?> Girişi</a>
            <?php endforeach; ?>
            <a class="ao-btn soft" target="_blank" rel="noopener" href="<?= url('client/service-panel-login?service_id='.(int)$service['id'].'&panel=webmail') ?>">Webmail Girişi</a>
            <a class="ao-btn soft" href="<?= url('client/support') ?>">Destek Aç</a>
          </div>

          <div class="ao-service-gap-sm"></div>

          <div class="ao-hosting-shortcuts">
            <div class="ao-shortcut-head">
              <strong>Hosting Kısayolları</strong>
              <span><?= e($panelType ? strtoupper($panelType) : 'PANEL') ?> araçları</span>
            </div>
            <div class="ao-shortcut-grid">
              <?php foreach($hostingShortcuts as $shortcut): ?>
                <a href="<?= e($shortcut['url']) ?>" target="<?= ao_sv_has_text($shortcut['url'], 'service-panel-login') ? '_blank' : '_self' ?>" rel="noopener">
                  <b><?= e($shortcut['icon']) ?></b>
                  <span><?= e($shortcut['label']) ?></span>
                  <small><?= e($shortcut['hint']) ?></small>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="ao-service-gap-sm"></div>

          <form class="ao-password-form" method="post" action="<?= url('client/services/password-update') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
            <label>
              <strong>Panel Şifresi Güncelle</strong>
              <input class="ao-input" type="text" name="panel_password" placeholder="Yeni panel şifresi">
            </label>
            <button class="ao-btn soft" type="button" data-ao-generate-secret>Güçlü Şifre Üret</button>
            <button class="ao-btn" type="submit">Şifreyi Güncelle</button>
          </form>
        <?php else: ?>
          <div class="ao-note">
            Bu hizmet için hosting hesabı henüz oluşturulmamış. Kurulum tamamlandığında panel girişleri burada görünecek.
          </div>
          <div class="ao-service-gap-xs"></div>
          <a class="ao-btn" href="<?= url('client/support') ?>">Destek Talebi Aç</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="ao-service-gap-md"></div>

    <div class="ao-service-card">
      <div class="ao-section-title">
        <div>
          <h3>Kaynak Kullanımı</h3>
          <p>Disk, trafik, e-posta ve veritabanı limitleri.</p>
        </div>
      </div>

      <?php if ($hosting): ?>
        <div class="ao-usage-grid">
          <div class="ao-usage">
            <div class="ao-usage-top"><span>Disk</span><small><?= e((string)$diskUsed) ?> / <?= e((string)$diskLimit) ?> MB</small></div>
            <div class="ao-progress"><i style="--p:<?= (int)$diskPct ?>%"></i></div>
          </div>
          <div class="ao-usage">
            <div class="ao-usage-top"><span>Trafik</span><small><?= e((string)$bandUsed) ?> / <?= e((string)$bandLimit) ?> MB</small></div>
            <div class="ao-progress"><i style="--p:<?= (int)$bandPct ?>%"></i></div>
          </div>
          <div class="ao-usage">
            <div class="ao-usage-top"><span>E-posta</span><small><?= e((string)$mailUsed) ?> / <?= e((string)$mailLimit) ?></small></div>
            <div class="ao-progress"><i style="--p:<?= (int)$mailPct ?>%"></i></div>
          </div>
          <div class="ao-usage">
            <div class="ao-usage-top"><span>Veritabanı</span><small><?= e((string)$dbUsed) ?> / <?= e((string)$dbLimit) ?></small></div>
            <div class="ao-progress"><i style="--p:<?= (int)$dbPct ?>%"></i></div>
          </div>
        </div>
      <?php else: ?>
        <div class="ao-note">
          Hosting kaynak verisi bulunamadı. Bu alan sadece hosting hesabı oluşturulduktan sonra dolacaktır.
        </div>
      <?php endif; ?>
    </div>

    <div class="ao-service-gap-md"></div>

    <div class="ao-service-card">
      <div class="ao-section-title">
        <div>
          <h3>Güvenlik ve SSL</h3>
          <p>SSL durumu hizmet özeti içinde gösterilir; ayrı “SSL ekle / satın al” kartı kaldırıldı.</p>
        </div>
      </div>

      <div class="ao-info-list">
        <div class="ao-info-row"><b>SSL Durumu</b><span><?= e($sslStatus) ?></span></div>
        <div class="ao-info-row"><b>SSL Bitiş Tarihi</b><span><?= $sslExpires ? ao_sv_date($sslExpires) : '-' ?></span></div>
        <div class="ao-info-row"><b>Panel Kullanıcı Güvenliği</b><span>Şifre güncelleme müşteri panelinden yapılabilir</span></div>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
(function(){
  document.addEventListener('click', function(e){
    var reveal = e.target.closest && e.target.closest('[data-ao-reveal-secret]');
    var copy = e.target.closest && e.target.closest('[data-ao-copy-secret]');
    var generate = e.target.closest && e.target.closest('[data-ao-generate-secret]');
    if(generate){
      var form = generate.closest('form');
      var target = form ? form.querySelector('input[name="panel_password"]') : null;
      var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
      var pass = '';
      for(var i=0;i<16;i++) pass += chars[Math.floor(Math.random() * chars.length)];
      if(target){ target.type = 'text'; target.value = pass; target.focus(); }
      return;
    }
    if(!reveal && !copy) return;
    var wrap = e.target.closest('.ao-secret-line');
    var input = wrap ? wrap.querySelector('[data-ao-secret-field]') : null;
    if(!input) return;
    if(reveal){
      input.type = input.type === 'password' ? 'text' : 'password';
      reveal.textContent = input.type === 'password' ? 'Göster' : 'Gizle';
    }
    if(copy && navigator.clipboard){
      navigator.clipboard.writeText(input.value || '');
      copy.textContent = 'Kopyalandı';
      setTimeout(function(){ copy.textContent = 'Kopyala'; }, 1200);
    }
  });
})();
</script>
