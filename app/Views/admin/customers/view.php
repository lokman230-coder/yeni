<?php
$id = (int)($_GET['id'] ?? 0);
$flash = get_flash();
$c = null;
$services = $domains = $invoices = $tickets = $orders = $hosting = [];
$customerSwitchList = $users = $domainContacts = $creditTransactions = $gatewayTransactions = $activityLogs = $renewalLogs = $invoiceEmailLogs = $messageLogs = $quotes = $products = $serverRows = $registrarRows = $paymentMethods = [];

if (!function_exists('ao_cp_rows')) {
    function ao_cp_rows(string $sql, array $params = []): array {
        try { $st = db()->prepare($sql); $st->execute($params); return $st->fetchAll() ?: []; }
        catch (Throwable $e) { return []; }
    }
}
if (!function_exists('ao_cp_one')) {
    function ao_cp_one(string $sql, array $params = []): ?array {
        try { $st = db()->prepare($sql); $st->execute($params); $row = $st->fetch(); return $row ?: null; }
        catch (Throwable $e) { return null; }
    }
}
if (!function_exists('ao_cp_money')) {
    function ao_cp_money($amount, string $currency = 'TRY'): string {
        $symbol = $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : '₺');
        return number_format((float)$amount, 2, ',', '.') . ' ' . $symbol;
    }
}
if (!function_exists('ao_cp_text')) {
    function ao_cp_text($value, string $fallback = '-'): string { $v = trim((string)($value ?? '')); return $v === '' ? $fallback : $v; }
}
if (!function_exists('ao_cp_domain_name')) {
    function ao_cp_domain_name(array $row): string { return ao_cp_text($row['domain_name'] ?? ($row['domain'] ?? ''), '-'); }
}
if (!function_exists('ao_cp_short')) {
    function ao_cp_short($value, int $len = 120): string {
        $v = trim((string)($value ?? ''));
        if ($v === '') return '-';
        if (function_exists('mb_substr')) return mb_strlen($v) > $len ? mb_substr($v, 0, $len) . '…' : $v;
        return strlen($v) > $len ? substr($v, 0, $len) . '…' : $v;
    }
}
if (!function_exists('ao_cp_days_until')) {
    function ao_cp_days_until($date): int {
        $date = trim((string)($date ?? ''));
        if ($date === '') return 9999;
        try { return (int)floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400); }
        catch (Throwable $e) { return 9999; }
    }
}
if (!function_exists('ao_cp_status_label')) {
    function ao_cp_status_label($status): string {
        $map = [
            'active'=>'Aktif','inactive'=>'Pasif','closed'=>'Kapalı','deleted'=>'Silinmiş','pending'=>'Beklemede','suspended'=>'Askıda','terminated'=>'Sonlandırıldı','cancelled'=>'İptal','fraud'=>'Şüpheli',
            'paid'=>'Ödendi','unpaid'=>'Ödenmedi','overdue'=>'Gecikmiş','draft'=>'Taslak','sent'=>'Gönderildi','open'=>'Açık','answered'=>'Yanıtlandı','customer-reply'=>'Müşteri Yanıtı','closed_ticket'=>'Kapalı'
        ];
        $s = trim((string)$status);
        return $map[$s] ?? ($s !== '' ? $s : '-');
    }
}
if (!function_exists('ao_cp_status_class')) {
    function ao_cp_status_class($status): string {
        $s = strtolower(trim((string)$status));
        if (in_array($s, ['active','paid','sent','success','accepted','open'], true)) return 'good';
        if (in_array($s, ['pending','unpaid','draft','queued','customer-reply','answered'], true)) return 'warn';
        if (in_array($s, ['suspended','terminated','cancelled','fraud','overdue','failed','closed','deleted'], true)) return 'bad';
        return 'neutral';
    }
}
if (!function_exists('ao_cp_panel_type')) {
    function ao_cp_panel_type(array $service, array $hosting): string {
        $raw = strtolower(trim((string)($hosting['panel_type'] ?? $service['module_name'] ?? $service['product_type'] ?? '')));
        if (str_contains($raw, 'directadmin') || $raw === 'da') return 'directadmin';
        if (str_contains($raw, 'plesk')) return 'plesk';
        if (str_contains($raw, 'whm')) return 'whm';
        if (str_contains($raw, 'cpanel')) return 'cpanel';
        if (!empty($hosting['directadmin_url'])) return 'directadmin';
        if (!empty($hosting['plesk_url'])) return 'plesk';
        return 'cpanel';
    }
}
if (!function_exists('ao_cp_customer_domains')) {
    function ao_cp_customer_domains(int $customerId): array {
        if ($customerId <= 0) return [];
        try {
            $cols = [];
            foreach (db()->query('SHOW COLUMNS FROM domains')->fetchAll(PDO::FETCH_ASSOC) as $col) {
                $cols[$col['Field']] = true;
            }
            $where = [];
            $params = [];
            foreach (['customer_id', 'client_id', 'userid', 'user_id', 'owner_id'] as $col) {
                if (!empty($cols[$col])) {
                    $where[] = "d.`{$col}`=?";
                    $params[] = $customerId;
                }
            }
            $serviceDomains = ao_cp_rows('SELECT DISTINCT domain FROM services WHERE customer_id=? AND domain IS NOT NULL AND domain<>""', [$customerId]);
            $names = [];
            foreach ($serviceDomains as $row) {
                $name = trim((string)($row['domain'] ?? ''));
                if ($name !== '') $names[] = $name;
            }
            if ($names) {
                $domainCol = !empty($cols['domain_name']) ? 'domain_name' : (!empty($cols['domain']) ? 'domain' : '');
                if ($domainCol !== '') {
                    $where[] = 'd.`'.$domainCol.'` IN ('.implode(',', array_fill(0, count($names), '?')).')';
                    array_push($params, ...$names);
                }
            }
            if (!$where) return [];
            $sql = 'SELECT d.* FROM domains d WHERE ('.implode(' OR ', $where).') ORDER BY d.id DESC';
            return ao_cp_rows($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('admin_pct')) {
    function admin_pct($used, $limit): int {
        $used = (float)($used ?? 0);
        $limit = (float)($limit ?? 0);
        if ($limit <= 0) return 0;
        $pct = (int)round(($used / $limit) * 100);
        if ($pct < 0) return 0;
        if ($pct > 100) return 100;
        return $pct;
    }
}
if (!function_exists('ao_cp_price_try_amount')) {
    function ao_cp_price_try_amount(array $row): float {
        $try = (float)($row['price_try'] ?? 0);
        if ($try > 0) return round($try, 2);

        $usd = (float)($row['price_usd'] ?? 0);
        if ($usd > 0) {
            if (function_exists('ao_v23_price_try')) return round((float)ao_v23_price_try($usd, 'USD'), 2);
            if (function_exists('ao_v237_currency_rate')) return round($usd * (float)ao_v237_currency_rate('USD'), 2);
            if (function_exists('ao_currency_rate')) return round($usd * (float)ao_currency_rate('USD', 'TRY'), 2);
            return round($usd, 2);
        }

        $price = (float)($row['price'] ?? 0);
        $currency = strtoupper((string)($row['currency'] ?? 'TRY'));
        if ($price > 0 && $currency === 'USD') {
            if (function_exists('ao_v23_price_try')) return round((float)ao_v23_price_try($price, 'USD'), 2);
            if (function_exists('ao_v237_currency_rate')) return round($price * (float)ao_v237_currency_rate('USD'), 2);
            if (function_exists('ao_currency_rate')) return round($price * (float)ao_currency_rate('USD', 'TRY'), 2);
        }
        return round($price, 2);
    }
}
if (!function_exists('ao_cp_product_price_payload')) {
    function ao_cp_product_price_payload(array $product): array {
        $productId = (int)($product['id'] ?? 0);
        $payload = ['free' => 0.00];
        if ($productId <= 0) return $payload;

        $aliases = [
            'one_time' => 'onetime',
            'onetime' => 'one_time',
            'annual' => 'annually',
            'biennial' => 'biennially',
            'triennial' => 'triennially',
            'semiannual' => 'semiannually',
        ];
        $rows = ao_cp_rows("SELECT cycle,price,price_try,price_usd,currency,is_active FROM product_pricing WHERE product_id=? AND (is_active=1 OR is_active IS NULL) ORDER BY FIELD(cycle,'one_time','onetime','monthly','quarterly','semiannually','annually','biennially','triennially'), id ASC", [$productId]);
        foreach ($rows as $row) {
            $cycle = strtolower(trim((string)($row['cycle'] ?? '')));
            if ($cycle === '') continue;
            $amount = ao_cp_price_try_amount($row);
            $payload[$cycle] = $amount;
            if (isset($aliases[$cycle])) $payload[$aliases[$cycle]] = $amount;
        }

        if (count($payload) === 1 && isset($product['price'])) {
            $amount = ao_cp_price_try_amount($product);
            $cycle = strtolower(trim((string)($product['billing_cycle'] ?? 'monthly'))) ?: 'monthly';
            $payload[$cycle] = $amount;
            if (isset($aliases[$cycle])) $payload[$aliases[$cycle]] = $amount;
        }

        return $payload;
    }
}

try {
    $q = db()->prepare('SELECT * FROM customers WHERE id=?');
    $q->execute([$id]);
    $c = $q->fetch();
    if ($c) {
        $services = ao_cp_rows('SELECT s.*,p.name product_name,p.type product_type,p.module_name,p.description product_description FROM services s LEFT JOIN products p ON p.id=s.product_id WHERE s.customer_id=? ORDER BY s.id DESC', [$id]);
        $domains  = ao_cp_customer_domains((int)$id);
        $invoices = ao_cp_rows('SELECT * FROM invoices WHERE customer_id=? ORDER BY id DESC', [$id]);
        $tickets  = ao_cp_rows('SELECT * FROM tickets WHERE customer_id=? ORDER BY id DESC', [$id]);
        $orders   = ao_cp_rows('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC', [$id]);
        $hosting  = ao_cp_rows('SELECT h.*,s.id service_id,s.domain,s.status service_status,s.billing_cycle,s.next_due_date,p.name product_name FROM hosting_accounts h JOIN services s ON s.id=h.service_id LEFT JOIN products p ON p.id=s.product_id WHERE s.customer_id=? ORDER BY h.id DESC', [$id]);
        $customerSwitchList = ao_cp_rows('SELECT id,first_name,last_name,email,company_name FROM customers WHERE status<>"deleted" ORDER BY first_name,last_name LIMIT 500');
        $users = ao_cp_rows('SELECT * FROM customer_account_users WHERE customer_id=? ORDER BY id DESC', [$id]);
        $domainIds = array_values(array_filter(array_map(fn($d) => (int)($d['id'] ?? 0), $domains)));
        $domainNameExpr = 'd.id';
        try {
            $domainCols = [];
            foreach (db()->query('SHOW COLUMNS FROM domains')->fetchAll(PDO::FETCH_ASSOC) as $col) { $domainCols[$col['Field']] = true; }
            if (!empty($domainCols['domain_name']) && !empty($domainCols['domain'])) $domainNameExpr = 'COALESCE(d.domain_name,d.domain)';
            elseif (!empty($domainCols['domain_name'])) $domainNameExpr = 'd.domain_name';
            elseif (!empty($domainCols['domain'])) $domainNameExpr = 'd.domain';
        } catch (Throwable $e) {}
        $domainContacts = $domainIds ? ao_cp_rows('SELECT dc.*,'.$domainNameExpr.' AS domain_name FROM domain_contacts dc JOIN domains d ON d.id=dc.domain_id WHERE dc.domain_id IN ('.implode(',', array_fill(0, count($domainIds), '?')).') ORDER BY dc.id DESC', $domainIds) : [];
        $creditTransactions = ao_cp_rows('SELECT * FROM credit_transactions WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $gatewayTransactions = ao_cp_rows('SELECT * FROM payment_gateway_transactions WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $activityLogs = ao_cp_rows('SELECT * FROM customer_activity_logs WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $renewalLogs = ao_cp_rows('SELECT * FROM renewal_automation_logs WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $invoiceEmailLogs = ao_cp_rows('SELECT * FROM invoice_email_logs WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $quotes = ao_cp_rows('SELECT * FROM quotes WHERE customer_id=? ORDER BY id DESC LIMIT 100', [$id]);
        $products = ao_cp_rows('SELECT id,name,type,module_name,price,currency,billing_cycle FROM products ORDER BY name');
        $serverRows = ao_cp_rows('SELECT * FROM hosting_servers ORDER BY name');
        if (!$serverRows) { $serverRows = ao_cp_rows('SELECT * FROM servers ORDER BY name'); }
        if (!$serverRows) { $serverRows = ao_cp_rows('SELECT * FROM server_nodes ORDER BY name'); }
        $registrarRows = ao_cp_rows('SELECT * FROM domain_registrars ORDER BY name');
        $paymentMethods = ao_cp_rows('SELECT * FROM payment_gateways ORDER BY sort_order,name');

        try {
            require_once __DIR__ . '/../../../Controllers/Admin/AnnouncementsController.php';
            $messageLogs = admin_notification_logs_for_customer((int)$id, 200);
        } catch (Throwable $e) { $messageLogs = []; }
    }
} catch (Throwable $e) {}

if (!$c): ?>
<div class="ao-card"><h2>Müşteri bulunamadı</h2><a class="ao-btn" href="<?= url('admin/customers') ?>">Listeye Dön</a></div><?php return; endif;

$customerName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ('Müşteri #' . (int)$c['id']);
$currency = $c['currency'] ?? 'TRY';
$openInvoices = array_values(array_filter($invoices, fn($i) => in_array(strtolower((string)($i['status'] ?? '')), ['unpaid','overdue','pending'], true)));
$paidInvoices = array_values(array_filter($invoices, fn($i) => strtolower((string)($i['status'] ?? '')) === 'paid'));
$activeServices = array_values(array_filter($services, fn($s) => strtolower((string)($s['status'] ?? '')) === 'active'));
$activeDomains = array_values(array_filter($domains, fn($d) => strtolower((string)($d['status'] ?? '')) === 'active'));
$openTickets = array_values(array_filter($tickets, fn($t) => !in_array(strtolower((string)($t['status'] ?? '')), ['closed','kapalı'], true)));
$nextServiceDue = null;
foreach ($services as $s) { if (!empty($s['next_due_date']) && (!$nextServiceDue || $s['next_due_date'] < $nextServiceDue)) $nextServiceDue = $s['next_due_date']; }
$nextDomainDue = null;
foreach ($domains as $d) { $due = $d['next_due_date'] ?? ($d['expiry_date'] ?? null); if ($due && (!$nextDomainDue || $due < $nextDomainDue)) $nextDomainDue = $due; }
$totalDue = 0; foreach ($openInvoices as $i) { $totalDue += (float)($i['total'] ?? 0); }
$addressLine = trim(implode(' ', array_filter([$c['address1'] ?? '', $c['address2'] ?? '', $c['postcode'] ?? '', $c['city'] ?? '', $c['state'] ?? '', $c['country'] ?? ''])));
$hostingByService = []; foreach (($hosting ?? []) as $hh) { $hostingByService[(int)($hh['service_id'] ?? 0)] = $hh; }
$transferTargets = array_values(array_filter($customerSwitchList ?? [], fn($x) => (int)($x['id'] ?? 0) !== (int)$c['id']));
$productPricePayloads = []; foreach (($products ?? []) as $p) { $productPricePayloads[(int)($p['id'] ?? 0)] = ao_cp_product_price_payload($p); }
?>
<?php if($flash): ?><div class="ao-alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>


<div class="ao-admin-customer-profile">
  <div class="ao-cp-hero">
    <div class="ao-cp-hero-top">
      <div>
        <span class="ao-cp-kicker">👥 Admin Müşteri Profili</span>
        <div class="ao-cp-title">
          <div class="ao-cp-avatar"><?= e(strtoupper(substr($c['first_name'] ?: 'M', 0, 1) . substr($c['last_name'] ?: 'A', 0, 1))) ?></div>
          <div>
            <h1>#<?= (int)$c['id'] ?> · <?= e($customerName) ?></h1>
            <p><?= e($c['company_name'] ?: 'Bireysel müşteri') ?> · <?= e($c['email'] ?? '-') ?> · <?= e($c['phone'] ?? 'Telefon yok') ?></p>
          </div>
        </div>
        <div class="ao-cp-status-line">
          <span class="ao-cp-pill <?= e(ao_cp_status_class($c['status'] ?? '')) ?>">Durum: <?= e(ao_cp_status_label($c['status'] ?? '')) ?></span>
          <span class="ao-cp-pill">Para Birimi: <?= e($currency) ?></span>
          <span class="ao-cp-pill">Kayıt: <?= e($c['created_at'] ?? '-') ?></span>
          <span class="ao-cp-pill">Son Giriş: <?= e($c['last_login_at'] ?? 'Henüz yok') ?></span>
        </div>
      </div>
      <div class="ao-cp-hero-actions">
        <a class="ao-cp-btn primary" href="<?= url('admin/customers/login-as?id='.(int)$c['id']) ?>">Sahip Olarak Gir</a>
        <a class="ao-cp-btn" href="<?= url('client') ?>">Müşteri Paneli</a>
        <a class="ao-cp-btn" href="<?= url('admin/orders/new?customer_id='.(int)$c['id']) ?>">Yeni Sipariş</a>
        <a class="ao-cp-btn" href="<?= url('admin/accounting/invoices?customer_id='.(int)$c['id']) ?>">Fatura Oluştur</a>
        <a class="ao-cp-btn" href="<?= url('admin/customers') ?>">Listeye Dön</a>
      </div>
    </div>
  </div>

  <div class="ao-cp-switch">
    <label for="adminCustomerSwitch">Müşteri Değiştir</label>
    <select id="adminCustomerSwitch" onchange="if(this.value){location.href='<?= url('admin/customers/view?id=') ?>'+this.value}">
      <option value="">Müşteri seç...</option>
      <?php foreach($customerSwitchList as $sw): ?>
        <option value="<?= (int)$sw['id'] ?>" <?= (int)$sw['id']===(int)$c['id']?'selected':'' ?>>#<?= (int)$sw['id'] ?> · <?= e(trim(($sw['first_name']??'').' '.($sw['last_name']??''))) ?><?= !empty($sw['company_name'])?' · '.e($sw['company_name']):'' ?> · <?= e($sw['email']??'') ?></option>
      <?php endforeach; ?>
    </select>
    <a class="ao-cp-btn soft" href="<?= url('admin/customers/view?id='.max(1,(int)$c['id']-1)) ?>">← Önceki</a>
    <a class="ao-cp-btn soft" href="<?= url('admin/customers/view?id='.((int)$c['id']+1)) ?>">Sonraki →</a>
  </div>

  <div class="ao-cp-stats">
    <div class="ao-cp-stat"><span>Aktif Hizmet</span><strong><?= count($activeServices) ?></strong><small>Toplam <?= count($services) ?> kayıt</small></div>
    <div class="ao-cp-stat"><span>Alan Adı</span><strong><?= count($activeDomains) ?></strong><small>Toplam <?= count($domains) ?> domain</small></div>
    <div class="ao-cp-stat"><span>Açık Fatura</span><strong><?= count($openInvoices) ?></strong><small><?= e(ao_cp_money($totalDue, $currency)) ?></small></div>
    <div class="ao-cp-stat"><span>Kredi / Bakiye</span><strong><?= e(ao_cp_money($c['balance'] ?? 0, $currency)) ?></strong><small>Hesap bakiyesi</small></div>
    <div class="ao-cp-stat"><span>Destek</span><strong><?= count($openTickets) ?></strong><small>Toplam <?= count($tickets) ?> talep</small></div>
    <div class="ao-cp-stat"><span>Yenileme</span><strong><?= e($nextServiceDue ?: ($nextDomainDue ?: '-')) ?></strong><small>En yakın tarih</small></div>
  </div>

  <div class="ao-cp-shell">
    <div class="ao-cp-main">
      <div class="ao-cp-card" data-ao-tabs>
        <div class="ao-cp-tabs ao-real-tabs" role="tablist">
          <button class="active" data-tab="ozet">Özet</button>
          <button data-tab="profil">Profil</button>
          <button data-tab="kullanicilar">Kullanıcılar</button>
          <button data-tab="iletisim">İletişim Bilgisi</button>
          <button data-tab="urunler">Ürün/Hizmetler</button>
          <button data-tab="domainler">Alan Adları</button>
          <button data-tab="billable">Faturalandırılabilir Ürünler</button>
          <button data-tab="faturalar">Faturalar</button>
          <button data-tab="teklifler">Teklifler</button>
          <button data-tab="muhasebe">Muhasebe Geçmişi</button>
          <button data-tab="destek">Destek Talepleri</button>
          <button data-tab="epostalar">İletilen E-postalar</button>
          <button data-tab="notlar">Notlar</button>
          <button data-tab="loglar">Günlük Kayıtları</button>
        </div>

        <section id="tab-ozet" class="ao-cp-tab-panel ao-tab-panel active">
          <div class="ao-cp-grid-2">
            <div class="ao-cp-card">
              <div class="ao-cp-card-head"><h3>Müşteri Özeti</h3><span class="ao-cp-pill <?= e(ao_cp_status_class($c['status'] ?? '')) ?>"><?= e(ao_cp_status_label($c['status'] ?? '')) ?></span></div>
              <div class="ao-cp-card-body ao-cp-list">
                <div class="ao-cp-line"><span>Ad Soyad</span><strong><?= e($customerName) ?></strong></div>
                <div class="ao-cp-line"><span>Firma</span><strong><?= e($c['company_name'] ?: '-') ?></strong></div>
                <div class="ao-cp-line"><span>E-posta</span><strong><?= e($c['email'] ?? '-') ?></strong></div>
                <div class="ao-cp-line"><span>Telefon</span><strong><?= e($c['phone'] ?? '-') ?></strong></div>
                <div class="ao-cp-line"><span>Adres</span><strong><?= e($addressLine ?: '-') ?></strong></div>
                <div class="ao-cp-line"><span>Vergi / Kimlik</span><strong><?= e($c['tax_number'] ?? ($c['tc_identity_no'] ?? '-')) ?></strong></div>
              </div>
            </div>
            <div class="ao-cp-card">
              <div class="ao-cp-card-head"><h3>Operasyon Özeti</h3><a class="ao-cp-btn mini soft" href="<?= url('admin/customers/view?id='.(int)$c['id'].'#tab-urunler') ?>">Hizmetlere Git</a></div>
              <div class="ao-cp-card-body ao-cp-list">
                <div class="ao-cp-line"><span>Sonraki Hizmet Yenileme</span><strong><?= e($nextServiceDue ?: '-') ?></strong></div>
                <div class="ao-cp-line"><span>Sonraki Domain Yenileme</span><strong><?= e($nextDomainDue ?: '-') ?></strong></div>
                <div class="ao-cp-line"><span>Ödenmemiş Tutar</span><strong><?= e(ao_cp_money($totalDue, $currency)) ?></strong></div>
                <div class="ao-cp-line"><span>Son Sipariş</span><strong><?= e($orders[0]['order_number'] ?? '-') ?></strong></div>
                <div class="ao-cp-line"><span>Son Fatura</span><strong><?= e($invoices[0]['invoice_number'] ?? '-') ?></strong></div>
                <div class="ao-cp-line"><span>Son Ticket</span><strong><?= e($tickets[0]['subject'] ?? '-') ?></strong></div>
              </div>
            </div>
          </div>
          <div class="ao-cp-grid-3">
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Son Hizmetler</h3></div><div class="ao-cp-card-body ao-cp-list"><?php foreach(array_slice($services,0,4) as $s): ?><div class="ao-cp-line"><span><?= e($s['product_name'] ?: 'Özel Hizmet') ?><br><small><?= e($s['domain'] ?: '-') ?></small></span><strong><span class="ao-cp-pill <?= e(ao_cp_status_class($s['status'] ?? '')) ?>"><?= e(ao_cp_status_label($s['status'] ?? '')) ?></span></strong></div><?php endforeach; if(!$services): ?><div class="ao-cp-empty"><strong>Hizmet yok</strong>Henüz atanmış hizmet bulunmuyor.</div><?php endif; ?></div></div>
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Son Faturalar</h3></div><div class="ao-cp-card-body ao-cp-list"><?php foreach(array_slice($invoices,0,4) as $i): ?><div class="ao-cp-line"><span><?= e($i['invoice_number'] ?? ('#'.(int)$i['id'])) ?><br><small><?= e($i['due_date'] ?? '-') ?></small></span><strong><?= e(ao_cp_money($i['total'] ?? 0, $i['currency'] ?? $currency)) ?></strong></div><?php endforeach; if(!$invoices): ?><div class="ao-cp-empty"><strong>Fatura yok</strong>Henüz fatura kaydı yok.</div><?php endif; ?></div></div>
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Son Hareketler</h3></div><div class="ao-cp-card-body ao-cp-timeline"><?php foreach(array_slice($activityLogs,0,4) as $l): ?><div class="ao-cp-timeline-item"><strong><?= e($l['action'] ?? '-') ?></strong><small><?= e($l['created_at'] ?? '-') ?></small><p class="ao-cp-muted"><?= e(ao_cp_short($l['description'] ?? '',90)) ?></p></div><?php endforeach; if(!$activityLogs): ?><div class="ao-cp-empty"><strong>Günlük yok</strong>İşlem kaydı oluşmamış.</div><?php endif; ?></div></div>
          </div>
        </section>

        <section id="tab-profil" class="ao-cp-tab-panel ao-tab-panel">
          <form class="ao-cp-form" method="post" action="<?= url('admin/customers/update') ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Profil Bilgileri</h3><button class="ao-cp-btn primary">Bilgileri Güncelle</button></div><div class="ao-cp-card-body">
              <div class="ao-cp-form-grid">
                <label>Ad<input name="first_name" value="<?= e($c['first_name'] ?? '') ?>" required></label>
                <label>Soyad<input name="last_name" value="<?= e($c['last_name'] ?? '') ?>" required></label>
                <label>Firma<input name="company_name" value="<?= e($c['company_name'] ?? '') ?>"></label>
                <label>E-posta<input name="email" value="<?= e($c['email'] ?? '') ?>" required></label>
                <label>Telefon<input name="phone" value="<?= e($c['phone'] ?? '') ?>"></label>
                <label>Durum<select name="status"><?php foreach(['active'=>'Aktif','inactive'=>'Pasif','closed'=>'Kapalı'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= ($c['status']??'')===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
                <label>Bakiye<input name="balance" type="number" step="0.01" value="<?= e($c['balance'] ?? '0.00') ?>"></label>
                <label>Para Birimi<input name="currency" value="<?= e($currency) ?>"></label>
                <label>Dil<input name="language" value="<?= e($c['language'] ?? 'tr') ?>"></label>
                <label>Vergi No<input name="tax_number" value="<?= e($c['tax_number'] ?? '') ?>"></label>
                <label>TC Kimlik No<input name="tc_identity_no" value="<?= e($c['tc_identity_no'] ?? '') ?>"></label>
                <label>Doğum Tarihi<input type="date" name="birth_date" value="<?= e($c['birth_date'] ?? '') ?>"></label>
                <label class="full">Adres 1<input name="address1" value="<?= e($c['address1'] ?? '') ?>"></label>
                <label class="full">Adres 2<input name="address2" value="<?= e($c['address2'] ?? '') ?>"></label>
                <label>Şehir<input name="city" value="<?= e($c['city'] ?? '') ?>"></label>
                <label>İlçe/Bölge<input name="state" value="<?= e($c['state'] ?? '') ?>"></label>
                <label>Posta Kodu<input name="postcode" value="<?= e($c['postcode'] ?? '') ?>"></label>
                <label>Ülke<input name="country" value="<?= e($c['country'] ?? 'Türkiye') ?>"></label>
                <label class="full">Admin Notu<textarea name="notes" rows="5"><?= e($c['notes'] ?? '') ?></textarea></label>
              </div>
            </div></div>
          </form>
        </section>

        <section id="tab-kullanicilar" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Müşteri Hesap Kullanıcıları</h3><span class="ao-cp-pill"><?= count($users) ?> kullanıcı</span></div><div class="ao-cp-card-body">
            <div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>ID</th><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Davet</th><th>Son Giriş</th></tr><?php foreach($users as $u): ?><tr><td>#<?= (int)$u['id'] ?></td><td><?= e($u['name'] ?? '-') ?><br><small><?= e($u['phone'] ?? '') ?></small></td><td><?= e($u['email'] ?? '-') ?></td><td><?= e($u['role_key'] ?? '-') ?></td><td><span class="ao-cp-pill <?= e(ao_cp_status_class($u['status'] ?? '')) ?>"><?= e(ao_cp_status_label($u['status'] ?? '')) ?></span></td><td><?= e($u['invited_at'] ?? '-') ?></td><td><?= e($u['last_login_at'] ?? '-') ?></td></tr><?php endforeach; if(!$users): ?><tr><td colspan="7"><div class="ao-cp-empty"><strong>Kullanıcı yok</strong>Bu müşteriye ek yetkili kullanıcı atanmadı.</div></td></tr><?php endif; ?></table></div>
          </div></div>
        </section>

        <section id="tab-iletisim" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-grid-2">
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Birincil İletişim / Fatura Bilgisi</h3></div><div class="ao-cp-card-body ao-cp-list">
              <div class="ao-cp-line"><span>Ad Soyad</span><strong><?= e($customerName) ?></strong></div><div class="ao-cp-line"><span>Firma</span><strong><?= e($c['company_name'] ?: '-') ?></strong></div><div class="ao-cp-line"><span>E-posta</span><strong><?= e($c['email'] ?? '-') ?></strong></div><div class="ao-cp-line"><span>Telefon</span><strong><?= e($c['phone'] ?? '-') ?></strong></div><div class="ao-cp-line"><span>Adres</span><strong><?= e($addressLine ?: '-') ?></strong></div><div class="ao-cp-line"><span>Vergi No</span><strong><?= e($c['tax_number'] ?? '-') ?></strong></div>
            </div></div>
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Domain WHOIS İletişimleri</h3></div><div class="ao-cp-card-body">
              <div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Domain</th><th>Ad</th><th>E-posta</th><th>Telefon</th></tr><?php foreach($domainContacts as $dc): ?><tr><td><?= e($dc['domain_name'] ?? '-') ?></td><td><?= e($dc['registrant_name'] ?? '-') ?></td><td><?= e($dc['registrant_email'] ?? '-') ?></td><td><?= e($dc['registrant_phone'] ?? '-') ?></td></tr><?php endforeach; if(!$domainContacts): ?><tr><td colspan="4">Domain iletişim kaydı yok.</td></tr><?php endif; ?></table></div>
            </div></div>
          </div>
        </section>

        <section id="tab-urunler" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Müşteriye Ait Ürün/Hizmetler</h3><span class="ao-cp-pill">Profil içinde düzenlenebilir</span></div><div class="ao-cp-card-body">
            <?php if($services): ?>
              <label class="ao-cp-switch-label">Hizmet Seç
                <select class="ao-cp-inline-selector" data-ao-switch="service">
                  <?php foreach($services as $idx=>$s): ?><option value="service-<?= (int)$s['id'] ?>"><?= e('#'.(int)$s['id'].' · '.($s['product_name'] ?: 'Özel Hizmet').' · '.($s['domain'] ?: 'Alan yok')) ?></option><?php endforeach; ?>
                </select>
              </label>
              <?php foreach($services as $idx=>$s): $h=$hostingByService[(int)$s['id']] ?? []; $panelType=ao_cp_panel_type($s,$h); $dp=min(100,(int)admin_pct((int)($h['disk_used_mb']??$h['disk_used']??0),(int)($h['disk_mb']??$h['disk_limit']??0))); $bp=min(100,(int)admin_pct((int)($h['bandwidth_used_mb']??$h['bandwidth_used']??0),(int)($h['bandwidth_mb']??$h['bandwidth_limit']??0))); $adminPanelBase='admin/service-panel-login?service_id='.(int)$s['id']; $adminHostingShortcuts=$h ? [
                ['▦','Kontrol Paneli','Ana panel oturumu',url($adminPanelBase.'&panel='.$panelType)],
                ['✉','Webmail','E-posta girişi',url($adminPanelBase.'&panel=webmail')],
                ['📁','Dosya Yöneticisi','Site dosyaları',url($adminPanelBase.'&panel='.$panelType.'&feature=filemanager')],
                ['@','E-posta Hesapları','Mailbox yönetimi',url($adminPanelBase.'&panel='.$panelType.'&feature=email')],
                ['↻','Yedekleme','Backup araçları',url($adminPanelBase.'&panel='.$panelType.'&feature=backup')],
                ['◉','Alt Domainler','Subdomain işlemleri',url($adminPanelBase.'&panel='.$panelType.'&feature=subdomains')],
                ['▣','MySQL Veritabanı','Veritabanları',url($adminPanelBase.'&panel='.$panelType.'&feature=mysql')],
                ['⚙','PHPMyAdmin','DB düzenleme',url($adminPanelBase.'&panel='.$panelType.'&feature=phpmyadmin')],
                ['↗','İstatistikler','Awstats / trafik',url($adminPanelBase.'&panel='.$panelType.'&feature=stats')],
                ['⏱','Cron İşleri','Zamanlanmış görevler',url($adminPanelBase.'&panel='.$panelType.'&feature=cron')],
                ['🌐','Müşteri Paneli','Sahip olarak gör',url('admin/customers/login-as?id='.(int)$c['id'])],
                ['🎧','Destek Talebi','Talep merkezine git',url('admin/support/tickets?customer_id='.(int)$c['id'])],
              ] : []; ?>
                <div class="ao-cp-inline-detail ao-cp-switch-card" data-ao-switch-item="service-<?= (int)$s['id'] ?>" style="<?= $idx===0?'':'display:none' ?>">
                  <div class="ao-cp-detail-head"><div><h4><?= e($s['product_name'] ?: 'Özel Hizmet') ?></h4><p>#<?= (int)$s['id'] ?> · <?= e($s['product_type'] ?? 'service') ?> · Otomasyon: <?= e($s['module_name'] ?: ($h['panel_type'] ?? 'manuel')) ?></p></div><span class="ao-cp-pill <?= e(ao_cp_status_class($s['status'] ?? '')) ?>"><?= e(ao_cp_status_label($s['status'] ?? '')) ?></span></div>
                  <form method="post" action="<?= url('admin/customers/service-save') ?>" class="ao-cp-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" value="<?= (int)$c['id'] ?>">
                    <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="hosting_update" value="1">
                    <div class="ao-cp-edit-layout">
                      <div class="ao-cp-edit-col">
                        <div class="ao-cp-edit-row"><label>Sipariş #</label><div class="ao-cp-read"><?= !empty($s['order_id']) ? '<a href="'.e(url('admin/orders/view?id='.(int)$s['order_id'])).'">#'.(int)$s['order_id'].' - Siparişi Görüntüle</a>' : '-' ?></div></div>
                        <div class="ao-cp-edit-row"><label>Ürün/Hizmet</label><select name="product_id"><?php foreach($products as $p): ?><option value="<?= (int)$p['id'] ?>" data-product-name="<?= e($p['name'] ?? '') ?>" data-prices="<?= e(json_encode($productPricePayloads[(int)$p['id']] ?? [], JSON_UNESCAPED_UNICODE)) ?>" <?= (int)($s['product_id']??0)===(int)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; if(!$products): ?><option value="<?= (int)($s['product_id']??0) ?>"><?= e($s['product_name'] ?: 'Mevcut ürün') ?></option><?php endif; ?></select></div>
                        <div class="ao-cp-edit-row"><label>Sunucu</label><select name="server_id"><option value="">Sunucu seçilmedi</option><?php foreach($serverRows as $srv): $srvName=$srv['name'] ?? $srv['server_name'] ?? $srv['hostname'] ?? ('Sunucu #'.(int)$srv['id']); ?><option value="<?= (int)$srv['id'] ?>" <?= (int)($h['server_id']??$s['server_id']??0)===(int)$srv['id']?'selected':'' ?>><?= e($srvName) ?></option><?php endforeach; ?></select></div>
                        <div class="ao-cp-edit-row"><label>Alan Adı</label><input name="domain" value="<?= e($s['domain'] ?? '') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Atanmış IP</label><input name="server_ip" value="<?= e($h['server_ip'] ?? $h['ip_address'] ?? '') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Kullanıcı Adı</label><input name="whm_username" value="<?= e($h['whm_username'] ?? $h['username'] ?? $h['panel_username'] ?? '') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Şifre</label><div class="ao-cp-secret-wrap"><input type="password" name="panel_password" value="<?= e($h['panel_password'] ?? '') ?>" data-ao-admin-secret><button type="button" class="ao-cp-btn mini soft" data-ao-admin-secret-reveal>Göster</button><button type="button" class="ao-cp-btn mini soft" data-ao-admin-secret-copy>Kopyala</button></div></div>
                        <div class="ao-cp-edit-row"><label>Durum</label><select name="status"><?php foreach(['active'=>'Aktif','pending'=>'Beklemede','suspended'=>'Askıda','terminated'=>'Sonlandırıldı','cancelled'=>'İptal'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= strtolower((string)($s['status']??''))===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
                      </div>
                      <div class="ao-cp-edit-col">
                        <div class="ao-cp-edit-row"><label>Kayıt Tarihi</label><input type="date" name="registration_date" value="<?= e(substr((string)($s['registration_date'] ?? $s['created_at'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Miktar</label><input type="number" name="quantity" step="1" value="<?= e($s['quantity'] ?? 1) ?>"></div>
                        <div class="ao-cp-edit-row"><label>İlk Ödeme Miktarı</label><input type="number" step="0.01" name="first_payment_amount" value="<?= e($s['first_payment_amount'] ?? $s['setup_fee'] ?? '0.00') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Yinelenen Miktar</label><input type="number" step="0.01" name="recurring_amount" value="<?= e($s['recurring_amount'] ?? $s['amount'] ?? '0.00') ?>"><button type="button" class="ao-cp-btn mini soft" data-ao-fill-product-price>Ürün fiyatını çek</button><small class="ao-cp-muted" data-ao-price-note>Seçili ürün ve fatura dönemindeki fiyatı bu alana aktarır.</small></div>
                        <div class="ao-cp-edit-row"><label>Sonraki Ödeme Tarihi</label><input type="date" name="next_due_date" value="<?= e(substr((string)($s['next_due_date'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Kapatma/Silme Tarihi</label><input type="date" name="termination_date" value="<?= e(substr((string)($s['termination_date'] ?? $s['terminated_at'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Fatura Dönemi</label><select name="billing_cycle"><?php foreach(['free'=>'Ücretsiz','onetime'=>'Tek Seferlik','monthly'=>'Aylık','quarterly'=>'3 Aylık','semiannually'=>'6 Aylık','annually'=>'Yıllık','biennially'=>'2 Yıllık','triennially'=>'3 Yıllık'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= strtolower((string)($s['billing_cycle']??''))===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
                        <div class="ao-cp-edit-row"><label>Ödeme Yöntemi</label><select name="payment_method"><option value="">Varsayılan</option><?php foreach($paymentMethods as $pm): $pmName=$pm['name'] ?? $pm['title'] ?? $pm['gateway'] ?? ('Gateway #'.(int)$pm['id']); $pmVal=$pm['gateway'] ?? $pm['slug'] ?? $pmName; ?><option value="<?= e($pmVal) ?>" <?= (string)($s['payment_method']??'')===(string)$pmVal?'selected':'' ?>><?= e($pmName) ?></option><?php endforeach; ?><option value="banktransfer" <?= (string)($s['payment_method']??'')==='banktransfer'?'selected':'' ?>>Banka Transferi (EFT)</option></select></div>
                        <div class="ao-cp-edit-row"><label>Promosyon Kodu</label><input name="promo_code" value="<?= e($s['promo_code'] ?? '') ?>"></div>
                      </div>
                    </div>

                    <div class="ao-cp-section-title"><h4>Hosting / Panel Bilgileri</h4><span class="ao-cp-pill">Sunucuya bağlı alanlar</span></div>
                    <div class="ao-cp-detail-grid">
                      <label>Panel Türü<select name="panel_type"><option value="">Otomatik</option><?php foreach(['cpanel'=>'cPanel','plesk'=>'Plesk','directadmin'=>'DirectAdmin','whm'=>'WHM / Reseller','manual'=>'Manuel'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= strtolower((string)($h['panel_type']??$s['module_name']??''))===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
                      <label>Sunucu Adı<input name="server_name" value="<?= e($h['server_name'] ?? $h['server_hostname'] ?? '') ?>"></label>
                      <label>Paket Adı<input name="package_name" value="<?= e($h['package_name'] ?? $h['package'] ?? '') ?>"></label>
                      <label>cPanel URL<input name="cpanel_url" value="<?= e($h['cpanel_url'] ?? '') ?>"></label>
                      <label>Webmail URL<input name="webmail_url" value="<?= e($h['webmail_url'] ?? '') ?>"></label>
                      <label>WHM URL<input name="whm_url" value="<?= e($h['whm_url'] ?? '') ?>"></label>
                      <label>DirectAdmin URL<input name="directadmin_url" value="<?= e($h['directadmin_url'] ?? '') ?>"></label>
                      <label>Plesk URL<input name="plesk_url" value="<?= e($h['plesk_url'] ?? '') ?>"></label>
                    </div>
                    <div class="ao-cp-ns-grid">
                      <label>NS1<input name="ns1" value="<?= e($h['ns1'] ?? '') ?>"></label>
                      <label>NS2<input name="ns2" value="<?= e($h['ns2'] ?? '') ?>"></label>
                    </div>

                    <?php if($h): ?>
                    <div class="ao-cp-section-title"><h4>WHMCS Tarzı Hosting Kısayolları</h4><span class="ao-cp-pill">Admin hızlı erişim</span></div>
                    <div class="ao-cp-hosting-shortcuts">
                      <?php foreach($adminHostingShortcuts as $shortcut): ?>
                        <a href="<?= e($shortcut[3]) ?>" target="_blank" rel="noopener">
                          <b><?= e($shortcut[0]) ?></b>
                          <span><?= e($shortcut[1]) ?></span>
                          <small><?= e($shortcut[2]) ?></small>
                        </a>
                      <?php endforeach; ?>
                    </div>

                    <div class="ao-cp-section-title"><h4>Metrik İstatistikleri</h4><span class="ao-cp-pill">Canlı kullanım / limit</span></div>
                    <div class="ao-cp-resource-grid">
                      <div class="ao-cp-resource"><span>Disk</span><b><?= $dp ?>%</b><i><em style="width:<?= $dp ?>%"></em></i><small><?= e(($h['disk_used_mb']??$h['disk_used']??0).' / '.(($h['disk_mb']??$h['disk_limit']??0) ?: '∞')) ?> MB · Kalan: <?= e(($h['disk_mb']??$h['disk_limit']??0) ? max(0,(int)($h['disk_mb']??$h['disk_limit'])-(int)($h['disk_used_mb']??$h['disk_used']??0)) : '∞') ?></small></div>
                      <div class="ao-cp-resource"><span>Trafik</span><b><?= $bp ?>%</b><i><em style="width:<?= $bp ?>%"></em></i><small><?= e(($h['bandwidth_used_mb']??$h['bandwidth_used']??0).' / '.(($h['bandwidth_mb']??$h['bandwidth_limit']??0) ?: '∞')) ?> MB · Kalan: <?= e(($h['bandwidth_mb']??$h['bandwidth_limit']??0) ? max(0,(int)($h['bandwidth_mb']??$h['bandwidth_limit'])-(int)($h['bandwidth_used_mb']??$h['bandwidth_used']??0)) : '∞') ?></small></div>
                    </div>
                    <?php endif; ?>

                    <div class="ao-cp-command-strip">
                      <strong>Otomasyon Komutları</strong>
                      <?php foreach(['activate'=>'Aktif Et','suspend'=>'Askıya Al','unsuspend'=>'Askıdan Çıkar','terminate'=>'Kapat/Sil'] as $act=>$label): ?>
                      <button class="ao-cp-btn mini soft" formaction="<?= url('admin/customers/service-action') ?>" name="service_action" value="<?= e($act) ?>" onclick="return confirm('<?= e($label) ?> işlemi uygulanacak. Emin misiniz?')"><?= e($label) ?></button>
                      <?php endforeach; ?>
                      <?php if($h): ?>
                        <a class="ao-cp-btn mini primary" target="_blank" rel="noopener" href="<?= url('admin/service-panel-login?service_id='.(int)$s['id'].'&panel='.$panelType) ?>">Otomatik Panel Girişi</a>
                        <a class="ao-cp-btn mini soft" target="_blank" rel="noopener" href="<?= url('admin/service-panel-login?service_id='.(int)$s['id'].'&panel=webmail') ?>">Webmail</a>
                      <?php endif; ?>
                    </div>

                    <label class="ao-cp-switch-label">Yönetici Notu<textarea name="admin_notes" rows="4"><?= e($s['admin_notes'] ?? $s['notes'] ?? '') ?></textarea></label>
                    <div class="ao-cp-savebar"><button class="ao-cp-btn primary">Değişiklikleri Kaydet</button><a class="ao-cp-btn soft" href="<?= url('admin/customers/view?id='.(int)$c['id'].'#tab-urunler') ?>">Vazgeç</a></div>
                  </form>

                  <form class="ao-cp-transfer-box" method="post" action="<?= url('admin/customers/transfer-entity') ?>"><?= csrf_field() ?><input type="hidden" name="entity_type" value="service"><input type="hidden" name="entity_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="from_customer_id" value="<?= (int)$c['id'] ?>"><label>Bu hizmeti başka müşteriye aktar<select name="to_customer_id" required><option value="">Müşteri seçin</option><?php foreach($transferTargets as $tc): ?><option value="<?= (int)$tc['id'] ?>">#<?= (int)$tc['id'] ?> · <?= e(trim(($tc['first_name']??'').' '.($tc['last_name']??'')) ?: ($tc['company_name']??$tc['email']??'')) ?></option><?php endforeach; ?></select></label><input name="note" placeholder="Aktarım notu"><button class="ao-cp-btn mini warn" onclick="return confirm('Bu hizmet seçilen müşteriye aktarılacak. Onaylıyor musunuz?')">Müşteriye Aktar</button></form>
                </div>
              <?php endforeach; ?>
            <?php else: ?><div class="ao-cp-empty"><strong>Hizmet yok</strong>Bu müşteriye atanmış ürün/hizmet kaydı bulunmuyor.</div><?php endif; ?>
          </div></div>
        </section>

        <section id="tab-domainler" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Alan Adları</h3><span class="ao-cp-pill">Profil içinde düzenlenebilir</span></div><div class="ao-cp-card-body">
            <?php if($domains): ?>
              <label class="ao-cp-switch-label">Domain Seç<select class="ao-cp-inline-selector" data-ao-switch="domain"><?php foreach($domains as $idx=>$d): ?><option value="domain-<?= (int)$d['id'] ?>"><?= e(ao_cp_domain_name($d).' · '.ao_cp_status_label($d['status'] ?? '')) ?></option><?php endforeach; ?></select></label>
              <?php foreach($domains as $idx=>$d): $domainRegistrar=$d['registrar'] ?? ($d['registrar_name'] ?? (!empty($d['registrar_id']) ? ('Registrar #'.(int)$d['registrar_id']) : 'DomainNameAPI')); $domainEpp=$d['epp_code'] ?? ($d['auth_code'] ?? ''); ?>
                <div class="ao-cp-inline-detail ao-cp-switch-card" data-ao-switch-item="domain-<?= (int)$d['id'] ?>" style="<?= $idx===0?'':'display:none' ?>">
                  <div class="ao-cp-detail-head"><div><h4><?= e(ao_cp_domain_name($d)) ?></h4><p>#<?= (int)$d['id'] ?> · Registrar: <?= e($domainRegistrar) ?></p></div><span class="ao-cp-pill <?= e(ao_cp_status_class($d['status'] ?? '')) ?>"><?= e(ao_cp_status_label($d['status'] ?? '')) ?></span></div>
                  <form method="post" action="<?= url('admin/customers/domain-save') ?>" class="ao-cp-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" value="<?= (int)$c['id'] ?>">
                    <input type="hidden" name="domain_id" value="<?= (int)$d['id'] ?>">
                    <div class="ao-cp-edit-layout">
                      <div class="ao-cp-edit-col">
                        <div class="ao-cp-edit-row"><label>Sipariş #</label><div class="ao-cp-read"><?= !empty($d['order_id']) ? '<a href="'.e(url('admin/orders/view?id='.(int)$d['order_id'])).'">#'.(int)$d['order_id'].' - Siparişi Görüntüle</a>' : '-' ?></div></div>
                        <div class="ao-cp-edit-row"><label>Alan Adı</label><input name="domain_name" value="<?= e(ao_cp_domain_name($d) !== '-' ? ao_cp_domain_name($d) : '') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Alan Adı Kayıt Operatörü</label><select name="registrar_id"><option value="">Manuel / Registrar yok</option><?php foreach($registrarRows as $r): $rName=$r['name'] ?? $r['module_name'] ?? $r['slug'] ?? ('Registrar #'.(int)$r['id']); ?><option value="<?= (int)$r['id'] ?>" <?= (int)($d['registrar_id']??0)===(int)$r['id']?'selected':'' ?>><?= e($rName) ?></option><?php endforeach; ?></select></div>
                        <div class="ao-cp-edit-row"><label>Registrar Metni</label><input name="registrar" value="<?= e($domainRegistrar) ?>"></div>
                        <div class="ao-cp-edit-row"><label>İlk Ödeme Miktarı</label><input type="number" step="0.01" name="first_payment_amount" value="<?= e($d['first_payment_amount'] ?? $d['first_payment'] ?? '0.00') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Yenileme Miktarı</label><input type="number" step="0.01" name="recurring_amount" value="<?= e($d['recurring_amount'] ?? $d['renewal_price'] ?? $d['amount'] ?? '0.00') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Promosyon Kodu</label><input name="promo_code" value="<?= e($d['promo_code'] ?? '') ?>"></div>
                        <div class="ao-cp-edit-row"><label>Abonelik ID'si</label><input name="subscription_id" value="<?= e($d['subscription_id'] ?? '') ?>"></div>
                      </div>
                      <div class="ao-cp-edit-col">
                        <div class="ao-cp-edit-row"><label>Tescil Dönemi</label><div style="display:grid;grid-template-columns:1fr auto;gap:8px"><input type="number" step="1" name="registration_period" value="<?= e($d['registration_period'] ?? 1) ?>"><span class="ao-cp-read">Yıl</span></div></div>
                        <div class="ao-cp-edit-row"><label>Kayıt Tarihi</label><input type="date" name="registration_date" value="<?= e(substr((string)($d['registration_date'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Bitiş Tarihi</label><input type="date" name="expiry_date" value="<?= e(substr((string)($d['expiry_date'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Sonraki Ödeme Tarihi</label><input type="date" name="next_due_date" value="<?= e(substr((string)($d['next_due_date'] ?? ''),0,10)) ?>"></div>
                        <div class="ao-cp-edit-row"><label>Ödeme Yöntemi</label><select name="payment_method"><option value="">Varsayılan</option><?php foreach($paymentMethods as $pm): $pmName=$pm['name'] ?? $pm['title'] ?? $pm['gateway'] ?? ('Gateway #'.(int)$pm['id']); $pmVal=$pm['gateway'] ?? $pm['slug'] ?? $pmName; ?><option value="<?= e($pmVal) ?>" <?= (string)($d['payment_method']??'')===(string)$pmVal?'selected':'' ?>><?= e($pmName) ?></option><?php endforeach; ?><option value="banktransfer" <?= (string)($d['payment_method']??'')==='banktransfer'?'selected':'' ?>>Banka Transferi (EFT)</option></select></div>
                        <div class="ao-cp-edit-row"><label>Durum</label><select name="status"><?php foreach(['active'=>'Aktif','pending'=>'Beklemede','expired'=>'Süresi Doldu','cancelled'=>'İptal','fraud'=>'Şüpheli','transfer_pending'=>'Transfer Bekliyor'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= strtolower((string)($d['status']??''))===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
                        <div class="ao-cp-edit-row"><label>Otomatik Yenileme</label><label class="ao-cp-inline-check"><input type="checkbox" name="auto_renew" value="1" <?= !empty($d['auto_renew'])?'checked':'' ?>> Açık</label></div>
                        <div class="ao-cp-edit-row"><label>Transfer Kilidi</label><label class="ao-cp-inline-check"><input type="checkbox" name="lock_status" value="1" <?= !empty($d['lock_status'])?'checked':'' ?>> Kilitli</label></div>
                      </div>
                    </div>
                    <div class="ao-cp-section-title"><h4>DNS / Nameserver Yönetimi</h4><span class="ao-cp-pill">Profil içinden değiştirilebilir</span></div>
                    <div class="ao-cp-ns-grid">
                      <label>Ad Sunucusu 1<input name="ns1" value="<?= e($d['ns1'] ?? '') ?>"></label>
                      <label>Ad Sunucusu 2<input name="ns2" value="<?= e($d['ns2'] ?? '') ?>"></label>
                      <label>Ad Sunucusu 3<input name="ns3" value="<?= e($d['ns3'] ?? '') ?>"></label>
                      <label>Ad Sunucusu 4<input name="ns4" value="<?= e($d['ns4'] ?? '') ?>"></label>
                      <label>Ad Sunucusu 5<input name="ns5" value="<?= e($d['ns5'] ?? '') ?>"></label>
                      <label>EPP / Auth Code<input name="epp_code" value="<?= e($domainEpp) ?>"></label>
                    </div>
                    <div class="ao-cp-command-strip">
                      <strong>Alan Adı Komutları</strong>
                      <button class="ao-cp-btn mini soft" formaction="<?= url('admin/customers/domain-action') ?>" name="domain_action" value="toggle-lock">Kilit Değiştir</button>
                      <button class="ao-cp-btn mini soft" formaction="<?= url('admin/customers/domain-action') ?>" name="domain_action" value="toggle-autorenew">Oto Yenileme</button>
                      <button class="ao-cp-btn mini soft" formaction="<?= url('admin/customers/domain-action') ?>" name="domain_action" value="update-epp">EPP Güncelle</button>
                      <button class="ao-cp-btn mini soft" formaction="<?= url('admin/customers/domain-action') ?>" name="domain_action" value="renew" onclick="return confirm('Bu domain için yenileme siparişi/faturası oluşturulsun mu?')">Yenileme Siparişi</button>
                    </div>
                    <label class="ao-cp-switch-label">Yönetici Notu<textarea name="notes" rows="4"><?= e($d['notes'] ?? $d['admin_notes'] ?? '') ?></textarea></label>
                    <div class="ao-cp-savebar"><button class="ao-cp-btn primary">Değişiklikleri Kaydet</button><a class="ao-cp-btn soft" href="<?= url('admin/customers/view?id='.(int)$c['id'].'#tab-domainler') ?>">Vazgeç</a></div>
                  </form>
                  <form class="ao-cp-transfer-box" method="post" action="<?= url('admin/customers/transfer-entity') ?>"><?= csrf_field() ?><input type="hidden" name="entity_type" value="domain"><input type="hidden" name="entity_id" value="<?= (int)$d['id'] ?>"><input type="hidden" name="from_customer_id" value="<?= (int)$c['id'] ?>"><label>Bu domaini başka müşteriye aktar<select name="to_customer_id" required><option value="">Müşteri seçin</option><?php foreach($transferTargets as $tc): ?><option value="<?= (int)$tc['id'] ?>">#<?= (int)$tc['id'] ?> · <?= e(trim(($tc['first_name']??'').' '.($tc['last_name']??'')) ?: ($tc['company_name']??$tc['email']??'')) ?></option><?php endforeach; ?></select></label><input name="note" placeholder="Aktarım notu"><button class="ao-cp-btn mini warn" onclick="return confirm('Bu domain seçilen müşteriye aktarılacak. Onaylıyor musunuz?')">Müşteriye Aktar</button></form>
                </div>
              <?php endforeach; ?>
            <?php else: ?><div class="ao-cp-empty"><strong>Domain yok</strong>Müşteriye kayıtlı alan adı bulunmuyor.</div><?php endif; ?>
          </div></div>
        </section>
        <section id="tab-billable" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-grid-2">
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Yaklaşan Hizmet Yenilemeleri</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Hizmet</th><th>Alan</th><th>Tarih</th><th>Kalan</th><th>Oto</th></tr><?php foreach($services as $s): $days=ao_cp_days_until($s['next_due_date'] ?? null); ?><tr><td><?= e($s['product_name'] ?: 'Hizmet') ?></td><td><?= e($s['domain'] ?: '-') ?></td><td><?= e($s['next_due_date'] ?: '-') ?></td><td><span class="ao-cp-pill <?= $days<16?'bad':($days<45?'warn':'good') ?>"><?= $days===9999?'-':$days.' gün' ?></span></td><td><?= !empty($s['auto_renew'])?'Açık':'Kapalı' ?></td></tr><?php endforeach; if(!$services): ?><tr><td colspan="5">Yenilenecek hizmet yok.</td></tr><?php endif; ?></table></div></div></div>
            <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Yaklaşan Domain Yenilemeleri</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Domain</th><th>Bitiş</th><th>Kalan</th><th>Oto</th></tr><?php foreach($domains as $d): $days=ao_cp_days_until($d['expiry_date'] ?? null); ?><tr><td><?= e(ao_cp_domain_name($d)) ?></td><td><?= e($d['expiry_date'] ?? '-') ?></td><td><span class="ao-cp-pill <?= $days<16?'bad':($days<45?'warn':'good') ?>"><?= $days===9999?'-':$days.' gün' ?></span></td><td><?= !empty($d['auto_renew'])?'Açık':'Kapalı' ?></td></tr><?php endforeach; if(!$domains): ?><tr><td colspan="4">Yenilenecek domain yok.</td></tr><?php endif; ?></table></div></div></div>
          </div>
        </section>

        <section id="tab-faturalar" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Faturalar</h3><a class="ao-cp-btn mini primary" href="<?= url('admin/accounting/invoices?customer_id='.(int)$c['id']) ?>">Yeni Fatura</a></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>No</th><th>Durum</th><th>Ara Toplam</th><th>Vergi</th><th>Toplam</th><th>Son Ödeme</th><th>Ödenme</th><th>İşlem</th></tr><?php foreach($invoices as $i): ?><tr><td><?= e($i['invoice_number'] ?? ('#'.(int)$i['id'])) ?></td><td><span class="ao-cp-pill <?= e(ao_cp_status_class($i['status'] ?? '')) ?>"><?= e(ao_cp_status_label($i['status'] ?? '')) ?></span></td><td><?= e(ao_cp_money($i['subtotal'] ?? 0, $i['currency'] ?? $currency)) ?></td><td><?= e(ao_cp_money($i['tax'] ?? 0, $i['currency'] ?? $currency)) ?></td><td><strong><?= e(ao_cp_money($i['total'] ?? 0, $i['currency'] ?? $currency)) ?></strong></td><td><?= e($i['due_date'] ?? '-') ?></td><td><?= e($i['paid_at'] ?? '-') ?></td><td><a class="ao-cp-btn mini soft" href="<?= url('admin/accounting/invoices?view='.(int)$i['id']) ?>">Detay</a></td></tr><?php endforeach; if(!$invoices): ?><tr><td colspan="8"><div class="ao-cp-empty"><strong>Fatura yok</strong>Müşteriye ait fatura bulunmuyor.</div></td></tr><?php endif; ?></table></div></div></div>
        </section>

        <section id="tab-teklifler" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Teklifler / Proformalar</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>No</th><th>Konu</th><th>Durum</th><th>Tutar</th><th>Geçerlilik</th></tr><?php foreach($quotes as $q): ?><tr><td><?= e($q['quote_number'] ?? ('#'.(int)$q['id'])) ?></td><td><?= e($q['subject'] ?? '-') ?></td><td><?= e($q['status'] ?? '-') ?></td><td><?= e(ao_cp_money($q['total'] ?? 0, $q['currency'] ?? $currency)) ?></td><td><?= e($q['valid_until'] ?? '-') ?></td></tr><?php endforeach; if(!$quotes): ?><tr><td colspan="5"><div class="ao-cp-empty"><strong>Teklif yok</strong>Teklif tablosu yoksa sistem güvenli boş gösterir.</div></td></tr><?php endif; ?></table></div></div></div>
        </section>

        <section id="tab-muhasebe" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-grid-2"><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Kredi İşlemleri</h3></div><div class="ao-cp-card-body"><form class="ao-cp-form" method="post" action="<?= url('admin/customers/credit') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><div class="ao-cp-form-grid"><label>Tutar (+/-)<input name="amount" type="number" step="0.01" value="100.00"></label><label>Not<input name="note" value="Admin kredi işlemi"></label><label>&nbsp;<button class="ao-cp-btn primary">Kredi Güncelle</button></label></div></form><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Tür</th><th>Tutar</th><th>Bakiye</th><th>Açıklama</th><th>Tarih</th></tr><?php foreach($creditTransactions as $t): ?><tr><td><?= e($t['type'] ?? '-') ?></td><td><?= e(ao_cp_money($t['amount'] ?? 0, $t['currency'] ?? $currency)) ?></td><td><?= e(ao_cp_money($t['balance_after'] ?? 0, $t['currency'] ?? $currency)) ?></td><td><?= e($t['description'] ?? '-') ?></td><td><?= e($t['created_at'] ?? '-') ?></td></tr><?php endforeach; if(!$creditTransactions): ?><tr><td colspan="5">Kredi hareketi yok.</td></tr><?php endif; ?></table></div></div></div><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Ödeme Gateway Hareketleri</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Gateway</th><th>Durum</th><th>Tutar</th><th>Komisyon</th><th>Tarih</th></tr><?php foreach($gatewayTransactions as $g): ?><tr><td><?= e($g['gateway'] ?? '-') ?><br><small><?= e($g['gateway_transaction_id'] ?? '') ?></small></td><td><?= e($g['status'] ?? '-') ?></td><td><?= e(ao_cp_money($g['amount'] ?? 0, $g['currency'] ?? $currency)) ?></td><td><?= e(ao_cp_money($g['fee_amount'] ?? 0, $g['currency'] ?? $currency)) ?></td><td><?= e($g['created_at'] ?? '-') ?></td></tr><?php endforeach; if(!$gatewayTransactions): ?><tr><td colspan="5">Gateway hareketi yok.</td></tr><?php endif; ?></table></div></div></div></div>
        </section>

        <section id="tab-destek" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Destek Talepleri</h3><a class="ao-cp-btn mini primary" href="<?= url('admin/support/tickets?customer_id='.(int)$c['id']) ?>">Destek Merkezi</a></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>ID</th><th>Konu</th><th>Departman</th><th>Öncelik</th><th>Durum</th><th>Oluşturma</th><th>Güncelleme</th></tr><?php foreach($tickets as $t): ?><tr><td>#<?= (int)$t['id'] ?></td><td><?= e($t['subject'] ?? '-') ?></td><td><?= e($t['department'] ?? ($t['department_name'] ?? (!empty($t['department_id']) ? ('Departman #'.(int)$t['department_id']) : 'Genel'))) ?></td><td><?= e($t['priority'] ?? '-') ?></td><td><span class="ao-cp-pill <?= e(ao_cp_status_class($t['status'] ?? '')) ?>"><?= e(ao_cp_status_label($t['status'] ?? '')) ?></span></td><td><?= e($t['created_at'] ?? '-') ?></td><td><?= e($t['updated_at'] ?? '-') ?></td></tr><?php endforeach; if(!$tickets): ?><tr><td colspan="7"><div class="ao-cp-empty"><strong>Ticket yok</strong>Destek talebi bulunmuyor.</div></td></tr><?php endif; ?></table></div></div></div>
        </section>

        <section id="tab-epostalar" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-grid-2"><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Bildirim / E-posta / SMS / WhatsApp</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Kanal</th><th>Alıcı</th><th>Konu / Mesaj</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr><?php foreach($messageLogs as $m): ?><tr><td><?= e($m['channel_type'] ?? $m['channel'] ?? '-') ?></td><td><?= e($m['recipient'] ?? '-') ?></td><td><strong><?= e($m['subject'] ?? '') ?></strong><br><small><?= e(ao_cp_short($m['message'] ?? '',160)) ?></small></td><td><?= e($m['status'] ?? '-') ?></td><td><?= e($m['sent_at'] ?? $m['created_at'] ?? '-') ?></td><td><button class="ao-cp-btn mini soft" onclick="document.getElementById('edit-msg-<?= (int)$m['id'] ?>').classList.toggle('hidden')">Düzenle</button><form method="post" action="<?= url('admin/customers/notification-delete') ?>" style="display:inline" onsubmit="return confirm('Bu mesaj silinecek, emin misiniz?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="customer_id" value="<?= (int)$c['id'] ?>"><button class="ao-cp-btn mini danger">Sil</button></form><div id="edit-msg-<?= (int)$m['id'] ?>" class="hidden"><form method="post" action="<?= url('admin/customers/notification-edit') ?>" class="ao-cp-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="customer_id" value="<?= (int)$c['id'] ?>"><label>Konu<input name="subject" value="<?= e($m['subject'] ?? '') ?>"></label><label>Alıcı<input name="recipient" value="<?= e($m['recipient'] ?? '') ?>"></label><label>Mesaj<textarea name="message" rows="3"><?= e($m['message'] ?? '') ?></textarea></label><label>Durum<select name="status"><option value="queued">queued</option><option value="sent">sent</option><option value="failed">failed</option></select></label><button class="ao-cp-btn primary">Kaydet</button></form></div></td></tr><?php endforeach; if(!$messageLogs): ?><tr><td colspan="6">Bildirim kaydı yok.</td></tr><?php endif; ?></table></div></div></div><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Fatura E-posta Logları</h3></div><div class="ao-cp-card-body"><div class="ao-cp-table-wrap"><table class="ao-cp-table"><tr><th>Fatura</th><th>Alıcı</th><th>Konu</th><th>Durum</th><th>Tarih</th></tr><?php foreach($invoiceEmailLogs as $m): ?><tr><td>#<?= (int)($m['invoice_id'] ?? 0) ?></td><td><?= e($m['recipient_email'] ?? '-') ?></td><td><?= e($m['subject'] ?? '-') ?></td><td><?= e($m['status'] ?? '-') ?></td><td><?= e($m['created_at'] ?? '-') ?></td></tr><?php endforeach; if(!$invoiceEmailLogs): ?><tr><td colspan="5">Fatura e-postası yok.</td></tr><?php endif; ?></table></div></div></div></div>
        </section>

        <section id="tab-notlar" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Yönetici Notları</h3></div><div class="ao-cp-card-body"><form class="ao-cp-form" method="post" action="<?= url('admin/customers/update') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="first_name" value="<?= e($c['first_name'] ?? '') ?>"><input type="hidden" name="last_name" value="<?= e($c['last_name'] ?? '') ?>"><input type="hidden" name="company_name" value="<?= e($c['company_name'] ?? '') ?>"><input type="hidden" name="email" value="<?= e($c['email'] ?? '') ?>"><input type="hidden" name="phone" value="<?= e($c['phone'] ?? '') ?>"><input type="hidden" name="status" value="<?= e($c['status'] ?? 'active') ?>"><input type="hidden" name="balance" value="<?= e($c['balance'] ?? '0') ?>"><input type="hidden" name="currency" value="<?= e($currency) ?>"><input type="hidden" name="language" value="<?= e($c['language'] ?? 'tr') ?>"><input type="hidden" name="tax_number" value="<?= e($c['tax_number'] ?? '') ?>"><input type="hidden" name="tc_identity_no" value="<?= e($c['tc_identity_no'] ?? '') ?>"><input type="hidden" name="birth_date" value="<?= e($c['birth_date'] ?? '') ?>"><input type="hidden" name="address1" value="<?= e($c['address1'] ?? '') ?>"><input type="hidden" name="address2" value="<?= e($c['address2'] ?? '') ?>"><input type="hidden" name="city" value="<?= e($c['city'] ?? '') ?>"><input type="hidden" name="state" value="<?= e($c['state'] ?? '') ?>"><input type="hidden" name="postcode" value="<?= e($c['postcode'] ?? '') ?>"><input type="hidden" name="country" value="<?= e($c['country'] ?? 'Türkiye') ?>"><label>Admin Notu<textarea name="notes" rows="10"><?= e($c['notes'] ?? '') ?></textarea></label><button class="ao-cp-btn primary">Notu Kaydet</button></form></div></div>
        </section>

        <section id="tab-loglar" class="ao-cp-tab-panel ao-tab-panel">
          <div class="ao-cp-grid-2"><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Müşteri Günlük Kayıtları</h3></div><div class="ao-cp-card-body ao-cp-timeline"><?php foreach($activityLogs as $l): ?><div class="ao-cp-timeline-item"><strong><?= e($l['action'] ?? '-') ?></strong><small><?= e($l['created_at'] ?? '-') ?> · IP: <?= e($l['ip_address'] ?? '-') ?></small><p class="ao-cp-muted"><?= e(ao_cp_short($l['description'] ?? '',180)) ?></p></div><?php endforeach; if(!$activityLogs): ?><div class="ao-cp-empty"><strong>Kayıt yok</strong>Müşteri işlem günlüğü boş.</div><?php endif; ?></div></div><div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Otomasyon / Yenileme Logları</h3></div><div class="ao-cp-card-body ao-cp-timeline"><?php foreach($renewalLogs as $l): ?><div class="ao-cp-timeline-item"><strong><?= e($l['action'] ?? '-') ?> · <?= e($l['status'] ?? '-') ?></strong><small><?= e($l['created_at'] ?? '-') ?></small><p class="ao-cp-muted"><?= e(ao_cp_short($l['message'] ?? '',180)) ?></p></div><?php endforeach; if(!$renewalLogs): ?><div class="ao-cp-empty"><strong>Kayıt yok</strong>Otomasyon logu bulunmuyor.</div><?php endif; ?></div></div></div>
        </section>
      </div>
    </div>

    <aside class="ao-cp-side">
      <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Hızlı İşlemler</h3></div><div class="ao-cp-card-body ao-cp-list">
        <a class="ao-cp-btn primary" href="<?= url('admin/orders/new?customer_id='.(int)$c['id']) ?>">🛒 Yeni Sipariş Ekle</a>
        <a class="ao-cp-btn soft" href="<?= url('admin/accounting/invoices?customer_id='.(int)$c['id']) ?>">₺ Fatura Oluştur</a>
        <a class="ao-cp-btn soft" href="<?= url('admin/support/tickets?customer_id='.(int)$c['id']) ?>">🎧 Destek Talebi Aç</a>
        <a class="ao-cp-btn soft" href="<?= url('admin/customers/login-as?id='.(int)$c['id']) ?>">👤 Sahip Olarak Gir</a>
        <a class="ao-cp-btn warn" onclick="return confirm('Müşteri kapalı duruma alınsın mı?')" href="<?= url('admin/customers/close?id='.(int)$c['id'].'&csrf_token='.csrf_token()) ?>">Müşteriyi Kapat</a>
        <a class="ao-cp-btn danger" onclick="return confirm('Müşteri çöp kutusuna taşınsın mı?')" href="<?= url('admin/customers/delete?id='.(int)$c['id'].'&csrf_token='.csrf_token()) ?>">Müşteriyi Sil</a>
      </div></div>
      <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Hesap Bilgileri</h3></div><div class="ao-cp-card-body ao-cp-list">
        <div class="ao-cp-line"><span>Müşteri ID</span><strong>#<?= (int)$c['id'] ?></strong></div><div class="ao-cp-line"><span>Durum</span><strong><?= e(ao_cp_status_label($c['status'] ?? '')) ?></strong></div><div class="ao-cp-line"><span>Kredi</span><strong><?= e(ao_cp_money($c['balance'] ?? 0, $currency)) ?></strong></div><div class="ao-cp-line"><span>Kimlik</span><strong><?= !empty($c['identity_verified']) ? 'Doğrulandı' : 'Doğrulanmadı' ?></strong></div><div class="ao-cp-line"><span>Oluşturma</span><strong><?= e($c['created_at'] ?? '-') ?></strong></div>
      </div></div>
      <div class="ao-cp-card"><div class="ao-cp-card-head"><h3>Risk / Operasyon</h3></div><div class="ao-cp-card-body ao-cp-list">
        <div class="ao-cp-line"><span>Ödenmemiş Fatura</span><strong><?= count($openInvoices) ?></strong></div><div class="ao-cp-line"><span>Açık Ticket</span><strong><?= count($openTickets) ?></strong></div><div class="ao-cp-line"><span>Askıdaki Hizmet</span><strong><?= count(array_filter($services, fn($s)=>strtolower((string)($s['status']??''))==='suspended')) ?></strong></div><div class="ao-cp-line"><span>Şüpheli Sipariş</span><strong><?= count(array_filter($orders, fn($o)=>strtolower((string)($o['status']??''))==='fraud' || (int)($o['fraud_score']??0)>70)) ?></strong></div>
      </div></div>
    </aside>
  </div>
</div>

<script>
(function(){
  const profile=document.querySelector('.ao-admin-customer-profile');
  if(!profile) return;
  const clean=function(raw){
    return String(raw || '').replace(/^#?tab-?/,'').replace(/[^a-z0-9_-]/gi,'') || 'ozet';
  };
  const activeTab=function(){
    const active=profile.querySelector('.ao-cp-tabs [data-tab].active');
    return clean(active ? active.dataset.tab : (location.hash || 'ozet'));
  };
  const openTab=function(tab, writeHash){
    tab=clean(tab);
    const buttons=profile.querySelectorAll('.ao-cp-tabs [data-tab]');
    const panels=profile.querySelectorAll('.ao-cp-tab-panel');
    let found=false;
    buttons.forEach(function(btn){
      const ok=btn.dataset.tab === tab;
      btn.classList.toggle('active', ok);
      if(ok) found=true;
    });
    if(!found && tab !== 'ozet') return openTab('ozet', writeHash);
    panels.forEach(function(panel){
      panel.classList.toggle('active', panel.id === 'tab-' + tab);
    });
    if(writeHash && history.replaceState) history.replaceState(null, '', '#tab-' + tab);
  };
  profile.addEventListener('click', function(e){
    const btn=e.target.closest('.ao-cp-tabs [data-tab]');
    if(!btn) return;
    e.preventDefault();
    openTab(btn.dataset.tab, true);
  });
  profile.addEventListener('submit', function(e){
    const form=e.target;
    if(!form || !form.appendChild) return;
    let input=form.querySelector('input[name="return_tab"]');
    if(!input){
      input=document.createElement('input');
      input.type='hidden';
      input.name='return_tab';
      form.appendChild(input);
    }
    input.value=activeTab();
  }, true);
  openTab(clean(location.hash), false);
  window.addEventListener('hashchange', function(){ openTab(clean(location.hash), false); });
})();
document.addEventListener('change', function(e){
  const el=e.target.closest('[data-ao-switch]'); if(!el) return;
  const group=el.getAttribute('data-ao-switch'); const val=el.value;
  document.querySelectorAll('[data-ao-switch-item^="'+group+'-"]').forEach(function(card){card.style.display = card.getAttribute('data-ao-switch-item')===val ? '' : 'none';});
});
document.addEventListener('click', function(e){
  const revealSecret=e.target.closest('[data-ao-admin-secret-reveal]');
  const copySecret=e.target.closest('[data-ao-admin-secret-copy]');
  if(revealSecret || copySecret){
    const wrap=e.target.closest('.ao-cp-secret-wrap');
    const input=wrap ? wrap.querySelector('[data-ao-admin-secret]') : null;
    if(!input) return;
    if(revealSecret){
      input.type=input.type==='password'?'text':'password';
      revealSecret.textContent=input.type==='password'?'Göster':'Gizle';
    }
    if(copySecret && navigator.clipboard){
      navigator.clipboard.writeText(input.value || '');
      copySecret.textContent='Kopyalandı';
      setTimeout(function(){ copySecret.textContent='Kopyala'; }, 1200);
    }
    return;
  }
  const btn=e.target.closest('[data-ao-fill-product-price]'); if(!btn) return;
  const form=btn.closest('form'); if(!form) return;
  const productSelect=form.querySelector('select[name="product_id"]');
  const cycleSelect=form.querySelector('select[name="billing_cycle"]');
  const amountInput=form.querySelector('input[name="recurring_amount"]');
  const note=form.querySelector('[data-ao-price-note]');
  if(!productSelect || !cycleSelect || !amountInput) return;
  const option=productSelect.options[productSelect.selectedIndex];
  let prices={};
  try{ prices=JSON.parse(option.getAttribute('data-prices') || '{}'); }catch(err){ prices={}; }
  const aliases={onetime:'one_time', one_time:'onetime', annual:'annually', biennial:'biennially', triennial:'triennially', semiannual:'semiannually'};
  const cycle=(cycleSelect.value || 'monthly').toLowerCase();
  let price=prices[cycle];
  if((price===undefined || price===null || price==='') && aliases[cycle]) price=prices[aliases[cycle]];
  if((price===undefined || price===null || price==='') && prices.monthly!==undefined) price=prices.monthly;
  if(price===undefined || price===null || price===''){
    if(note) note.textContent='Bu ürün için seçili fatura döneminde fiyat bulunamadı.';
    return;
  }
  const amount=Number(price);
  if(!Number.isFinite(amount)){
    if(note) note.textContent='Fiyat okunamadı, ürün fiyatını kontrol edin.';
    return;
  }
  amountInput.value=amount.toFixed(2);
  if(note){
    const productName=option.getAttribute('data-product-name') || option.textContent.trim() || 'Seçili ürün';
    note.textContent=productName+' fiyatı aktarıldı: '+amount.toFixed(2)+' TL';
  }
});
</script>
