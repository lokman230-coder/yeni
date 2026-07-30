<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Auth\ImpersonationService;

final class AdminCustomerController
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $sql = 'SELECT id, email, first_name, last_name, phone, company, status, email_verified_at, created_at, last_login_at
                FROM customers';
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR company LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like, $like];
        }
        $sql .= ' ORDER BY id DESC LIMIT 200';

        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);
        $customers = $st->fetchAll();

        return Response::html((new View())->render('admin::customers.index', [
            'title'     => 'Müşteriler',
            'customers' => $customers,
            'q'         => $q,
        ]));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $customer = Connection::selectOne('SELECT * FROM customers WHERE id = ?', [$id]);
        if (!$customer) {
            return Response::notFound();
        }

        $pdo = Connection::pdo();
        $orders = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 50');
        $orders->execute([$id]);

        $invoices = $pdo->prepare('SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC LIMIT 50');
        $invoices->execute([$id]);

        $tickets = $pdo->prepare('SELECT * FROM tickets WHERE customer_id = ? ORDER BY id DESC LIMIT 50');
        $tickets->execute([$id]);

        $hosting = $pdo->prepare(
            'SELECT ha.*, p.name AS product_name, s.name AS server_name, s.hostname AS server_hostname, s.panel AS server_panel, s.port AS server_port,
                    oi.line_total AS order_amount, oi.period AS billing_period
             FROM hosting_accounts ha
             LEFT JOIN products p ON p.id = ha.product_id
             LEFT JOIN hosting_servers s ON s.id = ha.server_id
             LEFT JOIN order_items oi ON oi.id = ha.order_item_id
             WHERE ha.customer_id = ?
             ORDER BY ha.id DESC LIMIT 50'
        );
        $hosting->execute([$id]);

        $ordersRows = $orders->fetchAll();
        $invoiceRows = $invoices->fetchAll();
        $ticketRows = $tickets->fetchAll();
        $hostingRows = $hosting->fetchAll();

        $domains = [];
        if (self::tableExists('domains')) {
            $domains = Connection::select(
                'SELECT d.*, r.name AS registrar_name
                 FROM domains d
                 LEFT JOIN domain_registrars r ON r.id = d.registrar_id
                 WHERE d.customer_id = ?
                 ORDER BY d.id DESC LIMIT 50',
                [$id]
            );
        }

        $credits = [];
        if (self::tableExists('customer_credits')) {
            $credits = Connection::select(
                'SELECT * FROM customer_credits WHERE customer_id = ? ORDER BY id DESC LIMIT 50',
                [$id]
            );
        }

        $activity = [];
        if (self::tableExists('admin_activity_logs')) {
            $activity = Connection::select(
                "SELECT id, admin_email, action, resource_type, resource_id, summary, ip, created_at
                 FROM admin_activity_logs
                 WHERE (resource_type = 'customer' AND resource_id = ?)
                    OR summary LIKE ?
                 ORDER BY id DESC LIMIT 30",
                [$id, '%' . (string) ($customer['email'] ?? '') . '%']
            );
        }

        $notes = [];
        if (self::tableExists('customer_notes')) {
            $notes = Connection::select(
                'SELECT * FROM customer_notes WHERE customer_id = ? ORDER BY is_sticky DESC, id DESC LIMIT 100',
                [$id]
            );
        }

        $contacts = [];
        if (self::tableExists('customer_contacts')) {
            $contacts = Connection::select(
                'SELECT * FROM customer_contacts WHERE customer_id = ? ORDER BY id DESC LIMIT 100',
                [$id]
            );
        }

        $billableItems = [];
        if (self::tableExists('billable_items')) {
            $billableItems = Connection::select(
                'SELECT * FROM billable_items WHERE customer_id = ? ORDER BY id DESC LIMIT 100',
                [$id]
            );
        }

        $quotes = [];
        if (self::tableExists('quotes')) {
            $quotes = Connection::select(
                'SELECT * FROM quotes WHERE customer_id = ? ORDER BY id DESC LIMIT 100',
                [$id]
            );
        }

        $payments = [];
        if (self::tableExists('payments')) {
            $payments = Connection::select(
                'SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC LIMIT 100',
                [$id]
            );
        }

        $emailLogs = [];
        if (self::tableExists('notification_logs') && !empty($customer['email'])) {
            $emailLogs = Connection::select(
                "SELECT * FROM notification_logs WHERE channel_type = 'email' AND recipient = ? ORDER BY id DESC LIMIT 100",
                [$customer['email']]
            );
        }

        $unpaidTotal = 0.0;
        $unpaidCount = 0;
        foreach ($invoiceRows as $invoice) {
            $status = strtolower((string) ($invoice['status'] ?? ''));
            if (in_array($status, ['unpaid', 'pending', 'overdue'], true)) {
                $unpaidCount++;
                $unpaidTotal += (float) ($invoice['total'] ?? 0);
            }
        }

        return Response::html((new View())->render('admin::customers.show', [
            'title'    => 'Müşteri: ' . $customer['email'],
            'customer' => $customer,
            'orders'   => $ordersRows,
            'invoices' => $invoiceRows,
            'hosting'  => $hostingRows,
            'domains'  => $domains,
            'tickets'  => $ticketRows,
            'credits'  => $credits,
            'activity' => $activity,
            'notes'    => $notes,
            'contacts' => $contacts,
            'billableItems' => $billableItems,
            'quotes'   => $quotes,
            'payments' => $payments,
            'emailLogs' => $emailLogs,
            'summary'  => [
                'orders'       => count($ordersRows),
                'invoices'     => count($invoiceRows),
                'unpaid_count'  => $unpaidCount,
                'unpaid_total'  => $unpaidTotal,
                'hosting'       => count($hostingRows),
                'domains'       => count($domains),
                'tickets'       => count($ticketRows),
            ],
        ]));
    }

    public function revealHostingPassword(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $hostingId = (int) $request->param('hostingId');

        $service = Connection::selectOne(
            "SELECT id, customer_id, domain, username, password_encrypted FROM hosting_accounts WHERE id = ? AND customer_id = ? LIMIT 1",
            [$hostingId, $customerId]
        );
        if (!$service) {
            return Response::json(['ok' => false, 'error' => 'Hizmet bulunamadi'], 404);
        }
        if (empty($service['password_encrypted'])) {
            return Response::json(['ok' => false, 'error' => 'Bu hizmet icin kayitli sifre yok'], 422);
        }

        try {
            $plain = \App\Support\Encrypter::decrypt((string) $service['password_encrypted']);
        } catch (\Throwable) {
            return Response::json(['ok' => false, 'error' => 'Sifre cozulemedi'], 422);
        }

        try {
            \App\Services\Logger\ActivityLog::log(
                'admin.hosting.password_revealed',
                'hosting_account',
                (int) $service['id'],
                'Admin viewed hosting password: ' . (string) $service['domain'],
                ['admin_id' => SessionManager::get('admin_id'), 'customer_id' => $customerId]
            );
        } catch (\Throwable) {}

        return Response::json(['ok' => true, 'password' => $plain, 'username' => (string) ($service['username'] ?? '')]);
    }

    /** Yeni müşteri formu */
    public function create(Request $request): Response
    {
        return Response::html((new View())->render('admin::customers.form', [
            'title'    => 'Yeni Müşteri',
            'customer' => null,
        ]));
    }

    /** Yeni müşteri kaydet */
    public function store(Request $request): Response
    {
        $data = $this->extractData($request);
        $password = trim((string) $request->input('password', ''));
        if ($password === '') {
            SessionManager::flash('error', 'Şifre zorunlu.');
            return Response::redirect('/admin/musteriler/yeni');
        }
        if (Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$data['email']])) {
            SessionManager::flash('error', 'Bu e-posta zaten kayıtlı.');
            return Response::redirect('/admin/musteriler/yeni');
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $data['email_verified_at'] = $request->input('email_verified') ? date('Y-m-d H:i:s') : null;

        $id = Connection::insert('customers', $data);
        SessionManager::flash('success', "Müşteri #$id oluşturuldu.");
        return Response::redirect('/admin/musteriler/' . $id);
    }

    /** Düzenleme formu */
    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $customer = Connection::selectOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer) return Response::notFound();
        return Response::html((new View())->render('admin::customers.form', [
            'title'    => 'Düzenle: ' . $customer['email'],
            'customer' => $customer,
        ]));
    }

    /** Güncelle */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $customer = Connection::selectOne("SELECT * FROM customers WHERE id = ?", [$id]);
        if (!$customer) return Response::notFound();

        $data = $this->extractData($request);
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Şifre değiştirilecekse
        $password = trim((string) $request->input('password', ''));
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // E-posta doğrulama toggle
        if ($request->input('email_verified') && empty($customer['email_verified_at'])) {
            $data['email_verified_at'] = date('Y-m-d H:i:s');
        } elseif (!$request->input('email_verified') && !empty($customer['email_verified_at'])) {
            $data['email_verified_at'] = null;
        }

        Connection::update('customers', $data, 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('customer.updated', 'customer', $id, "{$data['email']} güncellendi");

        SessionManager::flash('success', 'Müşteri güncellendi.');
        return Response::redirect('/admin/musteriler/' . $id);
    }

    /** Askıya al / tekrar aç */
    public function suspend(Request $request): Response
    {
        $id = (int) $request->param('id');
        $customer = Connection::selectOne("SELECT id, email, status FROM customers WHERE id = ?", [$id]);
        if (!$customer) return Response::notFound();

        $newStatus = $customer['status'] === 'suspended' ? 'active' : 'suspended';
        Connection::update('customers', ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        \App\Services\Logger\ActivityLog::log("customer.$newStatus", 'customer', $id, "{$customer['email']}");
        SessionManager::flash('success', $newStatus === 'suspended' ? '⏸ Müşteri askıya alındı.' : '✓ Müşteri aktif edildi.');
        return Response::redirect('/admin/musteriler/' . $id);
    }

    /** Sil (soft — status=closed) */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $customer = Connection::selectOne("SELECT id, email FROM customers WHERE id = ?", [$id]);
        if (!$customer) return Response::notFound();

        Connection::update('customers', ['status' => 'closed', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('customer.closed', 'customer', $id, "{$customer['email']}");

        SessionManager::flash('success', '🗑 Müşteri kapatıldı.');
        return Response::redirect('/admin/musteriler');
    }

    private static function tableExists(string $table): bool
    {
        try {
            $stmt = Connection::pdo()->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractData(Request $request): array
    {
        return [
            'email'      => trim((string) $request->input('email', '')),
            'first_name' => trim((string) $request->input('first_name', '')) ?: null,
            'last_name'  => trim((string) $request->input('last_name', '')) ?: null,
            'phone'      => trim((string) $request->input('phone', '')) ?: null,
            'company'    => trim((string) $request->input('company', '')) ?: null,
            'tax_id'     => trim((string) $request->input('tax_id', '')) ?: null,
            'tax_office' => trim((string) $request->input('tax_office', '')) ?: null,
            'address'    => trim((string) $request->input('address', '')) ?: null,
            'city'       => trim((string) $request->input('city', '')) ?: null,
            'postcode'   => trim((string) $request->input('postcode', '')) ?: null,
            'country'    => strtoupper(trim((string) $request->input('country', 'TR'))) ?: 'TR',
            'status'     => in_array($request->input('status'), ['active','pending','suspended','closed'], true) ? $request->input('status') : 'active',
            'is_individual' => $request->input('is_individual') ? 1 : 0,
            'preferred_language' => in_array($request->input('preferred_language'), ['tr','en','de'], true) ? $request->input('preferred_language') : 'tr',
            'preferred_currency' => in_array($request->input('preferred_currency'), ['TRY','USD','EUR','GBP'], true) ? $request->input('preferred_currency') : 'TRY',
            'admin_notes'        => trim((string) $request->input('admin_notes', '')) ?: null,
        ];
    }

    /** Admin, müşterinin bakiyesine kredi ekler veya düşer. */
    public function addCredit(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $adminId = (int) SessionManager::get('admin_id');
        $direction = (string) $request->input('direction', 'add');
        $amount = abs((float) $request->input('amount', 0));
        $source = (string) $request->input('source', 'admin_manual');
        $description = trim((string) $request->input('description', ''));

        if ($amount <= 0) {
            SessionManager::flash('error', 'Geçersiz tutar.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }

        $signedAmount = $direction === 'deduct' ? -$amount : $amount;

        $result = \App\Services\Credit\CreditService::record($customerId, $signedAmount, $source, [
            'admin_id'    => $adminId,
            'description' => $description ?: null,
        ]);

        if ($result['ok']) {
            SessionManager::flash('success',
                "✓ " . ($direction === 'add' ? 'Bakiye eklendi' : 'Bakiye düşüldü') .
                ". Yeni bakiye: " . number_format($result['balance'], 2) . " TRY"
            );
        } else {
            SessionManager::flash('error', $result['error'] ?? 'Hata');
        }
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    /** Adına giriş yap → müşteri paneline yönlendir. */
    public function impersonate(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $adminId = (int) SessionManager::get('admin_id');
        if ($adminId <= 0) {
            return Response::redirect('/admin/giris');
        }
        $reason = (string) $request->input('reason', '');
        $result = ImpersonationService::start($adminId, $customerId, $reason ?: null);

        if (empty($result['ok'])) {
            SessionManager::flash('error', $result['error'] ?? 'Başlatılamadı.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }

        SessionManager::flash('success', 'Müşteri paneline giriş yaptın. Sağ üstten çıkabilirsin.');
        return Response::redirect('/panel');
    }

    // ================= Notlar =================

    public function storeNote(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            SessionManager::flash('error', 'Not metni boş olamaz.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }
        Connection::insert('customer_notes', [
            'customer_id' => $customerId,
            'admin_email' => (string) (SessionManager::get('admin_email') ?? ''),
            'note'        => $note,
            'is_sticky'   => $request->input('is_sticky') ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        SessionManager::flash('success', 'Not eklendi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    public function deleteNote(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $noteId = (int) $request->param('noteId');
        Connection::query('DELETE FROM customer_notes WHERE id = ? AND customer_id = ?', [$noteId, $customerId]);
        SessionManager::flash('success', 'Not silindi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    // ================= Kullanıcılar (alt kişiler) =================

    public function storeContact(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $firstName = trim((string) $request->input('first_name', ''));
        $email = trim((string) $request->input('email', ''));
        if ($firstName === '' || $email === '') {
            SessionManager::flash('error', 'Ad ve e-posta zorunludur.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }
        $password = trim((string) $request->input('password', ''));
        Connection::insert('customer_contacts', [
            'customer_id'   => $customerId,
            'first_name'    => $firstName,
            'last_name'     => trim((string) $request->input('last_name', '')) ?: null,
            'email'         => $email,
            'phone'         => trim((string) $request->input('phone', '')) ?: null,
            'role_label'    => trim((string) $request->input('role_label', '')) ?: null,
            'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
            'is_active'     => $request->input('is_active') ? 1 : 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        SessionManager::flash('success', 'Kullanıcı eklendi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    public function updateContact(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $contactId = (int) $request->param('contactId');
        $contact = Connection::selectOne('SELECT id FROM customer_contacts WHERE id = ? AND customer_id = ?', [$contactId, $customerId]);
        if (!$contact) return Response::notFound();

        $data = [
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name'  => trim((string) $request->input('last_name', '')) ?: null,
            'email'      => trim((string) $request->input('email', '')),
            'phone'      => trim((string) $request->input('phone', '')) ?: null,
            'role_label' => trim((string) $request->input('role_label', '')) ?: null,
            'is_active'  => $request->input('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $password = trim((string) $request->input('password', ''));
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        Connection::update('customer_contacts', $data, 'id = ?', [$contactId]);
        SessionManager::flash('success', 'Kullanıcı güncellendi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    public function deleteContact(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $contactId = (int) $request->param('contactId');
        Connection::query('DELETE FROM customer_contacts WHERE id = ? AND customer_id = ?', [$contactId, $customerId]);
        SessionManager::flash('success', 'Kullanıcı silindi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    // ================= Faturalandırılabilir Ürünler =================

    public function storeBillableItem(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $description = trim((string) $request->input('description', ''));
        $unitPrice = (float) $request->input('unit_price', 0);
        if ($description === '' || $unitPrice <= 0) {
            SessionManager::flash('error', 'Açıklama ve tutar zorunludur.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }
        Connection::insert('billable_items', [
            'customer_id' => $customerId,
            'description' => $description,
            'quantity'    => max(1, (int) $request->input('quantity', 1)),
            'unit_price'  => $unitPrice,
            'tax_rate'    => (float) $request->input('tax_rate', 0),
            'currency'    => strtoupper((string) $request->input('currency', 'TRY')) ?: 'TRY',
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        SessionManager::flash('success', 'Kalem eklendi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    public function deleteBillableItem(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $itemId = (int) $request->param('itemId');
        Connection::query("DELETE FROM billable_items WHERE id = ? AND customer_id = ? AND status = 'pending'", [$itemId, $customerId]);
        SessionManager::flash('success', 'Kalem silindi.');
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    /** Seçilen faturalandırılabilir kalemleri tek bir faturaya çevirir. */
    public function convertBillableItems(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $ids = array_map('intval', (array) $request->input('item_ids', []));
        $ids = array_values(array_filter($ids));
        if (!$ids) {
            SessionManager::flash('error', 'Faturaya eklemek için en az bir kalem seç.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $items = Connection::select(
            "SELECT * FROM billable_items WHERE customer_id = ? AND status = 'pending' AND id IN ($placeholders)",
            array_merge([$customerId], $ids)
        );
        if (!$items) {
            SessionManager::flash('error', 'Seçilen kalemler bulunamadı ya da zaten faturalanmış.');
            return Response::redirect('/admin/musteriler/' . $customerId);
        }

        $currency = (string) ($items[0]['currency'] ?? 'TRY');
        $invoiceItems = array_map(fn($it) => [
            'description' => $it['description'],
            'quantity'    => (int) $it['quantity'],
            'unit_price'  => (float) $it['unit_price'],
            'tax_rate'    => (float) $it['tax_rate'],
        ], $items);

        $invoiceId = \App\Modules\Invoice\Services\InvoiceService::createManual($customerId, $invoiceItems, $currency);

        foreach ($items as $it) {
            Connection::update('billable_items', [
                'status'     => 'invoiced',
                'invoice_id' => $invoiceId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$it['id']]);
        }

        \App\Services\Logger\ActivityLog::log('customer.billable_items.invoiced', 'customer', $customerId, count($items) . ' kalem faturaya çevrildi (#' . $invoiceId . ')');
        SessionManager::flash('success', "✓ Fatura #$invoiceId oluşturuldu.");
        return Response::redirect('/admin/musteriler/' . $customerId);
    }

    // ================= Hosting Hızlı İşlemler =================

    private function hostingContext(int $customerId, int $hostingId): ?array
    {
        return Connection::selectOne(
            "SELECT ha.*, s.panel AS server_panel FROM hosting_accounts ha
             LEFT JOIN hosting_servers s ON s.id = ha.server_id
             WHERE ha.id = ? AND ha.customer_id = ? LIMIT 1",
            [$hostingId, $customerId]
        );
    }

    public function hostingSuspend(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $hostingId = (int) $request->param('hostingId');
        $ha = $this->hostingContext($customerId, $hostingId);
        if (!$ha) return Response::notFound();

        $ok = true;
        if (!empty($ha['server_id']) && !empty($ha['username'])) {
            try {
                $ok = \App\Modules\Hosting\HostingManager::forServer((int) $ha['server_id'])
                    ->suspendAccount((string) $ha['username'], 'Admin tarafından askıya alındı');
            } catch (\Throwable) { $ok = false; }
        }
        if ($ok) {
            Connection::update('hosting_accounts', ['status' => 'suspended', 'suspended_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$hostingId]);
            \App\Services\Logger\ActivityLog::log('admin.hosting.suspended', 'hosting_account', $hostingId, 'Hesap askıya alındı.');
            SessionManager::flash('success', 'Hesap askıya alındı.');
        } else {
            SessionManager::flash('error', 'Sunucuda askıya alma işlemi başarısız oldu, panel bağlantısını kontrol et.');
        }
        return Response::redirect('/admin/musteriler/' . $customerId . '#hosting');
    }

    public function hostingUnsuspend(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $hostingId = (int) $request->param('hostingId');
        $ha = $this->hostingContext($customerId, $hostingId);
        if (!$ha) return Response::notFound();

        $ok = true;
        if (!empty($ha['server_id']) && !empty($ha['username'])) {
            try {
                $ok = \App\Modules\Hosting\HostingManager::forServer((int) $ha['server_id'])
                    ->unsuspendAccount((string) $ha['username']);
            } catch (\Throwable) { $ok = false; }
        }
        if ($ok) {
            Connection::update('hosting_accounts', ['status' => 'active', 'suspended_at' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$hostingId]);
            \App\Services\Logger\ActivityLog::log('admin.hosting.unsuspended', 'hosting_account', $hostingId, 'Hesap aktif edildi.');
            SessionManager::flash('success', 'Hesap tekrar aktif edildi.');
        } else {
            SessionManager::flash('error', 'Sunucuda aktifleştirme işlemi başarısız oldu, panel bağlantısını kontrol et.');
        }
        return Response::redirect('/admin/musteriler/' . $customerId . '#hosting');
    }

    public function hostingResetPassword(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $hostingId = (int) $request->param('hostingId');
        $ha = $this->hostingContext($customerId, $hostingId);
        if (!$ha) return Response::notFound();
        if (empty($ha['server_id']) || empty($ha['username'])) {
            SessionManager::flash('error', 'Bu hesabın sunucu/kullanıcı bilgisi eksik, önce paket/sunucu ata.');
            return Response::redirect('/admin/musteriler/' . $customerId . '#hosting');
        }

        $newPassword = self::randomPassword();
        try {
            $ok = \App\Modules\Hosting\HostingManager::forServer((int) $ha['server_id'])
                ->changePassword((string) $ha['username'], $newPassword);
        } catch (\Throwable) { $ok = false; }

        if ($ok) {
            Connection::update('hosting_accounts', [
                'password_encrypted' => \App\Support\Encrypter::encrypt($newPassword),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$hostingId]);
            \App\Services\Logger\ActivityLog::log('admin.hosting.password_reset', 'hosting_account', $hostingId, 'Panel şifresi admin tarafından sıfırlandı.');
            SessionManager::flash('success', 'Yeni şifre: ' . $newPassword . ' (bir daha gösterilmeyecek, not al)');
        } else {
            SessionManager::flash('error', 'Sunucuda şifre değiştirme başarısız oldu, panel bağlantısını kontrol et.');
        }
        return Response::redirect('/admin/musteriler/' . $customerId . '#hosting');
    }

    private static function randomPassword(): string
    {
        return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%'), 0, 14);
    }

    /** Hosting hesaplarındaki domain adlarını "Alan Adları" (domains) tablosuna aktarır (eksik olanları). */
    public function syncDomainsFromHosting(Request $request): Response
    {
        $customerId = (int) $request->param('id');
        $hostingDomains = Connection::select(
            "SELECT DISTINCT domain FROM hosting_accounts WHERE customer_id = ? AND domain IS NOT NULL AND domain != ''",
            [$customerId]
        );
        $existing = array_column(
            Connection::select('SELECT domain_name FROM domains WHERE customer_id = ?', [$customerId]),
            'domain_name'
        );

        $added = 0;
        foreach ($hostingDomains as $row) {
            $name = trim((string) $row['domain']);
            if ($name === '' || in_array($name, $existing, true)) continue;
            // Aynı domain başka bir müşteride kayıtlıysa (unique kısıtı) atla.
            $clash = Connection::selectOne('SELECT id FROM domains WHERE domain_name = ?', [$name]);
            if ($clash) continue;

            Connection::insert('domains', [
                'customer_id'       => $customerId,
                'domain_name'       => $name,
                'registrar_id'      => null,
                'registration_date' => null,
                'expiry_date'       => null,
                'next_due_date'     => null,
                'status'            => 'active',
                'auto_renew'        => 1,
                'transfer_lock'     => 1,
                'whois_privacy'     => 0,
                'nameservers'       => json_encode([]),
                'period_years'      => 1,
                'recurring_amount'  => 0,
                'currency'          => 'TRY',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            $added++;
        }

        \App\Services\Logger\ActivityLog::log('customer.domains.synced', 'customer', $customerId, "$added domain hosting hesaplarından Alan Adları'na aktarıldı.");
        SessionManager::flash('success', $added > 0 ? "✓ $added domain eklendi." : 'Eklenecek yeni domain bulunamadı (ya hiç domain yok ya da hepsi zaten kayıtlı).');
        return Response::redirect('/admin/musteriler/' . $customerId . '#domain');
    }
}
