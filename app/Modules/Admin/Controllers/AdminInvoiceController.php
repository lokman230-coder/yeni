<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

final class AdminInvoiceController
{
    public function index(Request $request): Response
    {
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $sql = "SELECT i.*, c.email AS customer_email,
                       TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,''))) AS customer_name
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                WHERE 1=1";
        $params = [];
        if ($q !== '') { $sql .= " AND (i.invoice_number LIKE ? OR c.email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($status !== '') { $sql .= " AND i.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY i.id DESC LIMIT 200";
        $st = Connection::pdo()->prepare($sql);
        $st->execute($params);

        $summary = Connection::selectOne(
            "SELECT
                COUNT(*) total,
                SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) unpaid,
                SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) overdue,
                SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) paid,
                COALESCE(SUM(CASE WHEN status='paid' THEN total ELSE 0 END),0) revenue,
                COALESCE(SUM(CASE WHEN status IN ('unpaid','overdue','partially_paid') THEN balance ELSE 0 END),0) outstanding
             FROM invoices WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        return Response::html((new View())->render('admin::invoices.index', [
            'title'    => 'Faturalar',
            'invoices' => $st->fetchAll(),
            'q' => $q, 'status' => $status,
            'summary'  => $summary,
        ]));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $invoice = Connection::selectOne(
            "SELECT i.*, c.email AS customer_email, c.first_name, c.last_name, c.phone, c.company, c.address, c.city, c.tax_id
             FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.id = ?",
            [$id]
        );
        if (!$invoice) return Response::notFound();

        // Order ilişkisi
        $order = $invoice['order_id']
            ? Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$invoice['order_id']])
            : null;
        $items = $order
            ? Connection::select("SELECT * FROM order_items WHERE order_id = ?", [$order['id']])
            : [];

        $payments = Connection::select(
            "SELECT * FROM payments WHERE invoice_id = ? ORDER BY id DESC",
            [$id]
        );

        return Response::html((new View())->render('admin::invoices.show', [
            'title'    => 'Fatura #' . $invoice['invoice_number'],
            'invoice'  => $invoice,
            'order'    => $order,
            'items'    => $items,
            'payments' => $payments,
        ]));
    }

    /** Manuel ödeme kaydet (havale onayı veya manuel ekleme) */
    public function recordPayment(Request $request): Response
    {
        $id = (int) $request->param('id');
        $invoice = Connection::selectOne("SELECT * FROM invoices WHERE id = ?", [$id]);
        if (!$invoice) return Response::notFound();

        $amount = (float) $request->input('amount', 0);
        $method = (string) $request->input('method', 'bank_transfer');
        $txId   = trim((string) $request->input('gateway_transaction_id', ''));
        $notes  = trim((string) $request->input('notes', ''));

        if ($amount <= 0) {
            SessionManager::flash('error', 'Tutar sıfırdan büyük olmalı');
            return Response::redirect('/admin/faturalar/' . $id);
        }

        try {
            Connection::pdo()->beginTransaction();

            // Payment kaydet
            Connection::insert('payments', [
                'invoice_id'              => $id,
                'order_id'                => $invoice['order_id'],
                'customer_id'             => $invoice['customer_id'],
                'method'                  => $method,
                'amount'                  => $amount,
                'currency'                => $invoice['currency'],
                'gateway_transaction_id'  => $txId ?: null,
                'status'                  => 'success',
                'processed_at'            => date('Y-m-d H:i:s'),
                'notes'                   => $notes ?: null,
                'created_at'              => date('Y-m-d H:i:s'),
                'updated_at'              => date('Y-m-d H:i:s'),
            ]);

            // Fatura bakiyesini güncelle
            $newPaid = (float)$invoice['paid_total'] + $amount;
            $newBalance = (float)$invoice['total'] - $newPaid;
            $newStatus = $newBalance <= 0.01 ? 'paid' : ($newPaid > 0 ? 'partially_paid' : $invoice['status']);

            $update = [
                'paid_total' => $newPaid,
                'balance'    => max(0, $newBalance),
                'status'     => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($newStatus === 'paid' && empty($invoice['paid_at'])) {
                $update['paid_at'] = date('Y-m-d H:i:s');
            }
            Connection::update('invoices', $update, 'id = ?', [$id]);

            // Sipariş varsa onu da paid yap
            if ($invoice['order_id'] && $newStatus === 'paid') {
                Connection::update('orders', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = ? AND status = ?', [$invoice['order_id'], 'pending']);
            }

            Connection::pdo()->commit();
        } catch (\Throwable $e) {
            Connection::pdo()->rollBack();
            SessionManager::flash('error', 'Kaydetme başarısız: ' . $e->getMessage());
            return Response::redirect('/admin/faturalar/' . $id);
        }

        \App\Services\Logger\ActivityLog::log('invoice.payment_recorded', 'invoice', $id,
            "Fatura #{$invoice['invoice_number']} — $amount {$invoice['currency']} $method",
            ['method' => $method, 'tx' => $txId]
        );

        SessionManager::flash('success', "✓ Ödeme kaydedildi. Yeni durum: $newStatus");
        return Response::redirect('/admin/faturalar/' . $id);
    }

    /** Fatura iptal */
    public function cancel(Request $request): Response
    {
        $id = (int) $request->param('id');
        $inv = Connection::selectOne("SELECT * FROM invoices WHERE id = ?", [$id]);
        if (!$inv) return Response::notFound();

        Connection::update('invoices', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('invoice.cancelled', 'invoice', $id, "İptal: #{$inv['invoice_number']}");

        SessionManager::flash('success', 'Fatura iptal edildi.');
        return Response::redirect('/admin/faturalar/' . $id);
    }
}
