<?php
/**
 * Ahost One - Ücretsiz Domain Sorgulama
 */
$domain_result = null;
$error = null;
if ($_POST['action'] === 'check' && !empty($_POST['domain'])) {
    $domain = preg_replace('/\s+/', '', trim($_POST['domain']));
    $domain = preg_replace('/^https?:\/\//i', '', $domain);
    $domain = preg_replace('/\/.*$/', '', $domain);
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/', $domain)) {
        $error = "Geçerli bir domain adresi girin";
    } else {
        // Check availability via DNS
        $dns_records = [];
        $record_types = ['A', 'AAAA', 'MX', 'NS', 'TXT', 'SOA', 'CNAME'];
        foreach ($record_types as $type) {
            $records = @dns_get_record($domain, constant('DNS_' . $type));
            if ($records) $dns_records[$type] = $records;
        }
        $has_a = !empty($dns_records['A']) || !empty($dns_records['AAAA']);
        $ttl = 120;
        $nameservers = [];
        if (!empty($dns_records['NS'])) {
            foreach ($dns_records['NS'] as $ns) { $nameservers[] = $ns['target']; }
        }
        $mx = !empty($dns_records['MX']) ? count($dns_records['MX']) : 0;
        // Get WHOIS info
        $whois_data = @whois_query($domain);
        // Check SSL
        $ssl_info = null;
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
        $socket = @stream_socket_client("ssl://$domain:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        if ($socket) {
            $cert = stream_context_get_params($socket);
            if (!empty($cert['options']['ssl']['peer_certificate'])) {
                $ssl_info = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
            }
            fclose($socket);
        }
        $domain_result = [
            'domain' => $domain,
            'has_a' => $has_a,
            'nameservers' => $nameservers,
            'mx_count' => $mx,
            'whois' => $whois_data,
            'ssl' => $ssl_info,
            'dns_records' => $dns_records
        ];
    }
}
function whois_query($domain) {
    $server = 'whois.verisign-grs.com';
    $port = 43;
    $timeout = 10;
    $fp = @fsockopen($server, $port, $errno, $errstr, $timeout);
    if (!$fp) return ['error' => 'WHOIS sunucusuna bağlanılamadı'];
    fwrite($fp, "$domain\r\n");
    $response = '';
    while (!feof($fp)) { $response .= fgets($fp, 1024); }
    fclose($fp);
    $lines = explode("\n", $response);
    $data = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, ':') !== false) {
            list($key, $value) = array_map('trim', explode(':', $line, 2));
            if (!empty($key) && !empty($value)) $data[strtolower($key)] = $value;
        }
    }
    return $data;
}
?>

<section class="ao-site-content ao-domain-checker-page">
  <div class="ao-content-shell">
<div class="ao-tool-container">
    <div class="header">
        <h1>🌐 Ücretsiz Domain Sorgulama</h1>
        <p>Domain bilgilerini anında öğrenin</p>
    </div>
    
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="check">
            <div class="input-group">
                <input type="text" name="domain" placeholder="example.com" required value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>">
                <button type="submit" class="btn">🔍 Sorgula</button>
            </div>
        </form>
    </div>
    
    <?php if ($error): ?>
    <div class="card" style="border-left:4px solid #ef4444">
        <p style="color:#dc2626">❌ <?= htmlspecialchars($error) ?></p>
    </div>
    <?php elseif ($domain_result): ?>
    <div class="domain-status">
        <div class="icon">🌐</div>
        <div>
            <h2><?= htmlspecialchars($domain_result['domain']) ?></h2>
            <span class="badge"><?= $domain_result['has_a'] ? 'AKTİF' : 'PASİF' ?></span>
        </div>
    </div>
    
    <div class="card">
        <h3 style="margin-bottom:20px">📊 DNS Bilgileri</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="icon">🌐</div>
                <div class="label">DNS Kayıtları</div>
                <div class="value"><?= count($domain_result['dns_records']) ?></div>
            </div>
            <div class="info-item">
                <div class="icon">📧</div>
                <div class="label">MX Kayıtları</div>
                <div class="value"><?= $domain_result['mx_count'] ?></div>
            </div>
            <div class="info-item">
                <div class="icon">🔒</div>
                <div class="label">SSL Sertifikası</div>
                <div class="value"><?= $domain_result['ssl'] ? 'VAR' : 'YOK' ?></div>
            </div>
            <div class="info-item">
                <div class="icon">🖥️</div>
                <div class="label">Nameserver</div>
                <div class="value"><?= count($domain_result['nameservers']) ?></div>
            </div>
        </div>
        
        <?php if (!empty($domain_result['nameservers'])): ?>
        <h4 style="margin:20px 0 10px">Name Servers</h4>
        <div style="background:#f8fafc;padding:15px;border-radius:8px;font-family:monospace">
            <?= implode('<br>', array_map('htmlspecialchars', $domain_result['nameservers'])) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($domain_result['whois']) && empty($domain_result['whois']['error'])): ?>
        <h4 style="margin:20px 0 10px">WHOIS Bilgileri</h4>
        <table class="whois-table">
            <?php foreach (array_slice($domain_result['whois'], 0, 10) as $key => $value): ?>
            <tr><th><?= htmlspecialchars(ucfirst($key)) ?></th><td><?= htmlspecialchars($value) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="cta-box">
        <h3>🚀 Domain Almak İster misiniz?</h3>
        <p>.com, .net, .org ve daha fazlası uygun fiyatlarla</p>
        <a href="/" class="btn" style="background:#fff;color:#2563eb">Domain Ara</a>
    </div>
    
    <div class="features">
        <div class="feature"><div class="feature-icon">⚡</div><h4>Anlık Sorgulama</h4><p>DNS ve WHOIS bilgileri</p></div>
        <div class="feature"><div class="feature-icon">🔒</div><h4>SSL Kontrolü</h4><p>Güvenlik durumu</p></div>
        <div class="feature"><div class="feature-icon">🌐</div><h4>WHOIS</h4><p>Kayıt bilgileri</p></div>
    </div>
</div>
  </div>
</section>
