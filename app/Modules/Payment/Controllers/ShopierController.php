<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\Payment\PaymentManager;
use App\Services\Auth\AuthService;
use App\Services\Logger\Logger;

final class ShopierController
{
    public function checkout(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $orderId = (int) $request->param('id');
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $customer = AuthService::customer();
        if (!$order || (int) $order['customer_id'] !== (int) $customer['id']) {
            return Response::notFound('Sipariş bulunamadı');
        }

        $driver = PaymentManager::driver('shopier');
        if ($driver === null) {
            SessionManager::flash('error', 'Shopier driver yüklenemedi.');
            return Response::redirect('/odeme');
        }
        $result = $driver->createCheckout($order, $customer);

        if (!($result['success'] ?? false)) {
            SessionManager::flash('error', $result['error'] ?? 'Shopier hatası');
            return Response::redirect('/odeme');
        }

        // Auto-submit form HTML doğrudan render edilir
        Connection::update('orders', ['gateway_ref' => 'shopier'], 'id = ?', [$orderId]);
        return Response::html($result['html_form'] ?? '');
    }

    public function callback(Request $request): Response
    {
        $payload = $request->all();
        $ip = $request->ip();

        Logger::info('Shopier callback', ['order' => $payload['platform_order_id'] ?? '']);

        // Güvenlik: IP whitelist (Shopier için opsiyonel — sabit IP kullanmaz)
        \App\Modules\Payment\Services\CallbackSecurity::isAllowedIp('shopier', $ip);

        $driver = PaymentManager::driver('shopier');
        $verified = $driver !== null ? $driver->handleCallback($payload) : ['success' => false];

        \App\Modules\Payment\Services\CallbackSecurity::audit(
            'shopier',
            (string) ($verified['transaction_id'] ?? ''),
            (bool) ($verified['success'] ?? false),
            $payload,
            $ip
        );

        $orderNumber = (string) ($verified['basket_id'] ?? '');
        $order = $orderNumber !== ''
            ? Connection::selectOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber])
            : null;
        if (!$order) return Response::make('OK', 200);

        // Replay attack koruması
        $txId = (string) ($verified['transaction_id'] ?? '');
        if ($txId !== '' && !\App\Modules\Payment\Services\CallbackSecurity::markProcessed('shopier', $txId, $payload)) {
            return Response::make('OK', 200); // Duplicate — sessizce geç
        }

        if ($verified['success']) {
            Connection::update('orders', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [$order['id']]);
            Connection::insert('payments', [
                'order_id'    => (int) $order['id'],
                'customer_id' => (int) $order['customer_id'],
                'method'      => 'shopier',
                'amount'      => (float) $order['total'],
                'currency'    => $order['currency'],
                'gateway_transaction_id' => (string) ($verified['transaction_id'] ?? ''),
                'status'      => 'success',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'processed_at'=> date('Y-m-d H:i:s'),
            ]);
            InvoiceService::markPaid((int) $order['id'], (float) $order['total']);
        } else {
            Connection::update('orders', ['status' => 'failed'], 'id = ?', [$order['id']]);
        }
        return Response::make('OK', 200);
    }
}
