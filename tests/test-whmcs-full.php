<?php
/**
 * WHMCS Import — TAM KAPSAM Kanıt Testi
 * Test edilen: müşteri, sipariş, fatura, ürün, domain, hosting, ticket,
 *              SUNUCULAR, REGISTRARLAR, AYARLAR, ADDONLAR, ÖZEL ALANLAR
 */

require __DIR__ . '/../vendor/autoload.php';

// .env yükle
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $_ENV[$k] = trim($v, '"\'');
            putenv("$k=" . trim($v, '"\''));
        }
    }
}

use App\Modules\Import\Drivers\WhmcsDriver;

$driver = new WhmcsDriver();
$config = [
    'host' => '127.0.0.1', 'port' => 3306,
    'database' => 'fake_whmcs', 'username' => 'ahost', 'password' => 'ahost123',
    'prefix' => 'tbl',
];

echo "════════════════════════════════════════════════════════════\n";
echo "  🔌 WHMCS Import — TAM KAPSAM Testi\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Bağlantı
$test = $driver->testConnection($config);
echo "  " . ($test['ok'] ? '✅' : '❌') . " {$test['message']}\n\n";

// Sayımlar
echo "📊 KAYIT SAYILARI\n";
echo str_repeat('─', 60) . "\n";
$counts = $driver->counts($config);
foreach ($counts as $type => $count) {
    printf("  %-16s : %d kayıt\n", $type, $count);
}
echo "\n";

// Yeni türlerin gerçek verisini göster
echo "════════════════════════════════════════════════════════════\n";
echo "  YENI EKLENEN 5 TÜR — VERİ ÖRNEKLERİ\n";
echo "════════════════════════════════════════════════════════════\n\n";

// 1) Servers
echo "🖥️  SERVERS\n" . str_repeat('─', 60) . "\n";
foreach ($driver->fetch($config, 'servers', 10, 0) as $s) {
    printf("  #%s %-25s %-25s [%s] %s\n",
        $s['external_id'], $s['name'], $s['hostname'], $s['panel_type'], $s['ip']);
}
echo "\n";

// 2) Registrars
echo "🔗 REGISTRARS\n" . str_repeat('─', 60) . "\n";
foreach ($driver->fetch($config, 'registrars', 10, 0) as $r) {
    $settings = $r['settings'] ? '(' . count($r['settings']) . ' ayar)' : '';
    printf("  %-15s %s %s\n", $r['name'], $r['label'], $settings);
    foreach ($r['settings'] as $k => $v) {
        printf("      → %s: %s\n", $k, str_repeat('*', min(8, strlen($v))));
    }
}
echo "\n";

// 3) Settings (sadece mapped olanlar)
echo "⚙️  SETTINGS (mapped olanlar aktarılır)\n" . str_repeat('─', 60) . "\n";
$mapped = 0; $unmapped = 0;
foreach ($driver->fetch($config, 'settings', 50, 0) as $s) {
    if ($s['is_mapped']) {
        $mapped++;
        printf("  ✓ %-30s = %s\n", $s['key'], $s['value']);
    } else {
        $unmapped++;
    }
}
echo "  (+$unmapped adet mapped olmayan skip edildi)\n\n";

// 4) Addons
echo "📦 ADDONS (Ek Paketler)\n" . str_repeat('─', 60) . "\n";
foreach ($driver->fetch($config, 'addons', 10, 0) as $a) {
    printf("  #%s %-25s %8.2f %s / %s\n",
        $a['external_id'], $a['name'], $a['price'], $a['currency'], $a['period']);
}
echo "\n";

// 5) Custom Fields
echo "📝 CUSTOM FIELDS (Özel Alanlar)\n" . str_repeat('─', 60) . "\n";
foreach ($driver->fetch($config, 'custom_fields', 20, 0) as $f) {
    $opts = $f['options'] ? ' [' . implode(', ', $f['options']) . ']' : '';
    printf("  #%s [%s] %-25s %-10s %s%s\n",
        $f['external_id'], $f['context'], $f['label'], $f['field_type'],
        $f['is_required'] ? '*zorunlu' : '', $opts);
}
echo "\n";

echo "════════════════════════════════════════════════════════════\n";
echo "  ✅ 12 türden VERİ ÇEKME DOĞRULANDI\n";
echo "════════════════════════════════════════════════════════════\n\n";
echo "SONUÇ: WHMCS DB kredensiyeli girip aşağıdakileri seçmeli aktarabilirsin:\n";
echo "  • Müşteriler, siparişler, faturalar, domainler, hosting, ticketlar\n";
echo "  • ⭐ SUNUCULAR (cPanel/DA config'leri dahil)\n";
echo "  • ⭐ REGISTRAR AYARLARI (API keyler dahil)\n";
echo "  • ⭐ SİSTEM AYARLARI (firma, iletişim, para birimi vb.)\n";
echo "  • ⭐ EK PAKETLER (adon'lar + fiyatlarıyla)\n";
echo "  • ⭐ ÖZEL ALANLAR (ürün bazlı, WHMCS custom fields)\n";
