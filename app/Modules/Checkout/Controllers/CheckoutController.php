<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Payment\PaymentManager;
use App\Services\Auth\AuthService;

final class CheckoutController
{
    public function index(Request $request): Response
    {
        $couponCode = (string) SessionManager::get('cart_coupon', '');
        $totals = CartService::totals($couponCode !== '' ? $couponCode : null);

        if (empty($totals['items'])) {
            SessionManager::flash('info', 'Sepetinizde ürün bulunmuyor.');
            return Response::redirect('/sepet');
        }

        // Müşteri girişi yoksa: giriş / kayıt istiyoruz
        if (!AuthService::isCustomer()) {
            SessionManager::flash('info', 'Ödemeye devam etmek için giriş yapın veya kayıt olun.');
            SessionManager::set('after_login_redirect', '/odeme');
            return Response::redirect('/giris');
        }

        $view = new View();
        return Response::html($view->render('checkout::index', [
            'title'    => 'Ödeme',
            'totals'   => $totals,
            'customer' => AuthService::customer(),
            'gateways' => PaymentManager::available(),
        ]));
    }

    public function process(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }

        $method = (string) $request->input('payment_method', 'paytr');
        $internal = ['bank_transfer', 'balance', 'manual'];
        $gatewayIds = array_map(fn($g) => $g['id'], PaymentManager::available());
        $allowed = array_merge($internal, $gatewayIds, ['paytr']); // paytr her zaman kabul et (test ekranı gösterir)
        if (!in_array($method, $allowed, true)) {
            SessionManager::flash('error', 'Geçerli bir ödeme yöntemi seçin.');
            return Response::redirect('/odeme');
        }

        $customer = AuthService::customer();
        $couponCode = (string) SessionManager::get('cart_coupon', '');

        $result = CheckoutService::createOrder(
            (int) $customer['id'],
            $method,
            $couponCode !== '' ? $couponCode : null,
            [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        if (!$result['ok']) {
            SessionManager::flash('error', $result['error']);
            return Response::redirect('/odeme');
        }

        // Ödeme yönlendirmesi
        return match ($method) {
            'paytr'         => Response::redirect('/odeme/paytr/'   . $result['order']['id']),
            'iyzico'        => Response::redirect('/odeme/iyzico/'  . $result['order']['id']),
            'papara'        => Response::redirect('/odeme/papara/'  . $result['order']['id']),
            'shopier'       => Response::redirect('/odeme/shopier/' . $result['order']['id']),
            'bank_transfer' => Response::redirect('/odeme/basarili/' . $result['order']['id'] . '?havale=1'),
            'balance'       => Response::redirect('/odeme/basarili/' . $result['order']['id']),
            'manual'        => Response::redirect('/odeme/basarili/' . $result['order']['id']),
            default         => Response::redirect('/odeme/basarili/' . $result['order']['id']),
        };
    }

    public function success(Request $request): Response
    {
        $orderId = (int) $request->param('id');
        $order = \App\Core\Database\Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            return Response::notFound('Sipariş bulunamadı');
        }
        $view = new View();
        return Response::html($view->render('checkout::success', [
            'title' => 'Sipariş Alındı',
            'order' => $order,
            'havale'=> $request->query('havale') === '1',
        ]));
    }
}
