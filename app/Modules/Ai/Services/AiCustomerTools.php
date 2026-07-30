<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;
use App\Modules\Cart\Services\CartService;

/**
 * Müşteri panelinde AI'nin gerçekten yapabileceği işlemler.
 * Hepsi self-service — müşteri sadece kendi verisine erişir.
 */
final class AiCustomerTools
{
    public static function register(): void
    {
        $reg = AiToolRegistry::class;

        // 1) Ticket aç
        $reg::register('customer', [
            'name'        => 'create_ticket',
            'description' => 'Yeni bir destek talebi oluştur. Konu ve mesaj zorunlu.',
            'params'      => [
                'subject'  => ['type' => 'string', 'description' => 'Talep konusu (kısa)'],
                'message'  => ['type' => 'string', 'description' => 'Talep detayı'],
                'priority' => ['type' => 'string', 'enum' => ['low','medium','high'], 'description' => 'Öncelik (varsayılan: medium)'],
            ],
            'required'    => ['subject', 'message'],
            'handler'     => function (array $a, ?int $uid) {
                $priority = $a['priority'] ?? 'medium';
                $id = Connection::insert('tickets', [
                    'customer_id' => $uid,
                    'department_id' => 1,
                    'subject'     => substr(trim((string)$a['subject']), 0, 190),
                    'status'      => 'open',
                    'priority'    => $priority,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                Connection::insert('ticket_replies', [
                    'ticket_id'   => $id,
                    'author_type' => 'customer',
                    'author_id'   => $uid,
                    'message'     => (string)$a['message'],
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return ['ok' => true, 'message' => "✅ Destek talebi #$id oluşturuldu.", 'redirect' => "/panel/destek/$id"];
            },
        ]);

        // 2) Şifre sıfırlama isteği (hosting/domain vb.)
        $reg::register('customer', [
            'name'        => 'request_password_reset',
            'description' => 'Bir hosting hesabı için şifre sıfırlama isteği başlat. Şifre e-postana gelir.',
            'params'      => [
                'service_id' => ['type' => 'integer', 'description' => 'Hosting hesap ID (müşteri paneli > hizmetler)'],
            ],
            'required'    => ['service_id'],
            'permission'  => fn($a, $uid) => (bool) Connection::selectOne(
                "SELECT 1 FROM hosting_accounts WHERE id = ? AND customer_id = ?",
                [(int)$a['service_id'], $uid]
            ),
            'handler'     => function (array $a, ?int $uid) {
                // Ticket olarak yönlendir — gerçek reset provisioning modülünde
                $srvId = (int) $a['service_id'];
                $id = Connection::insert('tickets', [
                    'customer_id' => $uid,
                    'department_id' => 1,
                    'subject'     => "Şifre sıfırlama — Hosting #$srvId",
                    'status'      => 'open',
                    'priority'    => 'high',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                Connection::insert('ticket_replies', [
                    'ticket_id'   => $id,
                    'author_type' => 'system',
                    'author_id'   => null,
                    'message'     => "AI Asistan üzerinden şifre sıfırlama talebi. Destek ekibi cPanel/DA üzerinden sıfırlayacak.",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return ['ok' => true, 'message' => "✅ Şifre sıfırlama talebi #$id oluşturuldu. E-postana yeni şifre gelecek."];
            },
        ]);

        // 3) Fatura ödeme başlat
        $reg::register('customer', [
            'name'        => 'pay_invoice',
            'description' => 'Ödenmemiş bir faturayı ödemek için ödeme sayfasına yönlendir.',
            'params'      => [
                'invoice_id' => ['type' => 'integer', 'description' => 'Fatura ID'],
            ],
            'required'    => ['invoice_id'],
            'permission'  => fn($a, $uid) => (bool) Connection::selectOne(
                "SELECT 1 FROM invoices WHERE id = ? AND customer_id = ? AND status IN ('unpaid','partial','overdue')",
                [(int)$a['invoice_id'], $uid]
            ),
            'handler'     => function (array $a) {
                $id = (int) $a['invoice_id'];
                return [
                    'ok'       => true,
                    'message'  => "💳 Fatura #$id için ödeme sayfasına yönlendiriliyorsun.",
                    'redirect' => "/panel/faturalar/$id/ode",
                ];
            },
        ]);

        // 4) Domain yenileme sepete ekle
        $reg::register('customer', [
            'name'        => 'renew_domain',
            'description' => 'Bir domaini yenilemek için sepete ekle.',
            'params'      => [
                'domain_id' => ['type' => 'integer', 'description' => 'Domain ID'],
                'years'     => ['type' => 'integer', 'description' => 'Kaç yıl yenilensin (1-10)'],
            ],
            'required'    => ['domain_id'],
            'permission'  => fn($a, $uid) => (bool) Connection::selectOne(
                "SELECT 1 FROM domains WHERE id = ? AND customer_id = ?",
                [(int)$a['domain_id'], $uid]
            ),
            'handler'     => function (array $a) {
                $id = (int) $a['domain_id'];
                $years = max(1, min(10, (int) ($a['years'] ?? 1)));
                $d = Connection::selectOne("SELECT domain_name FROM domains WHERE id = ?", [$id]);
                return [
                    'ok'       => true,
                    'message'  => "🌐 " . ($d['domain_name'] ?? "Domain #$id") . " için {$years} yıllık yenileme sepete eklendi.",
                    'redirect' => "/panel/domainlerim?renew=$id&years=$years",
                ];
            },
        ]);

        // 5) Hizmet detayı
        $reg::register('customer', [
            'name'        => 'my_services_summary',
            'description' => 'Aktif hizmetlerimin özetini göster (kaç hosting, kaç domain, sonraki ödeme).',
            'params'      => [],
            'handler'     => function (array $a, ?int $uid) {
                $hosting  = (int) (Connection::selectOne("SELECT COUNT(*) c FROM hosting_accounts WHERE customer_id = ? AND status='active'", [$uid])['c'] ?? 0);
                $domains  = (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE customer_id = ? AND status='active'", [$uid])['c'] ?? 0);
                $unpaid   = (int) (Connection::selectOne("SELECT COUNT(*) c FROM invoices WHERE customer_id = ? AND status IN ('unpaid','overdue')", [$uid])['c'] ?? 0);
                $unpaidTotal = (float) (Connection::selectOne("SELECT COALESCE(SUM(total),0) t FROM invoices WHERE customer_id = ? AND status IN ('unpaid','overdue')", [$uid])['t'] ?? 0);

                return [
                    'ok' => true,
                    'message' => "📊 **Hizmet Özeti**\n\n• 🖥 Aktif hosting: **$hosting**\n• 🌐 Aktif domain: **$domains**\n• 🧾 Ödenmemiş fatura: **$unpaid** (" . number_format($unpaidTotal, 2) . " TRY)",
                ];
            },
        ]);

        // 6) Panelde sayfa yönlendirme
        $reg::register('customer', [
            'name'        => 'navigate',
            'description' => 'Müşteri panelinde bir sayfaya git.',
            'params'      => [
                'page' => ['type' => 'string', 'enum' => ['hizmetler','domainler','faturalar','odemeler','destek','profil','guvenlik','referans'], 'description' => 'Sayfa adı'],
            ],
            'required'    => ['page'],
            'handler'     => function (array $a) {
                $map = [
                    'hizmetler' => '/panel/hizmetler',
                    'domainler' => '/panel/domainlerim',
                    'faturalar' => '/panel/faturalar',
                    'odemeler'  => '/panel/odemeler',
                    'destek'    => '/panel/destek',
                    'profil'    => '/panel/profil',
                    'guvenlik'  => '/panel/guvenlik',
                    'referans'  => '/panel/referans',
                ];
                $url = $map[$a['page']] ?? '/panel';
                return ['ok' => true, 'message' => "➡️ " . ucfirst($a['page']) . " sayfasına gidiliyor.", 'redirect' => $url];
            },
        ]);

        // 7) 2FA aç/kapat
        $reg::register('customer', [
            'name'        => 'toggle_2fa',
            'description' => 'İki faktörlü doğrulama açma/kapatma sayfasına git.',
            'params'      => [],
            'handler'     => fn() => ['ok' => true, 'message' => "🔐 2FA ayarları için güvenlik sayfası açılıyor.", 'redirect' => '/panel/guvenlik'],
        ]);
    }
}
