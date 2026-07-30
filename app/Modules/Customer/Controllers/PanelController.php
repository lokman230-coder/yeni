<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Services\Auth\AuthService;

/**
 * Müşteri paneli — Dashboard, Hizmetlerim, Faturalarım, Domainlerim, Siparişlerim.
 */
final class PanelController
{
    public function dashboard(Request $request): Response
    {
        $customer = AuthService::customer();
        $stats = self::stats((int) $customer['id']);
        $view = new View();
        return Response::html($view->render('customer::dashboard', [
            'title'    => 'Panel',
            'customer' => $customer,
            'stats'    => $stats,
        ]));
    }

    public function services(Request $request): Response
    {
        $customer = AuthService::customer();
        $rows = Connection::select(
            "SELECT ha.*, p.name AS product_name, s.hostname AS server_hostname, s.panel AS server_panel
             FROM hosting_accounts ha
             LEFT JOIN products p ON p.id = ha.product_id
             LEFT JOIN hosting_servers s ON s.id = ha.server_id
             WHERE ha.customer_id = ?
             ORDER BY ha.created_at DESC",
            [$customer['id']]
        );
        $view = new View();
        return Response::html($view->render('customer::services', [
            'title'    => 'Hizmetlerim',
            'services' => $rows,
        ]));
    }

    public function invoices(Request $request): Response
    {
        $customer = AuthService::customer();
        $rows = Connection::select(
            "SELECT * FROM invoices WHERE customer_id = ? ORDER BY issue_date DESC, id DESC",
            [$customer['id']]
        );
        $view = new View();
        return Response::html($view->render('customer::invoices', [
            'title'    => 'Faturalarım',
            'invoices' => $rows,
        ]));
    }

    public function domains(Request $request): Response
    {
        $customer = AuthService::customer();
        $rows = Connection::select(
            "SELECT d.*, r.name AS registrar_name
             FROM domains d
             LEFT JOIN domain_registrars r ON r.id = d.registrar_id
             WHERE d.customer_id = ?
             ORDER BY d.expiry_date ASC",
            [$customer['id']]
        );
        $view = new View();
        return Response::html($view->render('customer::domains', [
            'title'   => 'Domainlerim',
            'domains' => $rows,
        ]));
    }

    public function orders(Request $request): Response
    {
        $customer = AuthService::customer();
        $rows = Connection::select(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 100",
            [$customer['id']]
        );
        $view = new View();
        return Response::html($view->render('customer::orders', [
            'title'  => 'Siparişlerim',
            'orders' => $rows,
        ]));
    }

    public function serviceDetail(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $service = Connection::selectOne(
            "SELECT ha.*, p.name AS product_name, p.slug AS product_slug,
                    s.name AS server_name, s.hostname AS server_hostname, s.panel AS server_panel, s.port AS server_port
             FROM hosting_accounts ha
             LEFT JOIN products p ON p.id = ha.product_id
             LEFT JOIN hosting_servers s ON s.id = ha.server_id
             WHERE ha.id = ? AND ha.customer_id = ?
             LIMIT 1",
            [$id, $customer['id']]
        );
        if (!$service) return Response::notFound('Hizmet bulunamadı');

        // İlgili sipariş
        $order = null;
        if (!empty($service['order_item_id'])) {
            $order = Connection::selectOne(
                "SELECT o.* FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.id = ?",
                [$service['order_item_id']]
            );
        }

        // Son 30 günlük kullanım snapshot'ları (Chart.js için)
        $snapshots = Connection::select(
            "SELECT snap_date, disk_mb, bandwidth_mb FROM hosting_usage_snapshots
             WHERE account_id = ? AND snap_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             ORDER BY snap_date ASC",
            [$id]
        );

        $view = new View();
        return Response::html($view->render('customer::service_detail', [
            'title'     => 'Hizmet: ' . $service['domain'],
            'service'   => $service,
            'order'     => $order,
            'snapshots' => $snapshots,
        ]));
    }

    /** @return array<string,int|float> */
    public static function stats(int $customerId): array
    {
        try {
            return [
                'services_active'  => (int) (Connection::selectOne("SELECT COUNT(*) c FROM hosting_accounts WHERE customer_id = ? AND status = 'active'", [$customerId])['c'] ?? 0),
                'services_all'     => (int) (Connection::selectOne("SELECT COUNT(*) c FROM hosting_accounts WHERE customer_id = ?", [$customerId])['c'] ?? 0),
                'domains'          => (int) (Connection::selectOne("SELECT COUNT(*) c FROM domains WHERE customer_id = ? AND status = 'active'", [$customerId])['c'] ?? 0),
                'invoices_unpaid'  => (int) (Connection::selectOne("SELECT COUNT(*) c FROM invoices WHERE customer_id = ? AND status IN ('unpaid','partially_paid','overdue')", [$customerId])['c'] ?? 0),
                'invoices_all'     => (int) (Connection::selectOne("SELECT COUNT(*) c FROM invoices WHERE customer_id = ?", [$customerId])['c'] ?? 0),
                'unpaid_total'     => (float) (Connection::selectOne("SELECT COALESCE(SUM(balance),0) c FROM invoices WHERE customer_id = ? AND status IN ('unpaid','partially_paid','overdue')", [$customerId])['c'] ?? 0),
                'balance'          => (float) (Connection::selectOne("SELECT COALESCE(balance,0) c FROM customers WHERE id = ?", [$customerId])['c'] ?? 0),
            ];
        } catch (\Throwable) {
            return ['services_active' => 0, 'services_all' => 0, 'domains' => 0, 'invoices_unpaid' => 0, 'invoices_all' => 0, 'unpaid_total' => 0, 'balance' => 0];
        }
    }

    // ═══════════════════════════════════════════════════════
    //  SAKLANAN KARTLAR
    // ═══════════════════════════════════════════════════════

    public function cards(Request $request): Response
    {
        $customer = AuthService::customer();
        $cards = Connection::select(
            "SELECT * FROM stored_cards WHERE customer_id = ? ORDER BY is_default DESC, id DESC",
            [$customer['id']]
        );
        return Response::html((new View())->render('customer::cards', [
            'title' => 'Kartlarım',
            'cards' => $cards,
        ]));
    }

    public function toggleAutoBilling(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $card = Connection::selectOne("SELECT * FROM stored_cards WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$card) return Response::notFound();

        $enabled = (int) (bool) $request->input('enabled', 0);
        Connection::update('stored_cards', ['auto_billing_enabled' => $enabled, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        SessionManager::flash('success', $enabled ? '✓ Otomatik tahsilat AÇILDI' : 'Otomatik tahsilat kapatıldı');
        return Response::redirect('/panel/kartlar');
    }

    public function setDefaultCard(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        Connection::query("UPDATE stored_cards SET is_default = 0 WHERE customer_id = ?", [$customer['id']]);
        Connection::query("UPDATE stored_cards SET is_default = 1 WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        SessionManager::flash('success', 'Varsayılan kart güncellendi.');
        return Response::redirect('/panel/kartlar');
    }

    public function deleteCard(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        Connection::query("DELETE FROM stored_cards WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        SessionManager::flash('success', 'Kart silindi.');
        return Response::redirect('/panel/kartlar');
    }

    // ═══════════════════════════════════════════════════════
    //  BAKİYE / KREDİT
    // ═══════════════════════════════════════════════════════

    public function credit(Request $request): Response
    {
        $customer = AuthService::customer();
        $balance = (float) (Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$customer['id']])['balance'] ?? 0);
        $credits = \App\Services\Credit\CreditService::history((int)$customer['id'], 100);

        return Response::html((new View())->render('customer::credit', [
            'title'   => 'Bakiyem',
            'balance' => $balance,
            'credits' => $credits,
        ]));
    }

    public function creditTopUp(Request $request): Response
    {
        $customer = AuthService::customer();
        $amount = (float) $request->input('amount', 0);
        $method = (string) $request->input('method', 'paytr');

        if ($amount < 10 || $amount > 50000) {
            SessionManager::flash('error', 'Tutar 10-50000 TL arası olmalı');
            return Response::redirect('/panel/bakiye');
        }

        // Havale ise: bekleyen payment kaydet + havale bilgilerini göster
        if ($method === 'bank_transfer') {
            $companyName = \App\Services\Settings\SettingsManager::get('company.name', 'Ahost Bilişim');
            $iban        = \App\Services\Settings\SettingsManager::get('company.iban', 'Ayarlar > Firma\'dan IBAN gir');
            $bankName    = \App\Services\Settings\SettingsManager::get('company.bank_name', '');

            SessionManager::flash('info', "Havale ile $amount TL yükleme için:\n" .
                "IBAN: $iban ($bankName)\n" .
                "Açıklama: BAKIYE-{$customer['id']}\n" .
                "Havale sonrası admin onayı ile bakiyeniz yüklenir.");
            return Response::redirect('/panel/bakiye');
        }

        // PayTR / iyzico ile ödeme başlat
        // Basitleştirilmiş: internal "credit_topup" order oluştur, checkout'a yönlendir
        try {
            $orderId = Connection::insert('orders', [
                'order_number'   => 'CRE-' . date('YmdHis') . '-' . random_int(100, 999),
                'customer_id'    => $customer['id'],
                'status'         => 'pending',
                'subtotal'       => $amount,
                'tax_total'      => 0,
                'total'          => $amount,
                'currency'       => 'TRY',
                'payment_method' => $method,
                'notes'          => 'Bakiye yükleme',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            // Fatura oluştur (bakiye yükleme faturası)
            $invId = Connection::insert('invoices', [
                'invoice_number' => 'BKY-' . date('YmdHis'),
                'order_id'       => $orderId,
                'customer_id'    => $customer['id'],
                'status'         => 'unpaid',
                'issue_date'     => date('Y-m-d'),
                'due_date'       => date('Y-m-d', time() + 7 * 86400),
                'subtotal'       => $amount,
                'total'          => $amount,
                'balance'        => $amount,
                'currency'       => 'TRY',
                'notes'          => 'Bakiye yükleme siparişi',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            SessionManager::flash('info', 'Ödeme sayfasına yönlendiriliyorsun...');
            return Response::redirect('/odeme/' . $invId);
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Sipariş oluşturulamadı: ' . $e->getMessage());
            return Response::redirect('/panel/bakiye');
        }
    }

    // ═══════════════════════════════════════════════════════
    //  ÖDEMELER
    // ═══════════════════════════════════════════════════════

    public function payments(Request $request): Response
    {
        $customer = AuthService::customer();
        $payments = Connection::select(
            "SELECT p.*, i.invoice_number
             FROM payments p
             LEFT JOIN invoices i ON i.id = p.invoice_id
             WHERE p.customer_id = ?
             ORDER BY p.id DESC
             LIMIT 200",
            [$customer['id']]
        );
        return Response::html((new View())->render('customer::payments', [
            'title'    => 'Ödemelerim',
            'payments' => $payments,
        ]));
    }

    // ═══════════════════════════════════════════════════════
    //  DOMAIN YÖNETİMİ (Müşteri)
    // ═══════════════════════════════════════════════════════

    public function domainDetail(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne(
            "SELECT d.*, r.name AS registrar_name FROM domains d
             LEFT JOIN domain_registrars r ON r.id = d.registrar_id
             WHERE d.id = ? AND d.customer_id = ?",
            [$id, $customer['id']]
        );
        if (!$domain) return Response::notFound();

        return Response::html((new View())->render('customer::domain_detail', [
            'title'  => $domain['domain_name'],
            'domain' => $domain,
        ]));
    }

    public function updateNameservers(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        $ns = (string) $request->input('nameservers', '');
        $lines = array_values(array_filter(array_map('trim', explode("\n", $ns))));
        if (count($lines) < 2) {
            SessionManager::flash('error', 'En az 2 nameserver girmelisin.');
            return Response::redirect('/panel/domain/' . $id);
        }
        $clean = implode("\n", array_slice($lines, 0, 5));
        Connection::update('domains', ['nameservers' => $clean, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        \App\Services\Logger\ActivityLog::log('domain.nameservers_updated', 'domain', $id, "NS: " . implode(', ', $lines));
        SessionManager::flash('success', '✓ Nameserver\'lar güncellendi. Yayılma 4-24 saat sürebilir.');
        return Response::redirect('/panel/domain/' . $id);
    }

    public function toggleAutoRenew(Request $request): Response
    {
        return $this->toggleDomainFlag($request, 'auto_renew');
    }

    public function toggleTransferLock(Request $request): Response
    {
        return $this->toggleDomainFlag($request, 'transfer_lock');
    }

    public function toggleWhoisPrivacy(Request $request): Response
    {
        return $this->toggleDomainFlag($request, 'whois_privacy');
    }

    private function toggleDomainFlag(Request $request, string $flag): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        $new = (int) $domain[$flag] === 1 ? 0 : 1;
        Connection::update('domains', [$flag => $new, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        \App\Services\Logger\ActivityLog::log("domain.$flag", 'domain', $id, "$flag = $new");
        $labels = ['auto_renew' => 'Otomatik yenileme', 'transfer_lock' => 'Transfer kilidi', 'whois_privacy' => 'WHOIS gizliliği'];
        SessionManager::flash('success', $labels[$flag] . ' ' . ($new ? 'AÇILDI ✓' : 'KAPATILDI'));
        return Response::redirect('/panel/domain/' . $id);
    }

    public function requestEpp(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        if (!empty($domain['epp_code'])) {
            SessionManager::flash('info', 'EPP kodu zaten mevcut: ' . $domain['epp_code']);
            return Response::redirect('/panel/domain/' . $id);
        }

        // Basitleştirilmiş: rastgele 16 karakter EPP kodu üret + e-postaya yolla
        $eppCode = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
        Connection::update('domains', ['epp_code' => $eppCode, 'transfer_lock' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        // Mail queue'ya yolla
        try {
            Connection::insert('mail_queue', [
                'to_email'   => $customer['email'],
                'subject'    => "Domain Transfer Kodu — {$domain['domain_name']}",
                'body_html'  => "<p>Sayın müşterimiz,</p><p><strong>{$domain['domain_name']}</strong> için EPP transfer kodunuz:</p><p style='font-size:20px;font-family:monospace;background:#f3f4f6;padding:12px;border-radius:6px'><strong>$eppCode</strong></p><p>Transfer kilidi otomatik olarak kapatıldı. Yeni registrarda bu kodu girip transferi başlatabilirsiniz.</p><p>Kod 30 gün geçerlidir.</p>",
                'status'     => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {}

        // SMS de yolla (telefon varsa + SMS driver aktifse)
        if (!empty($customer['phone']) && class_exists(\App\Services\Sms\SmsManager::class)) {
            try {
                \App\Services\Sms\SmsManager::send(
                    (string) $customer['phone'],
                    "Ahost: {$domain['domain_name']} EPP transfer kodunuz: $eppCode (30 gun gecerli). Transfer kilidi kapatildi."
                );
            } catch (\Throwable) {}
        }

        \App\Services\Logger\ActivityLog::log('domain.epp_generated', 'domain', $id, "EPP alındı: {$domain['domain_name']}");
        SessionManager::flash('success', "✓ EPP kodu oluşturuldu ve e-postana gönderildi. Transfer kilidi de kapatıldı.");
        return Response::redirect('/panel/domain/' . $id);
    }

    public function renewDomain(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $years = max(1, min(10, (int) $request->input('years', 1)));

        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        $amount = (float) $domain['recurring_amount'] * $years;
        if ($amount <= 0) $amount = 89.00 * $years;

        // Yenileme siparişi/faturası oluştur
        try {
            $orderId = Connection::insert('orders', [
                'order_number'   => 'REN-' . date('YmdHis') . '-' . random_int(100, 999),
                'customer_id'    => $customer['id'],
                'status'         => 'pending',
                'subtotal'       => $amount,
                'tax_total'      => 0,
                'total'          => $amount,
                'currency'       => $domain['currency'] ?? 'TRY',
                'notes'          => "Domain yenileme: {$domain['domain_name']} ($years yıl)",
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            Connection::insert('order_items', [
                'order_id'      => $orderId,
                'product_id'    => 0,
                'product_name'  => "Domain Yenileme: {$domain['domain_name']}",
                'period'        => 'annually',
                'quantity'      => $years,
                'domain_name'   => $domain['domain_name'],
                'unit_price'    => $amount / $years,
                'line_total'    => $amount,
                'currency'      => $domain['currency'] ?? 'TRY',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $invId = Connection::insert('invoices', [
                'invoice_number' => 'INV-' . date('YmdHis'),
                'order_id'       => $orderId,
                'customer_id'    => $customer['id'],
                'status'         => 'unpaid',
                'issue_date'     => date('Y-m-d'),
                'due_date'       => date('Y-m-d', time() + 7 * 86400),
                'subtotal'       => $amount,
                'total'          => $amount,
                'balance'        => $amount,
                'currency'       => $domain['currency'] ?? 'TRY',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            SessionManager::flash('success', "✓ Yenileme faturası oluşturuldu. Ödeme sonrası domain otomatik yenilenir.");
            return Response::redirect('/odeme/' . $invId);
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Yenileme başarısız: ' . $e->getMessage());
            return Response::redirect('/panel/domain/' . $id);
        }
    }

    /**
     * Müşteri kendi hosting hesabının şifresini görür (encrypted → plain).
     * Her istek activity log'a düşer.
     */
    public function revealPassword(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }
        $customer = AuthService::customer();
        $id = (int) $request->param('id');

        $service = Connection::selectOne(
            "SELECT id, password_encrypted, domain FROM hosting_accounts WHERE id = ? AND customer_id = ? LIMIT 1",
            [$id, $customer['id']]
        );
        if (!$service) {
            return Response::json(['ok' => false, 'error' => 'Hizmet bulunamadı']);
        }
        if (empty($service['password_encrypted'])) {
            return Response::json(['ok' => false, 'error' => 'Şifre henüz oluşturulmadı. Destek talebi açın.']);
        }

        try {
            $plain = \App\Support\Encrypter::decrypt((string) $service['password_encrypted']);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => 'Şifre çözülemedi']);
        }

        // Audit
        try {
            \App\Services\Logger\ActivityLog::log(
                'hosting.password_revealed',
                'hosting_account',
                (int) $service['id'],
                "Müşteri {$customer['email']} kendi hosting şifresini görüntüledi ({$service['domain']})",
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]
            );
        } catch (\Throwable) {}

        return Response::json(['ok' => true, 'password' => $plain]);
    }

    // ═══════════════════════════════════════════════════════
    //  DOMAIN BELGELERİ (.com.tr için TCKN/vergi/marka)
    // ═══════════════════════════════════════════════════════

    public function domainDocuments(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        // TLD tespit
        $parts = explode('.', (string)$domain['domain_name'], 2);
        $tld = $parts[1] ?? 'com';
        $req = \App\Services\Domain\TldPricingService::requiresDocuments($tld);

        $documents = Connection::select("SELECT * FROM domain_documents WHERE domain_id = ? ORDER BY id DESC", [$id]);

        return Response::html((new View())->render('customer::domain_documents', [
            'title'         => 'Belgeler — ' . $domain['domain_name'],
            'domain'        => $domain,
            'tld'           => $tld,
            'requiredDocs'  => $req['documents'] ?: ['tckn'],
            'documents'     => $documents,
        ]));
    }

    public function uploadDomainDocument(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $domain = Connection::selectOne("SELECT * FROM domains WHERE id = ? AND customer_id = ?", [$id, $customer['id']]);
        if (!$domain) return Response::notFound();

        $type = (string) $request->input('document_type', '');
        $number = trim((string) $request->input('document_number', ''));
        $file = $_FILES['document_file'] ?? null;

        if (!in_array($type, ['tckn','tax_id','trademark_cert','id_card','company_reg','domain_owner_doc'], true)) {
            SessionManager::flash('error', 'Geçersiz belge tipi.');
            return Response::redirect("/panel/domain/$id/belgeler");
        }

        $filePath = null; $fileName = null; $mime = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 5 * 1024 * 1024) {
                SessionManager::flash('error', 'Dosya 5 MB\'dan büyük olamaz.');
                return Response::redirect("/panel/domain/$id/belgeler");
            }
            $mime = mime_content_type($file['tmp_name']) ?: '';
            $ok = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
            if (!isset($ok[$mime])) {
                SessionManager::flash('error', 'Sadece PDF/JPG/PNG kabul edilir.');
                return Response::redirect("/panel/domain/$id/belgeler");
            }
            $dir = AHO_ROOT . '/storage/uploads/domain_docs/' . $customer['id'];
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $fileName = bin2hex(random_bytes(8)) . '_' . $type . '.' . $ok[$mime];
            $filePath = $dir . '/' . $fileName;
            move_uploaded_file($file['tmp_name'], $filePath);
        }

        Connection::insert('domain_documents', [
            'domain_id'       => $id,
            'customer_id'     => (int)$customer['id'],
            'document_type'   => $type,
            'document_number' => $number ?: null,
            'file_path'       => $filePath,
            'file_name'       => $fileName,
            'file_mime'       => $mime,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        \App\Services\Logger\ActivityLog::log('domain.document_uploaded', 'domain', $id, "$type belgesi yüklendi");
        SessionManager::flash('success', '✓ Belge yüklendi, inceleme bekliyor.');
        return Response::redirect("/panel/domain/$id/belgeler");
    }

    // ═══════════════════════════════════════════════════════
    //  DOMAIN BACKORDER
    // ═══════════════════════════════════════════════════════

    public function backorderList(Request $request): Response
    {
        $customer = AuthService::customer();
        $items = Connection::select("SELECT * FROM domain_backorders WHERE customer_id = ? ORDER BY id DESC", [$customer['id']]);
        return Response::html((new View())->render('customer::backorders', [
            'title' => 'Backorder Listem',
            'items' => $items,
        ]));
    }

    public function backorderAdd(Request $request): Response
    {
        $customer = AuthService::customer();
        $domainName = strtolower(trim((string) $request->input('domain_name', '')));
        $mode = in_array($request->input('mode'), ['notify_only','auto_catch'], true) ? $request->input('mode') : 'notify_only';
        $maxBid = (float) $request->input('max_bid', 0);

        if ($domainName === '') {
            SessionManager::flash('error', 'Domain adı zorunludur.');
            return Response::redirect('/panel/backorder');
        }

        // Duplicate kontrol
        $existing = Connection::selectOne("SELECT id FROM domain_backorders WHERE customer_id = ? AND domain_name = ? AND status IN ('watching','triggered')", [$customer['id'], $domainName]);
        if ($existing) {
            SessionManager::flash('info', 'Bu domain zaten takip listende.');
            return Response::redirect('/panel/backorder');
        }

        Connection::insert('domain_backorders', [
            'customer_id' => (int)$customer['id'],
            'domain_name' => $domainName,
            'mode'        => $mode,
            'max_bid'     => $mode === 'auto_catch' ? $maxBid : null,
            'currency'    => 'TRY',
            'status'      => 'watching',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        SessionManager::flash('success', "✓ $domainName takibe alındı. Boşalırsa e-posta + SMS ile bildirilirsin.");
        return Response::redirect('/panel/backorder');
    }

    public function backorderCancel(Request $request): Response
    {
        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        Connection::update('domain_backorders', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND customer_id = ?', [$id, $customer['id']]);
        SessionManager::flash('success', 'Takip iptal edildi.');
        return Response::redirect('/panel/backorder');
    }

    // ═══════════════════════════════════════════════════════
    //  VENDOR PANELİ (Marketplace satıcısı)
    // ═══════════════════════════════════════════════════════

    public function vendorPanel(Request $request): Response
    {
        $customer = AuthService::customer();
        $vendor = Connection::selectOne("SELECT * FROM vendors WHERE customer_id = ?", [$customer['id']]);

        if (!$vendor) {
            // Başvuru formu göster
            return Response::html((new View())->render('customer::vendor_apply', [
                'title' => 'Marketplace Satıcısı Ol',
            ]));
        }

        $listings = Connection::select("SELECT * FROM marketplace_listings WHERE vendor_id = ? ORDER BY id DESC LIMIT 50", [$vendor['id']]);
        $earnings = Connection::select("SELECT * FROM vendor_earnings WHERE vendor_id = ? ORDER BY id DESC LIMIT 50", [$vendor['id']]);
        $payouts  = Connection::select("SELECT * FROM vendor_payouts WHERE vendor_id = ? ORDER BY id DESC", [$vendor['id']]);
        $available = \App\Services\Marketplace\VendorService::availableBalance((int)$vendor['id']);

        return Response::html((new View())->render('customer::vendor_dashboard', [
            'title'    => 'Satıcı Paneli — ' . $vendor['shop_name'],
            'vendor'   => $vendor,
            'listings' => $listings,
            'earnings' => $earnings,
            'payouts'  => $payouts,
            'available'=> $available,
        ]));
    }

    public function vendorApply(Request $request): Response
    {
        $customer = AuthService::customer();
        $result = \App\Services\Marketplace\VendorService::apply((int)$customer['id'], [
            'shop_name'     => (string) $request->input('shop_name', ''),
            'description'   => (string) $request->input('description', ''),
            'contact_email' => (string) $request->input('contact_email', $customer['email']),
            'contact_phone' => (string) $request->input('contact_phone', ''),
            'website'       => (string) $request->input('website', ''),
            'city'          => (string) $request->input('city', ''),
            'tax_id'        => (string) $request->input('tax_id', ''),
            'iban'          => (string) $request->input('iban', ''),
            'iban_holder'   => (string) $request->input('iban_holder', ''),
        ]);
        if (!$result['ok']) {
            SessionManager::flash('error', $result['error']);
            return Response::redirect('/panel/satici');
        }
        SessionManager::flash('success', '✓ Başvurun alındı. Admin ekibi 1-3 iş günü içinde inceleyecek.');
        return Response::redirect('/panel/satici');
    }

    public function vendorPayoutRequest(Request $request): Response
    {
        $customer = AuthService::customer();
        $vendor = Connection::selectOne("SELECT * FROM vendors WHERE customer_id = ?", [$customer['id']]);
        if (!$vendor) return Response::notFound();

        $amount = (float) $request->input('amount', 0);
        $result = \App\Services\Marketplace\VendorService::requestPayout((int)$vendor['id'], $amount);
        SessionManager::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? '✓ Payout talebin oluşturuldu.' : $result['error']);
        return Response::redirect('/panel/satici');
    }
}
