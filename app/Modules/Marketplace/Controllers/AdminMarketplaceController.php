<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

/**
 * Admin > Marketplace onay & yönetim.
 * URL: /admin/marketplace
 */
final class AdminMarketplaceController
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'pending');
        $where = '';
        $params = [];
        if (in_array($status, ['pending','active','rejected','sold','draft'], true)) {
            $where = "WHERE l.status = ?";
            $params[] = $status;
        }
        $listings = Connection::select(
            "SELECT l.*, c.name AS category_name, u.email AS seller_email
             FROM marketplace_listings l
             LEFT JOIN marketplace_categories c ON c.id = l.category_id
             LEFT JOIN customers u ON u.id = l.seller_id
             $where ORDER BY l.created_at DESC LIMIT 100", $params
        );
        $metrics = [
            'pending'  => (int)(Connection::selectOne("SELECT COUNT(*) c FROM marketplace_listings WHERE status='pending'")['c'] ?? 0),
            'active'   => (int)(Connection::selectOne("SELECT COUNT(*) c FROM marketplace_listings WHERE status='active'")['c'] ?? 0),
            'sold'     => (int)(Connection::selectOne("SELECT COUNT(*) c FROM marketplace_listings WHERE status='sold'")['c'] ?? 0),
            'rejected' => (int)(Connection::selectOne("SELECT COUNT(*) c FROM marketplace_listings WHERE status='rejected'")['c'] ?? 0),
        ];
        $view = new View();
        return Response::html($view->render('marketplace::admin.index', [
            'title'    => 'Marketplace Yönetimi',
            'listings' => $listings,
            'metrics'  => $metrics,
            'status'   => $status,
            'success'  => flash('success'),
            'error'    => flash('error'),
        ]));
    }

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::update('marketplace_listings', ['status' => 'active'], 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('approved', 'marketplace_listing', $id, "İlan #$id onaylandı");
        SessionManager::flash('success', "İlan #$id onaylandı ve yayınlandı.");
        return Response::redirect('/admin/marketplace');
    }

    public function reject(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::update('marketplace_listings', ['status' => 'rejected'], 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('rejected', 'marketplace_listing', $id, "İlan #$id reddedildi");
        SessionManager::flash('success', "İlan #$id reddedildi.");
        return Response::redirect('/admin/marketplace');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::delete('marketplace_listings', 'id = ?', [$id]);
        \App\Services\Logger\ActivityLog::log('deleted', 'marketplace_listing', $id, "İlan #$id silindi");
        SessionManager::flash('success', "İlan #$id silindi.");
        return Response::redirect('/admin/marketplace');
    }
}
