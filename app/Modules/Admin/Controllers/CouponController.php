<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

/**
 * Admin — Kupon Yönetimi CRUD.
 *
 * /admin/kuponlar          → liste
 * /admin/kuponlar/yeni     → form
 * /admin/kuponlar/kaydet   → POST
 * /admin/kuponlar/{id}     → düzenle
 * /admin/kuponlar/{id}/kaydet
 * /admin/kuponlar/{id}/sil
 */
final class CouponController
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $where = "WHERE 1=1"; $params = [];
        if ($q !== '') { $where .= " AND (code LIKE ? OR name LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
        if ($status === 'active') $where .= " AND is_active = 1";
        if ($status === 'inactive') $where .= " AND is_active = 0";

        $coupons = Connection::select("SELECT * FROM coupons $where ORDER BY id DESC LIMIT 200", $params);

        $metrics = [
            'total'   => (int) (Connection::selectOne("SELECT COUNT(*) c FROM coupons")['c'] ?? 0),
            'active'  => (int) (Connection::selectOne("SELECT COUNT(*) c FROM coupons WHERE is_active=1")['c'] ?? 0),
            'uses'    => (int) (Connection::selectOne("SELECT COALESCE(SUM(usage_count),0) c FROM coupons")['c'] ?? 0),
            'expired' => (int) (Connection::selectOne("SELECT COUNT(*) c FROM coupons WHERE ends_at IS NOT NULL AND ends_at < NOW()")['c'] ?? 0),
        ];

        $view = new View();
        return Response::html($view->render('admin::coupons.index', [
            'title'   => 'Kuponlar',
            'coupons' => $coupons,
            'metrics' => $metrics,
            'q'       => $q,
            'status'  => $status,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function createForm(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('admin::coupons.form', [
            'title'  => 'Yeni Kupon',
            'coupon' => null,
            'error'  => flash('error'),
        ]));
    }

    public function editForm(Request $request): Response
    {
        $id = (int) $request->param('id');
        $coupon = Connection::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if (!$coupon) return Response::notFound();
        $view = new View();
        return Response::html($view->render('admin::coupons.form', [
            'title'   => 'Kupon: ' . $coupon['code'],
            'coupon'  => $coupon,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function store(Request $request): Response
    {
        $id = (int) $request->param('id');
        $code = strtoupper(trim((string) $request->input('code', '')));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,64}$/', $code)) {
            SessionManager::flash('error', 'Kod 3-64 karakter, harf/rakam/_/- olmalı.');
            return Response::redirect($id ? "/admin/kuponlar/$id" : '/admin/kuponlar/yeni');
        }

        $data = [
            'code'          => $code,
            'name'          => trim((string) $request->input('name', $code)) ?: $code,
            'type'          => (string) $request->input('type', 'percent'),
            'value'         => (float) str_replace(',', '.', (string) $request->input('value', '0')),
            'currency'      => (string) $request->input('currency', 'TRY') ?: null,
            'starts_at'     => $request->input('starts_at') ?: null,
            'ends_at'       => $request->input('ends_at') ?: null,
            'usage_limit'   => $request->input('usage_limit') ? (int) $request->input('usage_limit') : null,
            'usage_limit_per_customer' => $request->input('usage_limit_per_customer') ? (int) $request->input('usage_limit_per_customer') : null,
            'min_order_total' => $request->input('min_order_total') ? (float) $request->input('min_order_total') : null,
            'is_active'     => $request->input('is_active') ? 1 : 0,
            'auto_apply'    => $request->input('auto_apply') ? 1 : 0,
            'priority'      => max(0, min(100, (int) $request->input('priority', 0))),
        ];

        // Percent için 0-100 arası zorunlu
        if ($data['type'] === 'percent' && ($data['value'] <= 0 || $data['value'] > 100)) {
            SessionManager::flash('error', 'Yüzde tipinde değer 0-100 arası olmalı.');
            return Response::redirect($id ? "/admin/kuponlar/$id" : '/admin/kuponlar/yeni');
        }

        try {
            if ($id > 0) {
                Connection::update('coupons', $data, 'id = ?', [$id]);
                SessionManager::flash('success', "Kupon '$code' güncellendi.");
            } else {
                $exists = Connection::selectOne("SELECT id FROM coupons WHERE code = ?", [$code]);
                if ($exists) {
                    SessionManager::flash('error', "'$code' kodu zaten mevcut.");
                    return Response::redirect('/admin/kuponlar/yeni');
                }
                $id = Connection::insert('coupons', $data);
                SessionManager::flash('success', "Kupon '$code' oluşturuldu.");
            }
            return Response::redirect("/admin/kuponlar/$id");
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
            return Response::redirect('/admin/kuponlar');
        }
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        try {
            Connection::delete('coupons', 'id = ?', [$id]);
            SessionManager::flash('success', 'Kupon silindi.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Silinemedi: ' . $e->getMessage());
        }
        return Response::redirect('/admin/kuponlar');
    }
}
