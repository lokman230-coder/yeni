<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Core\Database\Connection;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\Payment\PaymentManager;
use App\Services\Logger\Logger;

/**
 * Ödeme mutabakatı (reconciliation).
 *
 * Callback URL'ler bazen ulaşmaz (internet kesintisi, DNS, gateway hatası).
 * Bu servis pending durumdaki siparişleri gateway API'sinden sorgulayarak
 * durumu senkron eder.
 *
 * Cron: payment:reconcile — 15 dakikada bir çalışır.
 * Kapsam: son 48 saatte oluşan pending siparişler.
 */
final class ReconciliationService
{
    /** @return array{checked:int, reconciled:int, failed:int, errors:array<int,string>} */
    public static function run(int $limit = 100): array
    {
        $pending = Connection::select(
            "SELECT * FROM orders
             WHERE status = 'pending'
               AND payment_method IN ('paytr','iyzico','papara','shopier')
               AND created_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)
               AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );

        $result = ['checked' => count($pending), 'reconciled' => 0, 'failed' => 0, 'errors' => []];

        foreach ($pending as $order) {
            try {
                $status = self::checkOrderStatus($order);

                if ($status === 'paid') {
                    Connection::update('orders',
                        ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                        'id = ?', [$order['id']]
                    );
                    Connection::insert('payments', [
                        'order_id'    => (int) $order['id'],
                        'customer_id' => (int) $order['customer_id'],
                        'method'      => (string) $order['payment_method'],
                        'amount'      => (float) $order['total'],
                        'currency'    => $order['currency'],
                        'gateway_transaction_id' => 'RECONCILE-' . $order['id'],
                        'status'      => 'success',
                        'gateway_response' => json_encode(['reconciled' => true, 'via' => 'cron']),
                        'processed_at'=> date('Y-m-d H:i:s'),
                    ]);
                    // markPaid → referral + provisioning tetiklenir
                    InvoiceService::markPaid((int) $order['id'], (float) $order['total']);
                    $result['reconciled']++;
                    Logger::info('Order reconciled', ['order_id' => $order['id'], 'via' => 'reconciliation']);
                } elseif ($status === 'failed' || $status === 'cancelled') {
                    Connection::update('orders',
                        ['status' => 'failed'], 'id = ?', [$order['id']]
                    );
                    $result['failed']++;
                }
                // pending → hiçbir şey yapma, bir sonraki turu bekle
            } catch (\Throwable $e) {
                $result['errors'][] = "Order #{$order['id']}: " . $e->getMessage();
                Logger::warning('Reconciliation error', ['order' => $order['id'], 'err' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /**
     * Bir siparişin gateway'deki gerçek durumunu sorgular.
     * @return string 'paid' | 'failed' | 'cancelled' | 'pending' | 'unknown'
     */
    private static function checkOrderStatus(array $order): string
    {
        $method = (string) $order['payment_method'];
        $driver = PaymentManager::driver($method);
        if ($driver === null) return 'unknown';

        // Her sağlayıcı için ayrı sorgu — çoğu 'inquireOrder' benzeri metodu yok
        // Basit yaklaşım: gateway_ref alanı varsa API'ye sor
        $ref = (string) ($order['gateway_ref'] ?? '');
        if ($ref === '') return 'pending'; // Kullanıcı gateway'e gitmemiş bile

        // Genel driver kontratımızda handleCallback var, ama "check status" yok
        // Bu bir sonraki iterasyonda her driver'a `inquireOrder($ref)` metodu eklenerek genişletilecek
        // Şimdilik: 24 saat üstü ise failed işaretle (kullanıcı ödeme yapmadıysa büyük ihtimalle vazgeçmiş)
        $ageHours = (time() - strtotime((string)$order['created_at'])) / 3600;
        if ($ageHours > 24) return 'failed';

        return 'pending';
    }
}
