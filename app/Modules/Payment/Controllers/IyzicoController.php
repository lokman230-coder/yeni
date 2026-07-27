<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\Payment\PaymentManager;
use App\Services\Auth\AuthService;
use App\Services\Logger\Logger;

final class IyzicoController
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

        $driver = PaymentManager::driver('iyzico');
        if ($driver === null) {
            SessionManager::flash('error', 'iyzico driver yüklenemedi.');
            return Response::redirect('/odeme');
        }
        $result = $driver->createCheckout($order, $customer);

        // Başarılı → doğrudan iyzico sayfasına yönlendir
        if (($result['success'] ?? false) && !empty($result['redirect_url'])) {
            // conversationId'yi orders tablosuna ekleyelim, callback'te lazım
            Connection::update('orders',
                ['gateway_ref' => $result['conversation_id'] ?? null],
                'id = ?', [$orderId]
            );
            return Response::redirect($result['redirect_url']);
        }

        $view = new View();
        return Response::html($view->render('checkout::gateway', [
            'title'   => 'iyzico ile Ödeme',
            'order'   => $order,
            'result'  => $result,
            'gateway' => 'iyzico',
            'env_keys'=> ['IYZICO_API_KEY','IYZICO_SECRET_KEY','IYZICO_SANDBOX'],
        ]));
    }

    public function callback(Request $request): Response
    {
        $payload = $request->all();
        $ip = $request->ip();
        Logger::info('iyzico callback', ['token' => substr((string)($payload['token'] ?? ''), 0, 12)]);

        $driver = PaymentManager::driver('iyzico');
        $verified = $driver !== null ? $driver->handleCallback($payload) : ['success' => false, 'message' => 'no driver'];

        \App\Modules\Payment\Services\CallbackSecurity::audit(
            'iyzico', (string)($verified['transaction_id'] ?? ''),
            (bool)($verified['success'] ?? false), $payload, $ip
        );

        $orderNumber = (string) ($verified['basket_id'] ?? '');
        $order = $orderNumber !== ''
            ? Connection::selectOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber])
            : null;

        if (!$order) {
            SessionManager::flash('error', 'Sipariş bulunamadı.');
            return Response::redirect('/panel');
        }

        // Replay koruması
        $txId = (string)($verified['transaction_id'] ?? '');
        if ($txId !== '' && !\App\Modules\Payment\Services\CallbackSecurity::markProcessed('iyzico', $txId, $payload)) {
            SessionManager::flash('info', 'Ödeme zaten işlenmiş.');
            return Response::redirect('/odeme/basarili/' . $order['id']);
        }

        if ($verified['success']) {
            Connection::update('orders',
                ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                'id = ?', [$order['id']]
            );
            Connection::insert('payments', [
                'order_id'    => (int) $order['id'],
                'invoice_id'  => null,
                'customer_id' => (int) $order['customer_id'],
                'method'      => 'iyzico',
                'amount'      => (float) $order['total'],
                'currency'    => $order['currency'],
                'gateway_transaction_id' => (string) ($verified['transaction_id'] ?? ''),
                'status'      => 'success',
                'gateway_response' => json_encode($verified['raw'] ?? $payload, JSON_UNESCAPED_UNICODE),
                'processed_at'=> date('Y-m-d H:i:s'),
            ]);
            InvoiceService::markPaid((int) $order['id'], (float) $order['total']);
            SessionManager::flash('success', 'Ödemeniz başarıyla alındı.');
            return Response::redirect('/odeme/basarili/' . $order['id']);
        }

        Connection::update('orders', ['status' => 'failed'], 'id = ?', [$order['id']]);
        Connection::insert('payments', [
            'order_id'    => (int) $order['id'],
            'customer_id' => (int) $order['customer_id'],
            'method'      => 'iyzico',
            'amount'      => (float) $order['total'],
            'currency'    => $order['currency'],
            'status'      => 'failed',
            'gateway_response' => json_encode($verified['raw'] ?? $payload, JSON_UNESCAPED_UNICODE),
            'processed_at'=> date('Y-m-d H:i:s'),
        ]);
        SessionManager::flash('error', 'Ödeme başarısız: ' . ($verified['message'] ?? ''));
        return Response::redirect('/sepet');
    }
}
