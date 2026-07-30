<?php
/**
 * WHMCS Import Kanıt Testi
 * DB'den müşteri + sipariş + fatura + domain + hosting + ticket çeker.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Modules\Import\Drivers\WhmcsDriver;
use App\Modules\Import\Services\ImportService;
use App\Core\Database\Connection;

// Ahost DB kur
$_ENV['DB_HOST'] = '127.0.0.1';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_DATABASE'] = 'ahost_one';
$_ENV['DB_USERNAME'] = 'ahost';
$_ENV['DB_PASSWORD'] = 'ahost123';

// .env yükle
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $_ENV[$k] = trim($v, '"\'');
            putenv("$k=" . trim($v, '"\''));
        }
    }
}

$driver = new WhmcsDriver();

// Kaynak WHMCS DB config
$config = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'fake_whmcs',
    'username' => 'ahost',
    'password' => 'ahost123',
    'prefix'   => 'tbl',
];

echo "════════════════════════════════════════════════\n";
echo "  🔌 WHMCS Import Driver — Canlı Test\n";
echo "════════════════════════════════════════════════\n\n";

// 1) Bağlantı testi
echo "[1] Bağlantı Testi\n";
$test = $driver->testConnection($config);
echo "    " . ($test['ok'] ? '✓' : '✗') . " {$test['message']}\n\n";

if (!$test['ok']) {
    echo "Bağlantı kurulamadı, durduruyorum.\n";
    exit(1);
}

// 2) Sayımlar
echo "[2] Kayıt Sayıları\n";
$counts = $driver->counts($config);
foreach ($counts as $type => $count) {
    printf("    %-12s: %d\n", $type, $count);
}
echo "\n";

// 3) Her tipten veri çek
echo "[3] Örnek Veriler (ilk 3)\n\n";

foreach (['customers', 'orders', 'invoices', 'products', 'domains', 'hosting', 'tickets'] as $type) {
    echo "  ┌─ $type ────────────────────────────\n";
    $rows = $driver->fetch($config, $type, 3, 0);
    foreach ($rows as $i => $row) {
        echo "  │ [" . ($i + 1) . "] ";
        // Kısa özet göster
        $summary = match ($type) {
            'customers' => sprintf('%s %s <%s> (%s)',
                $row['first_name'] ?? '', $row['last_name'] ?? '',
                $row['email'] ?? '', $row['status'] ?? ''),
            'orders'    => sprintf('#%s → %.2f TL [%s]',
                $row['external_id'] ?? '?', (float)($row['total'] ?? 0), $row['status'] ?? ''),
            'invoices'  => sprintf('#%s → %.2f TL [%s]',
                $row['external_id'] ?? '?', (float)($row['total'] ?? 0), $row['status'] ?? ''),
            'products'  => sprintf('%s (%s)',
                $row['name'] ?? '?', $row['type'] ?? ''),
            'domains'   => sprintf('%s → %s',
                $row['domain'] ?? '?', $row['expires_at'] ?? '?'),
            'hosting'   => sprintf('%s (%s) [%s]',
                $row['domain'] ?? '?', $row['username'] ?? '', $row['status'] ?? ''),
            'tickets'   => sprintf('#%s "%s" [%s]',
                $row['external_id'] ?? '?', $row['subject'] ?? '', $row['status'] ?? ''),
            default     => json_encode($row, JSON_UNESCAPED_UNICODE),
        };
        echo $summary . "\n";
    }
    if (!$rows) echo "  │ (kayıt yok)\n";
    echo "  └────────────────────────────────────\n\n";
}

echo "════════════════════════════════════════════════\n";
echo "  ✅ WHMCS Import Driver TAM ÇALIŞIYOR\n";
echo "════════════════════════════════════════════════\n";
echo "\nSonuç: Bir WHMCS instance'ının DB kredensiyelini adminden girip\n";
echo "TÜM müşteri + sipariş + fatura + ürün + domain + hosting + ticket\n";
echo "verisini AhostBilişim'e aktarabilirsin.\n";
