<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

/**
 * Import edilen / paket-sunucu ataması eksik hosting hesaplarını topluca
 * bir ürüne ve sunucuya bağlamak için.
 */
final class AdminHostingAccountController
{
    public function index(Request $request): Response
    {
        $onlyUnassigned = (bool) $request->query('unassigned', '1');
        $q = trim((string) $request->query('q', ''));

        $sql = "SELECT ha.*, p.name AS product_name, s.name AS server_name, c.email AS customer_email,
                       c.first_name AS customer_first_name, c.last_name AS customer_last_name
                FROM hosting_accounts ha
                LEFT JOIN products p ON p.id = ha.product_id
                LEFT JOIN hosting_servers s ON s.id = ha.server_id
                LEFT JOIN customers c ON c.id = ha.customer_id
                WHERE 1=1";
        $params = [];

        if ($onlyUnassigned) {
            $sql .= " AND (ha.product_id IS NULL OR ha.server_id IS NULL)";
        }
        if ($q !== '') {
            $sql .= " AND (ha.domain LIKE ? OR c.email LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY ha.id DESC LIMIT 300";

        $accounts = Connection::select($sql, $params);
        $products = Connection::select("SELECT id, name, type FROM products ORDER BY name ASC");
        $servers = Connection::select("SELECT id, name, hostname, is_active FROM hosting_servers ORDER BY is_active DESC, name ASC");

        return Response::html((new View())->render('admin::hosting_accounts.index', [
            'title'          => 'Hosting Hesapları',
            'accounts'       => $accounts,
            'products'       => $products,
            'servers'        => $servers,
            'onlyUnassigned' => $onlyUnassigned,
            'q'              => $q,
        ]));
    }

    /** Seçilen hesaplara toplu ürün + sunucu ata. */
    public function bulkAssign(Request $request): Response
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('account_ids', []))));
        $productId = (int) $request->input('product_id', 0) ?: null;
        $serverId = (int) $request->input('server_id', 0) ?: null;

        if (!$ids) {
            SessionManager::flash('error', 'En az bir hesap seçmelisin.');
            return Response::redirect('/admin/hosting-hesaplari');
        }
        if (!$productId && !$serverId) {
            SessionManager::flash('error', 'Ürün veya sunucudan en az birini seçmelisin.');
            return Response::redirect('/admin/hosting-hesaplari');
        }

        $data = ['updated_at' => date('Y-m-d H:i:s')];
        if ($productId) $data['product_id'] = $productId;
        if ($serverId) $data['server_id'] = $serverId;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        Connection::query(
            "UPDATE hosting_accounts SET $set WHERE id IN ($placeholders)",
            array_merge(array_values($data), $ids)
        );

        \App\Services\Logger\ActivityLog::log('hosting.bulk_assign', 'hosting_account', 0, count($ids) . ' hesaba toplu paket/sunucu atandı.');
        SessionManager::flash('success', "✓ " . count($ids) . " hesap güncellendi.");
        return Response::redirect('/admin/hosting-hesaplari');
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $account = Connection::selectOne(
            "SELECT ha.*, c.email AS customer_email FROM hosting_accounts ha
             LEFT JOIN customers c ON c.id = ha.customer_id
             WHERE ha.id = ?", [$id]
        );
        if (!$account) return Response::notFound();

        $products = Connection::select("SELECT id, name, type FROM products ORDER BY name ASC");
        $servers = Connection::select("SELECT id, name, hostname, is_active FROM hosting_servers ORDER BY is_active DESC, name ASC");

        return Response::html((new View())->render('admin::hosting_accounts.edit', [
            'title'    => 'Hosting Hesabı #' . $account['id'],
            'account'  => $account,
            'products' => $products,
            'servers'  => $servers,
        ]));
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $account = Connection::selectOne('SELECT id, customer_id FROM hosting_accounts WHERE id = ?', [$id]);
        if (!$account) return Response::notFound();

        Connection::update('hosting_accounts', [
            'domain'        => trim((string) $request->input('domain', '')),
            'username'      => trim((string) $request->input('username', '')) ?: null,
            'product_id'    => (int) $request->input('product_id', 0) ?: null,
            'server_id'     => (int) $request->input('server_id', 0) ?: null,
            'status'        => (string) $request->input('status', 'active'),
            'next_due_date' => trim((string) $request->input('next_due_date', '')) ?: null,
            'notes'         => trim((string) $request->input('notes', '')) ?: null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        \App\Services\Logger\ActivityLog::log('admin.hosting.updated', 'hosting_account', $id, 'Hosting hesabı detayları güncellendi.');
        SessionManager::flash('success', 'Hesap güncellendi.');
        return Response::redirect('/admin/musteriler/' . $account['customer_id'] . '#hosting');
    }
}
