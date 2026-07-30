<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Marketplace\VendorService;

final class AdminVendorController
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $sql = "SELECT v.*, c.email AS customer_email FROM vendors v LEFT JOIN customers c ON c.id = v.customer_id";
        $params = [];
        if ($status !== '') { $sql .= " WHERE v.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY v.id DESC";
        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);

        return Response::html((new View())->render('admin::vendors.index', [
            'title'   => 'Vendorlar (Marketplace Satıcıları)',
            'vendors' => $st->fetchAll(),
            'status'  => $status,
        ]));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $vendor = Connection::selectOne("SELECT v.*, c.email AS customer_email FROM vendors v LEFT JOIN customers c ON c.id = v.customer_id WHERE v.id = ?", [$id]);
        if (!$vendor) return Response::notFound();

        $listings = Connection::select("SELECT * FROM marketplace_listings WHERE vendor_id = ? ORDER BY id DESC LIMIT 50", [$id]);
        $earnings = Connection::select("SELECT * FROM vendor_earnings WHERE vendor_id = ? ORDER BY id DESC LIMIT 50", [$id]);
        $payouts  = Connection::select("SELECT * FROM vendor_payouts WHERE vendor_id = ? ORDER BY id DESC", [$id]);
        $available = VendorService::availableBalance($id);

        return Response::html((new View())->render('admin::vendors.show', [
            'title'    => $vendor['shop_name'],
            'vendor'   => $vendor,
            'listings' => $listings,
            'earnings' => $earnings,
            'payouts'  => $payouts,
            'availableBalance' => $available,
        ]));
    }

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        $adminId = (int) SessionManager::get('admin_id');
        VendorService::approve($id, $adminId);
        SessionManager::flash('success', '✓ Vendor onaylandı.');
        return Response::redirect('/admin/vendorlar/' . $id);
    }

    public function suspend(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::update('vendors', ['status' => 'suspended', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        SessionManager::flash('success', 'Vendor askıya alındı.');
        return Response::redirect('/admin/vendorlar/' . $id);
    }
}
