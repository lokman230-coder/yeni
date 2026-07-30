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

final class PaparaController
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

        $driver = PaymentManager::driver('papara');
        if ($driver === null) {
            SessionManager::flash('error', 'Papara driver yüklenemedi.');
            return Response::redirect('/odeme');
        }
        $result = $driver->createCheckout($order, $customer);

        if (($result['success'] ?? false) && !empty($result['redirect_url'])) {
            Connection::update('orders',
                ['gateway_ref' => (string) ($result['payment_id'] ?? '')],
                'id = ?', [$orderId]
            );
            return Response::redirect($result['redirect_url']);
        }

        $view = new View();
        return Response::html($view->render('checkout::gateway', [
            'title'   => 'Papara ile Ödeme',
            'order'   => $order,
            'result'  => $result,
            'gateway' => 'papara',
            'env_keys'=> ['PAPARA_API_KEY','PAPARA_SANDBOX'],
        ]));
    }

    public function callback(Request $request): Response
    {
        $payload = $request->all();
        // Papara raw JSON POST gönderir
        $rawInput = file_get_contents('php://input');
        if ($rawInput) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            }
        }
        Logger::info('Papara callback', ['id' => substr((string)($payload['data']['id'] ?? $payload['id'] ?? ''), 0, 20)]);

        $driver = PaymentManager::driver('papara');
        $verified = $driver !== null ? $driver->handleCallback($payload) : ['success' => false, 'message' => 'no driver'];

        \App\Modules\Payment\Services\CallbackSecurity::audit(
            'papara', (string)($verified['transaction_id'] ?? ''),
            (bool)($verified['success'] ?? false), $payload, $request->ip()
        );

        $ref = (string) ($verified['basket_id'] ?? '');
        $order = $ref !== ''
            ? Connection::selectOne("SELECT * FROM orders WHERE order_number = ?", [$ref])
            : null;

        if (!$order) {
            return Response::json(['ok' => false, 'msg' => 'order not found'], 404);
        }

        // Replay koruması
        $txId = (string)($verified['transaction_id'] ?? '');
        if ($txId !== '' && !\App\Modules\Payment\Services\CallbackSecurity::markProcessed('papara', $txId, $payload)) {
            return Response::make('OK', 200);
        }

        if ($verified['success']) {
            Connection::update('orders',
                ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                'id = ?', [$order['id']]
            );
            Connection::insert('payments', [
                'order_id'    => (int) $order['id'],
                'customer_id' => (int) $order['customer_id'],
                'method'      => 'papara',
                'amount'      => (float) $order['total'],
                'currency'    => $order['currency'],
                'gateway_transaction_id' => (string) ($verified['transaction_id'] ?? ''),
                'status'      => 'success',
                'gateway_response' => json_encode($verified['raw'] ?? $payload, JSON_UNESCAPED_UNICODE),
                'processed_at'=> date('Y-m-d H:i:s'),
            ]);
            InvoiceService::markPaid((int) $order['id'], (float) $order['total']);
        } else {
            Connection::update('orders', ['status' => 'failed'], 'id = ?', [$order['id']]);
        }
        // Papara sadece "OK" bekler
        return Response::make('OK', 200);
    }

    public function returnUrl(Request $request): Response
    {
        SessionManager::flash('info', 'Papara ödeme sonucu birkaç saniye içinde bildirilecek. Bu ekranı kapatabilirsiniz.');
        return Response::redirect('/panel/siparislerim');
    }
}
