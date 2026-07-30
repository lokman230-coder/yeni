<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;

final class AdminPaymentController
{
    public function index(Request $request): Response
    {
        $q      = trim((string) $request->query('q', ''));
        $method = (string) $request->query('method', '');
        $status = (string) $request->query('status', '');

        $sql = "SELECT p.*, i.invoice_number, c.email AS customer_email,
                       TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) AS customer_name
                FROM payments p
                LEFT JOIN invoices i ON i.id = p.invoice_id
                LEFT JOIN customers c ON c.id = p.customer_id
                WHERE 1=1";
        $params = [];
        if ($q !== '') {
            $sql .= " AND (p.gateway_transaction_id LIKE ? OR c.email LIKE ? OR i.invoice_number LIKE ?)";
            $like = "%$q%"; $params = [$like, $like, $like];
        }
        if ($method !== '') { $sql .= " AND p.method = ?"; $params[] = $method; }
        if ($status !== '') { $sql .= " AND p.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY p.id DESC LIMIT 200";
        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);

        $summary = Connection::selectOne(
            "SELECT
                COUNT(*) total,
                COALESCE(SUM(CASE WHEN status='success' THEN amount ELSE 0 END),0) total_ok,
                SUM(CASE WHEN method='bank_transfer' THEN 1 ELSE 0 END) bank_transfers,
                SUM(CASE WHEN method='paytr' THEN 1 ELSE 0 END) paytr
             FROM payments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        return Response::html((new View())->render('admin::payments.index', [
            'title'    => 'Ödemeler',
            'payments' => $st->fetchAll(),
            'q' => $q, 'method' => $method, 'status' => $status,
            'summary'  => $summary,
        ]));
    }
}
