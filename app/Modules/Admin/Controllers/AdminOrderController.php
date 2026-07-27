<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

final class AdminOrderController
{
    public function index(Request $request): Response
    {
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $sql = "SELECT o.*, c.email AS customer_email,
                       TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) AS customer_name
                FROM orders o
                LEFT JOIN customers c ON c.id = o.customer_id
                WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (o.order_number LIKE ? OR c.email LIKE ?)";
            $params[] = "%$q%"; $params[] = "%$q%";
        }
        if ($status !== '' && in_array($status, ['pending','paid','processing','active','failed','cancelled','refunded'], true)) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY o.id DESC LIMIT 200";
        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);

        // Özet
        $summary = Connection::selectOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending,
                    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) paid,
                    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) active,
                    COALESCE(SUM(CASE WHEN status IN ('paid','active','processing') THEN total ELSE 0 END),0) revenue
             FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        return Response::html((new View())->render('admin::orders.index', [
            'title'   => 'Siparişler',
            'orders'  => $st->fetchAll(),
            'q'       => $q,
            'status'  => $status,
            'summary' => $summary,
        ]));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $order = Connection::selectOne(
            "SELECT o.*, c.email AS customer_email, c.first_name, c.last_name, c.phone, c.company
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ?",
            [$id]
        );
        if (!$order) return Response::notFound();

        $items = Connection::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
        $invoices = Connection::select("SELECT * FROM invoices WHERE order_id = ? ORDER BY id DESC", [$id]);

        return Response::html((new View())->render('admin::orders.show', [
            'title'    => 'Sipariş #' . $order['order_number'],
            'order'    => $order,
            'items'    => $items,
            'invoices' => $invoices,
        ]));
    }

    /** Sipariş durumu değiştir (onay/iptal/aktif) */
    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->param('id');
        $status = (string) $request->input('status', '');
        $allowed = ['pending','paid','processing','active','failed','cancelled','refunded'];
        if (!in_array($status, $allowed, true)) {
            SessionManager::flash('error', 'Geçersiz durum');
            return Response::redirect('/admin/siparisler/' . $id);
        }
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$id]);
        if (!$order) return Response::notFound();

        $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'paid' && empty($order['paid_at'])) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'active' && empty($order['activated_at'])) {
            $update['activated_at'] = date('Y-m-d H:i:s');
        }
        Connection::update('orders', $update, 'id = ?', [$id]);

        \App\Services\Logger\ActivityLog::log("order.$status", 'order', $id, "Sipariş #{$order['order_number']} → $status");
        SessionManager::flash('success', "Sipariş durumu → $status");
        return Response::redirect('/admin/siparisler/' . $id);
    }
}
