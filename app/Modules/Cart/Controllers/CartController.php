<?php

declare(strict_types=1);

namespace App\Modules\Cart\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Cart\Services\CartService;

final class CartController
{
    public function index(Request $request): Response
    {
        $coupon = (string) SessionManager::get('cart_coupon', '');
        $totals = CartService::totals($coupon !== '' ? $coupon : null);

        $view = new View();
        return Response::html($view->render('cart::index', [
            'title'  => 'Sepetiniz',
            'totals' => $totals,
        ]));
    }

    public function add(Request $request): Response
    {
        $productId = (int) $request->input('product_id', 0);
        $period    = (string) $request->input('period', 'monthly');
        if ($productId <= 0) {
            SessionManager::flash('error', 'Ürün belirtilmedi.');
            return Response::redirect($request->header('Referer') ?? '/');
        }

        $result = CartService::add($productId, $period, [
            'quantity'      => (int) $request->input('quantity', 1),
            'domain_action' => $request->input('domain_action'),
            'domain_name'   => $request->input('domain_name'),
            'addons'        => (array) $request->input('addons', []),
            'custom_fields' => (array) $request->input('custom_fields', []),
            'options'       => (array) $request->input('options', []),  // Paket Opsiyonları (Rapor 5.3)
        ]);

        if (!$result['ok']) {
            SessionManager::flash('error', $result['error'] ?? 'Sepete eklenemedi.');
            return Response::redirect($request->header('Referer') ?? '/');
        }

        SessionManager::flash('success', 'Ürün sepete eklendi.');
        return Response::redirect('/sepet');
    }

    public function remove(Request $request): Response
    {
        CartService::remove((int) $request->param('id'));
        SessionManager::flash('success', 'Ürün sepetten çıkarıldı.');
        return Response::redirect('/sepet');
    }

    public function clear(Request $request): Response
    {
        CartService::clear();
        SessionManager::forget('cart_coupon');
        SessionManager::flash('success', 'Sepet temizlendi.');
        return Response::redirect('/sepet');
    }

    public function applyCoupon(Request $request): Response
    {
        $code = trim((string) $request->input('coupon_code', ''));
        if ($code === '') {
            SessionManager::forget('cart_coupon');
            SessionManager::flash('info', 'Kupon kaldırıldı.');
        } else {
            SessionManager::set('cart_coupon', $code);
            $totals = CartService::totals($code);
            if (!empty($totals['coupon_error'])) {
                SessionManager::forget('cart_coupon');
                SessionManager::flash('error', $totals['coupon_error']);
            } else {
                SessionManager::flash('success', 'Kupon uygulandı: ' . $totals['formatted']['discount'] . ' indirim.');
            }
        }
        return Response::redirect('/sepet');
    }
}
