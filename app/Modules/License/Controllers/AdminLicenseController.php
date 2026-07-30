<?php

declare(strict_types=1);

namespace App\Modules\License\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\License\LicenseService;

final class AdminLicenseController
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $sql = "SELECT l.*, c.email AS customer_email,
                       (SELECT COUNT(*) FROM license_activations a WHERE a.license_id = l.id AND a.is_active = 1) AS active_count
                FROM licenses l
                LEFT JOIN customers c ON c.id = l.customer_id
                WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (l.license_key LIKE ? OR c.email LIKE ? OR l.product_name LIKE ?)";
            $params = ["%$q%", "%$q%", "%$q%"];
        }
        if ($status !== '') { $sql .= " AND l.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY l.id DESC LIMIT 200";
        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);

        return Response::html((new View())->render('license::admin.index', [
            'title'    => 'Lisanslar',
            'licenses' => $st->fetchAll(),
            'q' => $q, 'status' => $status,
        ]));
    }

    public function create(Request $request): Response
    {
        $customers = Connection::select("SELECT id, email FROM customers WHERE status='active' ORDER BY id DESC LIMIT 500");
        $products = Connection::select("SELECT id, name FROM products WHERE status='active' ORDER BY name LIMIT 500");
        return Response::html((new View())->render('license::admin.form', [
            'title'    => 'Yeni Lisans',
            'license'  => null,
            'customers' => $customers,
            'products' => $products,
        ]));
    }

    public function store(Request $request): Response
    {
        $data = [
            'customer_id'   => (int) $request->input('customer_id'),
            'product_id'    => (int) $request->input('product_id', 0) ?: null,
            'product_name'  => trim((string) $request->input('product_name', 'Ahost Script')),
            'license_type'  => (string) $request->input('license_type', 'single_domain'),
            'max_domains'   => max(1, (int) $request->input('max_domains', 1)),
            'expires_at'    => $request->input('expires_at') ?: null,
            'purchase_code' => trim((string) $request->input('purchase_code', '')) ?: null,
            'source'        => (string) $request->input('source', 'ahost'),
            'notes'         => trim((string) $request->input('notes', '')) ?: null,
        ];

        if ($data['customer_id'] <= 0) {
            SessionManager::flash('error', 'Müşteri seçiniz');
            return Response::redirect('/admin/lisanslar/yeni');
        }

        $license = LicenseService::issue($data);
        SessionManager::flash('success', "✓ Lisans oluşturuldu: {$license['license_key']}");
        return Response::redirect('/admin/lisanslar/' . $license['id']);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $license = Connection::selectOne("SELECT l.*, c.email AS customer_email FROM licenses l LEFT JOIN customers c ON c.id = l.customer_id WHERE l.id = ?", [$id]);
        if (!$license) return Response::notFound();

        $activations = Connection::select("SELECT * FROM license_activations WHERE license_id = ? ORDER BY id DESC", [$id]);
        $verifications = Connection::select("SELECT * FROM license_verifications WHERE license_id = ? ORDER BY id DESC LIMIT 50", [$id]);

        return Response::html((new View())->render('license::admin.show', [
            'title'         => 'Lisans: ' . $license['license_key'],
            'license'       => $license,
            'activations'   => $activations,
            'verifications' => $verifications,
        ]));
    }

    public function revoke(Request $request): Response
    {
        $id = (int) $request->param('id');
        LicenseService::revoke($id, (string) $request->input('reason', 'Admin revoke'));
        SessionManager::flash('success', 'Lisans iptal edildi.');
        return Response::redirect('/admin/lisanslar/' . $id);
    }

    public function deactivateActivation(Request $request): Response
    {
        $id = (int) $request->param('id');
        $activationId = (int) $request->input('activation_id');
        Connection::update('license_activations', ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$activationId]);
        SessionManager::flash('success', 'Aktivasyon deaktive edildi.');
        return Response::redirect('/admin/lisanslar/' . $id);
    }
}
