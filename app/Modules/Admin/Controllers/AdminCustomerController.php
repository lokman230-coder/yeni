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
        $orders = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 20');
        $orders->execute([$id]);

        $invoices = $pdo->prepare('SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC LIMIT 20');
        $invoices->execute([$id]);

        $tickets = $pdo->prepare('SELECT * FROM tickets WHERE customer_id = ? ORDER BY id DESC LIMIT 20');
        $tickets->execute([$id]);

        return Response::html((new View())->render('admin::customers.show', [
            'title'    => 'Müşteri: ' . $customer['email'],
            'customer' => $customer,
            'orders'   => $orders->fetchAll(),
            'invoices' => $invoices->fetchAll(),
            'tickets'  => $tickets->fetchAll(),
        ]));
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
}
