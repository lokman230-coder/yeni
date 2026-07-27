<?php
/**
 * TAM İmport Testi — Fake WHMCS'ten Ahost'a gerçek insert
 */

require __DIR__ . '/bootstrap.php';

use App\Modules\Import\Services\ImportService;
use App\Core\Database\Connection;

$config = [
    'host' => '127.0.0.1', 'port' => 3306,
    'database' => 'fake_whmcs', 'username' => 'ahost', 'password' => 'ahost123',
    'prefix' => 'tbl',
];

echo "═══════════════════════════════════════════════════════\n";
echo "  🚀 FAKE WHMCS → AHOST TAM IMPORT TESTİ\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Öncesi
$before = [
    'customers'    => (int) Connection::selectOne("SELECT COUNT(*) c FROM customers")['c'],
    'products'     => (int) Connection::selectOne("SELECT COUNT(*) c FROM products")['c'],
    'hosting_servers' => (int) Connection::selectOne("SELECT COUNT(*) c FROM hosting_servers")['c'],
    'product_addons' => (int) Connection::selectOne("SELECT COUNT(*) c FROM product_addons")['c'],
    'settings'     => (int) Connection::selectOne("SELECT COUNT(*) c FROM settings")['c'],
];
echo "ÖNCESİ:\n";
foreach ($before as $k => $v) printf("  %-18s: %d\n", $k, $v);
echo "\n";

// 5 kritik tür için job oluştur + çalıştır
$types = ['servers', 'settings', 'addons', 'customers', 'products'];

foreach ($types as $type) {
    echo "▶ Import: $type\n";
    try {
        $jobId = ImportService::createJob('whmcs', $config, $type);
        $result = ImportService::runJob($jobId);
        printf("  ✓ Job #%d — %d imported / %d skipped / %d errors\n",
            $jobId, $result['imported'] ?? 0, $result['skipped'] ?? 0, $result['errors'] ?? 0);
    } catch (\Throwable $e) {
        echo "  ✗ HATA: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Sonrası
$after = [
    'customers'    => (int) Connection::selectOne("SELECT COUNT(*) c FROM customers")['c'],
    'products'     => (int) Connection::selectOne("SELECT COUNT(*) c FROM products")['c'],
    'hosting_servers' => (int) Connection::selectOne("SELECT COUNT(*) c FROM hosting_servers")['c'],
    'product_addons' => (int) Connection::selectOne("SELECT COUNT(*) c FROM product_addons")['c'],
    'settings'     => (int) Connection::selectOne("SELECT COUNT(*) c FROM settings")['c'],
];
echo "SONRASI:\n";
foreach ($after as $k => $v) {
    $diff = $v - $before[$k];
    $arrow = $diff > 0 ? "+$diff" : ($diff < 0 ? "$diff" : "0");
    printf("  %-18s: %d  (%s)\n", $k, $v, $arrow);
}
echo "\n";

// Import mapping kayıtları
$maps = Connection::select("SELECT entity_type, COUNT(*) c FROM import_mappings WHERE source = 'whmcs' GROUP BY entity_type");
echo "IMPORT MAPPINGS (duplicate önleme):\n";
foreach ($maps as $m) printf("  %-16s: %d eşleme kaydı\n", $m['entity_type'], $m['c']);

echo "\n═══════════════════════════════════════════════════════\n";
echo "  ✅ TAMAMLANDI\n";
echo "═══════════════════════════════════════════════════════\n";
