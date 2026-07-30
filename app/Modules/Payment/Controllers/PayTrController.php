<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\Payment\Drivers\PayTrDriver;
use App\Services\Auth\AuthService;
use App\Services\Logger\Logger;

final class PayTrController
{
    public function checkout(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }
        $orderId = (int) $request->param('id');
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $customer = AuthService::customer();

        if (!$order || (int) $order['customer_id'] !== (int) $customer['id']) {
            return Response::notFound('Sipariş bulunamadı');
        }

        $driver = new PayTrDriver();
        $result = $driver->createCheckout($order, $customer);

        $view = new View();
        return Response::html($view->render('checkout::paytr', [
            'title'  => 'PayTR ile Ödeme',
            'order'  => $order,
            'result' => $result,
        ]));
    }

    public function callback(Request $request): Response
    {
        $payload = $request->all();
        $ip = $request->ip();
        Logger::info('PayTR callback', ['oid' => $payload['merchant_oid'] ?? '', 'status' => $payload['status'] ?? '']);

        // PayTR sabit IP kullanır → whitelist önerilir ama zorunlu değil
        \App\Modules\Payment\Services\CallbackSecurity::isAllowedIp('paytr', $ip);

        $driver = new PayTrDriver();
        $verified = $driver->handleCallback($payload);

        \App\Modules\Payment\Services\CallbackSecurity::audit(
            'paytr', (string)($payload['merchant_oid'] ?? ''),
            (bool)($verified['success'] ?? false), $payload, $ip
        );

        if (!$verified['success'] && ($verified['message'] ?? '') === 'Invalid hash') {
            return Response::make('PAYTR notification failed: bad hash', 400);
        }

        $oid = $payload['merchant_oid'] ?? '';
        $orderId = (int) ltrim(preg_replace('/[^0-9]/', '', $oid) ?? '', '0');
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

        if (!$order) {
            return Response::make('OK', 200); // PayTR OK bekliyor
        }

        // Replay koruması
        if (!\App\Modules\Payment\Services\CallbackSecurity::markProcessed('paytr', $oid, $payload)) {
            return Response::make('OK', 200);
        }

        if ($verified['success']) {
            Connection::update('orders',
                ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                'id = ?', [$orderId]
            );
            Connection::insert('payments', [
                'order_id'    => $orderId,
                'invoice_id'  => null,
                'customer_id' => (int) $order['customer_id'],
                'method'      => 'paytr',
                'amount'      => (float) $order['total'],
                'currency'    => $order['currency'],
                'gateway_transaction_id' => $oid,
                'status'      => 'success',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'processed_at'=> date('Y-m-d H:i:s'),
            ]);
            InvoiceService::markPaid((int) $order['id'], (float) $order['total']);
        } else {
            Connection::update('orders', ['status' => 'failed'], 'id = ?', [$orderId]);
            Connection::insert('payments', [
                'order_id'    => $orderId,
                'customer_id' => (int) $order['customer_id'],
                'method'      => 'paytr',
                'amount'      => (float) $order['total'],
                'currency'    => $order['currency'],
                'status'      => 'failed',
                'gateway_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'processed_at'=> date('Y-m-d H:i:s'),
            ]);
        }

        return Response::make('OK', 200);
    }

    public function success(Request $request): Response
    {
        SessionManager::flash('success', 'Ödemeniz başarıyla alındı. Siparişiniz işleme alınıyor.');
        return Response::redirect('/panel');
    }

    public function fail(Request $request): Response
    {
        SessionManager::flash('error', 'Ödeme başarısız oldu. Lütfen tekrar deneyin.');
        return Response::redirect('/sepet');
    }
}
