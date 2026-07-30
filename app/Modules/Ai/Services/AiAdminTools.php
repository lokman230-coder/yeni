<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;

/**
 * Admin panelinde AI'nin yapabileceği işlemler.
 * Yıkıcı işlemler için "confirm=true" gerekli.
 */
final class AiAdminTools
{
    public static function register(): void
    {
        $reg = AiToolRegistry::class;

        // 1) Kupon oluştur
        $reg::register('admin', [
            'name'        => 'create_coupon',
            'description' => 'Yeni indirim kuponu oluştur.',
            'params'      => [
                'code'         => ['type' => 'string', 'description' => 'Kupon kodu (örn: WELCOME10)'],
                'discount_pct' => ['type' => 'number', 'description' => 'İndirim yüzdesi (1-90)'],
                'expires_days' => ['type' => 'integer', 'description' => 'Kaç gün geçerli (varsayılan 30)'],
                'max_uses'     => ['type' => 'integer', 'description' => 'Maks kullanım (0 = sınırsız)'],
            ],
            'required'    => ['code', 'discount_pct'],
            'handler'     => function (array $a) {
                $code = strtoupper(trim((string)$a['code']));
                $pct  = max(1, min(90, (int) $a['discount_pct']));
                $days = max(1, (int) ($a['expires_days'] ?? 30));
                $maxU = max(0, (int) ($a['max_uses'] ?? 0));

                $id = Connection::insert('coupons', [
                    'code'         => $code,
                    'name'         => "AI ile oluşturuldu — %$pct indirim",
                    'type'         => 'percent',
                    'value'        => $pct,
                    'usage_limit'  => $maxU ?: null,
                    'ends_at'      => date('Y-m-d H:i:s', time() + $days * 86400),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                return ['ok' => true, 'message' => "🎟 Kupon **$code** oluşturuldu (%$pct indirim, $days gün geçerli).", 'redirect' => "/admin/kuponlar/$id/duzenle"];
            },
        ]);

        // 2) Dashboard özet
        $reg::register('admin', [
            'name'        => 'dashboard_summary',
            'description' => 'Genel dashboard özeti: gelir, sipariş, müşteri, ticket sayıları.',
            'params'      => [],
            'handler'     => function () {
                $rev30  = (float) (Connection::selectOne("SELECT COALESCE(SUM(total),0) t FROM invoices WHERE status='paid' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")['t'] ?? 0);
                $ord30  = (int) (Connection::selectOne("SELECT COUNT(*) c FROM orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'] ?? 0);
                $newCu  = (int) (Connection::selectOne("SELECT COUNT(*) c FROM customers WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'] ?? 0);
                $openT  = (int) (Connection::selectOne("SELECT COUNT(*) c FROM tickets WHERE status IN ('open','answered')")['c'] ?? 0);
                $unpaid = (float) (Connection::selectOne("SELECT COALESCE(SUM(total),0) t FROM invoices WHERE status IN ('unpaid','overdue')")['t'] ?? 0);

                return [
                    'ok' => true,
                    'message' => "📊 **Son 30 Gün Özeti**\n\n• 💰 Gelir: **" . number_format($rev30, 2) . " TRY**\n• 📦 Sipariş: **$ord30**\n• 👥 Yeni müşteri: **$newCu**\n• 🎫 Açık talep: **$openT**\n• 🧾 Ödenmemiş: **" . number_format($unpaid, 2) . " TRY**",
                ];
            },
        ]);

        // 3) Müşteri ara
        $reg::register('admin', [
            'name'        => 'find_customer',
            'description' => 'E-posta, telefon veya isimle müşteri ara.',
            'params'      => [
                'query' => ['type' => 'string', 'description' => 'Arama sorgusu'],
            ],
            'required'    => ['query'],
            'handler'     => function (array $a) {
                $q = '%' . trim((string)$a['query']) . '%';
                $rows = Connection::select(
                    "SELECT id, email, first_name, last_name, phone
                     FROM customers
                     WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?
                     ORDER BY id DESC LIMIT 5",
                    [$q, $q, $q, $q]
                );
                if (!$rows) return ['ok' => true, 'message' => "🔍 Eşleşen müşteri bulunamadı."];
                $lines = ["🔍 **" . count($rows) . " sonuç:**\n"];
                foreach ($rows as $r) {
                    $lines[] = "• #{$r['id']} " . trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) . " <{$r['email']}> — {$r['phone']}";
                }
                return ['ok' => true, 'message' => implode("\n", $lines), 'data' => $rows];
            },
        ]);

        // 4) Bakım moduna al
        $reg::register('admin', [
            'name'        => 'maintenance_mode',
            'description' => 'Bakım modunu aç veya kapat. Yıkıcı işlem — confirm gerekir.',
            'params'      => [
                'action'  => ['type' => 'string', 'enum' => ['on','off'], 'description' => 'on veya off'],
                'confirm' => ['type' => 'boolean', 'description' => 'Onay'],
            ],
            'required'    => ['action'],
            'destructive' => true,
            'handler'     => function (array $a) {
                $lock = __DIR__ . '/../../../../storage/maintenance.lock';
                if ($a['action'] === 'on') {
                    file_put_contents($lock, date('Y-m-d H:i:s') . " — AI ile açıldı\n");
                    return ['ok' => true, 'message' => "⚠️ Bakım modu **AÇILDI**. Public site kapalı."];
                }
                @unlink($lock);
                return ['ok' => true, 'message' => "✅ Bakım modu **KAPATILDI**."];
            },
        ]);

        // 5) Ödenmemiş fatura hatırlatma yolla
        $reg::register('admin', [
            'name'        => 'send_payment_reminders',
            'description' => 'Ödenmemiş faturası olan müşterilere hatırlatma maili kuyruğa at.',
            'params'      => [
                'confirm' => ['type' => 'boolean', 'description' => 'Onay'],
            ],
            'destructive' => true,
            'handler'     => function () {
                $rows = Connection::select(
                    "SELECT i.id AS invoice_id, c.email, c.first_name, i.total, i.due_date
                     FROM invoices i JOIN customers c ON c.id = i.customer_id
                     WHERE i.status IN ('unpaid','overdue')
                     LIMIT 500"
                );
                $sent = 0;
                foreach ($rows as $r) {
                    try {
                        Connection::insert('mail_queue', [
                            'to_email'   => $r['email'],
                            'subject'    => 'Fatura Hatırlatma — #' . $r['invoice_id'],
                            'body_html'  => "Merhaba " . ($r['first_name'] ?? '') . ",<br><br>Fatura #{$r['invoice_id']} — {$r['total']} TRY hâlâ ödenmedi. Vade: {$r['due_date']}.",
                            'status'     => 'pending',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        $sent++;
                    } catch (\Throwable) {}
                }
                return ['ok' => true, 'message' => "📧 **$sent** hatırlatma maili kuyruğa alındı. Cron çalıştırınca gönderilir."];
            },
        ]);

        // 6) Cache temizle
        $reg::register('admin', [
            'name'        => 'clear_cache',
            'description' => 'Uygulama cache\'ini temizle.',
            'params'      => [],
            'handler'     => function () {
                $dir = __DIR__ . '/../../../../storage/cache';
                $count = 0;
                if (is_dir($dir)) {
                    foreach (glob($dir . '/*') as $f) {
                        if (is_file($f)) { @unlink($f); $count++; }
                    }
                }
                return ['ok' => true, 'message' => "🧹 Cache temizlendi ($count dosya)."];
            },
        ]);

        // 7) Health check
        $reg::register('admin', [
            'name'        => 'health_check',
            'description' => 'Sistem sağlık kontrolünü çalıştır ve sonucu göster.',
            'params'      => [],
            'handler'     => function () {
                exec('cd ' . escapeshellarg(realpath(__DIR__ . '/../../../..')) . ' && php console health:check 2>&1', $out, $rc);
                $text = strip_tags(implode("\n", array_slice($out, 0, 20)));
                return ['ok' => true, 'message' => "🩺 **Sağlık Kontrolü**\n```\n$text\n```"];
            },
        ]);

        // 8) Admin panelde sayfa yönlendirme
        $reg::register('admin', [
            'name'        => 'navigate',
            'description' => 'Admin panelinde bir sayfaya git.',
            'params'      => [
                'page' => ['type' => 'string', 'enum' => ['dashboard','musteriler','urunler','siparisler','faturalar','destek','ayarlar','kuponlar','yedekleme','ai-center','veri-aktarimi','paket-opsiyonlari','hosting-sunucu','domain-center','blog'], 'description' => 'Sayfa adı'],
            ],
            'required'    => ['page'],
            'handler'     => function (array $a) {
                $map = [
                    'dashboard'         => '/admin/',
                    'musteriler'        => '/admin/musteriler',
                    'urunler'           => '/admin/urun-merkezi',
                    'siparisler'        => '/admin/siparisler',
                    'faturalar'         => '/admin/finans',
                    'destek'            => '/admin/destek-merkezi',
                    'ayarlar'           => '/admin/ayarlar',
                    'kuponlar'          => '/admin/kuponlar',
                    'yedekleme'         => '/admin/yedekleme',
                    'ai-center'         => '/admin/ai-center',
                    'veri-aktarimi'     => '/admin/veri-aktarimi',
                    'paket-opsiyonlari' => '/admin/paket-opsiyonlari',
                    'hosting-sunucu'    => '/admin/hosting-sunucu',
                    'domain-center'     => '/admin/domain-center',
                    'blog'              => '/admin/blog',
                ];
                $url = $map[$a['page']] ?? '/admin/';
                return ['ok' => true, 'message' => "➡️ " . ucfirst(str_replace('-',' ',$a['page'])) . " sayfasına gidiliyor.", 'redirect' => $url];
            },
        ]);
    }
}
