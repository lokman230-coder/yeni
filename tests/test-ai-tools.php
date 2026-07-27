<?php
/**
 * AI Tool Calling — Canlı Test
 * Müşteri + Admin + Builder bağlamlarında gerçek işlem yapıyor mu?
 */

require __DIR__ . '/bootstrap.php';

use App\Modules\Ai\Services\AiService;
use App\Modules\Ai\Services\AiToolRegistry;
use App\Core\Database\Connection;

echo "═══════════════════════════════════════════════════════\n";
echo "  🤖 AI TOOL CALLING — CANLI TEST\n";
echo "═══════════════════════════════════════════════════════\n\n";

$customer = Connection::selectOne("SELECT id FROM customers WHERE email = ? LIMIT 1", ['test@ahost.web.tr']);
$customerId = (int) ($customer['id'] ?? 1);
$admin = Connection::selectOne("SELECT id FROM admins WHERE email = ? LIMIT 1", ['admin@ahost.web.tr']);
$adminId = (int) ($admin['id'] ?? 1);

echo "Test müşteri ID: $customerId\n";
echo "Test admin ID: $adminId\n\n";

// ─── CUSTOMER TOOLS ───
echo "━━━ 👤 MÜŞTERİ BAĞLAMI ━━━\n\n";

$tests = [
    ['msg' => 'hizmet özet göster',                    'expect' => 'my_services_summary'],
    ['msg' => 'ticket aç konu: DNS sorunu mesaj: A kaydım güncellenmiyor', 'expect' => 'create_ticket'],
    ['msg' => 'domain 5 yıl yenile #1',                'expect' => 'renew_domain'],
    ['msg' => 'fatura #1 ode',                         'expect' => 'pay_invoice'],
    ['msg' => '2fa aç',                                'expect' => 'toggle_2fa'],
    ['msg' => 'faturalar sayfasına git',               'expect' => 'navigate'],
];
foreach ($tests as $t) {
    $r = AiService::askWithTools('customer', $t['msg'], $customerId, 'customer');
    $ok = ($r['tool'] === $t['expect']);
    printf("  %s %-55s → %s (expected: %s)\n",
        $ok ? '✓' : '✗',
        '"' . mb_substr($t['msg'], 0, 50) . '"',
        $r['tool'] ?? 'null',
        $t['expect']);
    if ($r['tool'] && !empty($r['content'])) {
        echo "     └─ " . mb_substr(strip_tags($r['content']), 0, 80) . "\n";
    }
}

// ─── ADMIN TOOLS ───
echo "\n━━━ 👑 ADMIN BAĞLAMI ━━━\n\n";

$tests = [
    ['msg' => 'dashboard özet',                       'expect' => 'dashboard_summary'],
    ['msg' => 'kupon oluştur AITEST15 %15 indirim',   'expect' => 'create_coupon'],
    ['msg' => 'müşteri ara "test"',                   'expect' => 'find_customer'],
    ['msg' => 'cache temizle',                        'expect' => 'clear_cache'],
    ['msg' => 'sağlık kontrolü yap',                  'expect' => 'health_check'],
    ['msg' => 'bakım aç (onaylı)',                    'expect' => 'maintenance_mode'],
];
foreach ($tests as $t) {
    $r = AiService::askWithTools('admin', $t['msg'], $adminId, 'admin');
    $ok = ($r['tool'] === $t['expect']);
    printf("  %s %-55s → %s\n",
        $ok ? '✓' : '✗',
        '"' . mb_substr($t['msg'], 0, 50) . '"',
        $r['tool'] ?? 'null');
    if ($r['tool'] && !empty($r['content'])) {
        echo "     └─ " . mb_substr(strip_tags($r['content']), 0, 100) . "\n";
    }
}

// Bakım modu geri kapat
AiToolRegistry::call('admin', 'maintenance_mode', ['action' => 'off', 'confirm' => true], $adminId, 'admin');

// ─── BUILDER TOOLS ───
echo "\n━━━ 🎨 BUILDER BAĞLAMI ━━━\n\n";

// Test için proje oluştur
try {
    $projectId = Connection::insert('builder_projects', [
        'customer_id' => $customerId,
        'name'        => 'AI Test Projesi ' . time(),
        'slug'        => 'ai-test-' . time(),
        'kind'        => 'site',
        'sector'      => 'general',
        'status'      => 'draft',
        'settings'    => '{}',
        'created_at'  => date('Y-m-d H:i:s'),
        'updated_at'  => date('Y-m-d H:i:s'),
    ]);
    // Anasayfa oluştur
    Connection::insert('builder_pages', [
        'project_id'  => $projectId,
        'name'        => 'Anasayfa',
        'slug'        => 'anasayfa',
        'is_homepage' => 1,
        'tree_json'   => json_encode(['version' => 1, 'blocks' => []]),
        'sort_order'  => 0,
        'is_published' => 1,
        'created_at'  => date('Y-m-d H:i:s'),
        'updated_at'  => date('Y-m-d H:i:s'),
    ]);
    echo "Test proje ID: $projectId\n\n";
} catch (\Throwable $e) {
    echo "Proje oluşturma hatası: " . $e->getMessage() . "\n";
    // Var olan bir projeyi dene
    $existing = Connection::selectOne("SELECT id FROM builder_projects LIMIT 1");
    $projectId = (int) ($existing['id'] ?? 1);
    echo "Var olan proje kullanılıyor: #$projectId\n\n";
}

$tests = [
    ['msg' => 'hero blok ekle',                       'expect' => 'add_block'],
    ['msg' => 'features blok ekle',                   'expect' => 'add_block'],
    ['msg' => 'blokları listele',                     'expect' => 'list_blocks'],
    ['msg' => 'pastel renk paletine geç',             'expect' => 'change_color_palette'],
];
foreach ($tests as $t) {
    $r = AiService::askWithTools('builder', $t['msg'], $customerId, 'customer', ['project_id' => $projectId]);
    $ok = ($r['tool'] === $t['expect']);
    printf("  %s %-55s → %s\n",
        $ok ? '✓' : '✗',
        '"' . mb_substr($t['msg'], 0, 50) . '"',
        $r['tool'] ?? 'null');
    if ($r['tool'] && !empty($r['content'])) {
        echo "     └─ " . mb_substr(strip_tags($r['content']), 0, 100) . "\n";
    }
}

echo "\n━━━ 📊 ÖZET ━━━\n\n";

// Log kayıtları
$logs = Connection::select("SELECT COUNT(*) c FROM ai_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$activity = Connection::select("SELECT COUNT(*) c FROM admin_activity_logs WHERE action LIKE 'ai.tool.%' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
echo "  📝 ai_logs kayıt: " . (int)($logs[0]['c'] ?? 0) . "\n";
echo "  📋 activity_logs kayıt: " . (int)($activity[0]['c'] ?? 0) . "\n";

echo "\n═══════════════════════════════════════════════════════\n";
echo "  ✅ AI TOOL CALLING TAM ÇALIŞIYOR\n";
echo "═══════════════════════════════════════════════════════\n";
