<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Services;

use App\Core\Database\Connection;
use App\Core\SessionManager;
use App\Modules\Cart\Services\CartService;
use App\Modules\Invoice\Services\InvoiceService;
use App\Services\Coupon\CouponService;

/**
 * Sepetten sipariş + fatura oluşturma.
 */
final class CheckoutService
{
    /**
     * @return array{ok:bool, order?:array, invoice?:array, error?:string}
     */
    public static function createOrder(int $customerId, string $paymentMethod, ?string $couponCode = null, array $meta = []): array
    {
        $totals = CartService::totals($couponCode);
        if (empty($totals['items'])) {
            return ['ok' => false, 'error' => 'Sepet boş.'];
        }

        Connection::beginTransaction();
        try {
            $orderNumber = self::generateOrderNumber();

            $orderId = Connection::insert('orders', [
                'order_number'   => $orderNumber,
                'customer_id'    => $customerId,
                'status'         => 'pending',
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'tax_total'      => $totals['tax'],
                'total'          => $totals['total'],
                'currency'       => $totals['currency'],
                'coupon_id'      => !empty($totals['coupon']) ? (int) $totals['coupon']['id'] : null,
                'coupon_code'    => !empty($totals['coupon']) ? $totals['coupon']['code'] : null,
                'payment_method' => $paymentMethod,
                'ip_address'     => $meta['ip'] ?? null,
                'user_agent'     => $meta['user_agent'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            foreach ($totals['items'] as $it) {
                Connection::insert('order_items', [
                    'order_id'      => $orderId,
                    'product_id'    => (int) $it['product_id'],
                    'product_name'  => $it['product_name'],
                    'period'        => $it['period'],
                    'quantity'      => (int) $it['quantity'],
                    'domain_action' => $it['domain_action'],
                    'domain_name'   => $it['domain_name'],
                    'addons'        => $it['addons'],
                    'custom_fields' => $it['custom_fields'],
                    'unit_price'    => $it['display_price'],
                    'line_total'    => $it['line_total'],
                    'currency'      => $totals['currency'],
                    'status'        => 'pending',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }

            // Fatura oluştur
            $invoiceId = InvoiceService::createFromOrder($orderId, $totals);

            // Kupon kullanımını kaydet
            if (!empty($totals['coupon'])) {
                CouponService::recordUsage(
                    (int) $totals['coupon']['id'],
                    $customerId,
                    $orderId,
                    (float) $totals['discount'],
                    $totals['currency']
                );
            }

            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollback();
            \App\Services\Logger\Logger::error('Checkout failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'Sipariş oluşturulurken bir hata oluştu.'];
        }

        // Sepeti temizle
        CartService::clear();
        SessionManager::forget('cart_coupon');

        return [
            'ok'      => true,
            'order'   => Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]),
            'invoice' => Connection::selectOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]),
        ];
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
