<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Services;

use App\Core\Database\Connection;

final class InvoiceService
{
    public static function createFromOrder(int $orderId, array $totals): int
    {
        $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) throw new \RuntimeException("Order not found: {$orderId}");

        $number = self::generateNumber();
        $today = date('Y-m-d');
        $due   = date('Y-m-d', strtotime('+7 days'));

        $invoiceId = Connection::insert('invoices', [
            'invoice_number' => $number,
            'order_id'       => $orderId,
            'customer_id'    => (int) $order['customer_id'],
            'status'         => 'unpaid',
            'issue_date'     => $today,
            'due_date'       => $due,
            'subtotal'       => $totals['subtotal'],
            'discount_total' => $totals['discount'],
            'tax_total'      => $totals['tax'],
            'total'          => $totals['total'],
            'balance'        => $totals['total'],
            'currency'       => $totals['currency'],
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        foreach ($totals['items'] as $it) {
            Connection::insert('invoice_items', [
                'invoice_id'  => $invoiceId,
                'description' => $it['product_name'] . ' (' . ($it['period_label'] ?? $it['period']) . ')',
                'quantity'    => (int) $it['quantity'],
                'unit_price'  => (float) $it['display_price'],
                'tax_rate'    => (float) $totals['tax_rate'],
                'line_total'  => (float) $it['line_total'],
            ]);
        }

        return $invoiceId;
    }

    /**
     * Siparişe bağlı olmayan manuel fatura oluşturur (Teklif kabulü / Faturalandırılabilir Ürünler).
     * @param array<int,array{description:string,quantity:int,unit_price:float,tax_rate:float}> $items
     */
    public static function createManual(int $customerId, array $items, string $currency = 'TRY', ?string $notes = null): int
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($items as $it) {
            $lineTotal = (float) $it['unit_price'] * (int) $it['quantity'];
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ((float) ($it['tax_rate'] ?? 0) / 100);
        }
        $total = $subtotal + $taxTotal;

        $number = self::generateNumber();
        $today = date('Y-m-d');
        $due = date('Y-m-d', strtotime('+7 days'));

        $invoiceId = Connection::insert('invoices', [
            'invoice_number' => $number,
            'order_id'       => null,
            'customer_id'    => $customerId,
            'status'         => 'unpaid',
            'issue_date'     => $today,
            'due_date'       => $due,
            'subtotal'       => $subtotal,
            'discount_total' => 0,
            'tax_total'      => $taxTotal,
            'total'          => $total,
            'balance'        => $total,
            'currency'       => $currency,
            'notes'          => $notes,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        foreach ($items as $i => $it) {
            $lineTotal = (float) $it['unit_price'] * (int) $it['quantity'];
            Connection::insert('invoice_items', [
                'invoice_id'  => $invoiceId,
                'description' => (string) $it['description'],
                'quantity'    => (int) $it['quantity'],
                'unit_price'  => (float) $it['unit_price'],
                'tax_rate'    => (float) ($it['tax_rate'] ?? 0),
                'line_total'  => $lineTotal,
                'sort_order'  => $i,
            ]);
        }

        return $invoiceId;
    }

    public static function markPaid(int $orderId, float $amount): void
    {
        $invoice = Connection::selectOne("SELECT * FROM invoices WHERE order_id = ?", [$orderId]);
        if (!$invoice) return;

        $newPaid = (float) $invoice['paid_total'] + $amount;
        $balance = max(0, (float) $invoice['total'] - $newPaid);
        $status = $balance <= 0.01 ? 'paid' : 'partially_paid';

        Connection::update('invoices', [
            'paid_total' => $newPaid,
            'balance'    => $balance,
            'status'     => $status,
            'paid_at'    => $status === 'paid' ? date('Y-m-d H:i:s') : $invoice['paid_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$invoice['id']]);

        // Faz 6b: Fatura tam ödendiyse referans komisyonu oluştur
        if ($status === 'paid') {
            try {
                $order = Connection::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
                if ($order && class_exists(\App\Modules\Referral\Services\ReferralService::class)) {
                    \App\Modules\Referral\Services\ReferralService::onOrderPaid($order);
                }
            } catch (\Throwable $e) {
                \App\Services\Logger\Logger::warning('Referral hook (onOrderPaid) failed: ' . $e->getMessage());
            }

            // Mobile Builder: ödeme sonrası bekleyen APK/AAB işlerini tetikle.
            try {
                $mobileJobs = Connection::select("SELECT id FROM mobile_build_jobs WHERE invoice_id = ? AND status IN ('queued','waiting_worker')", [$invoice['id']]);
                foreach ($mobileJobs as $mobileJob) {
                    if (class_exists(\App\Modules\Builder\Services\MobileBuildService::class)) {
                        \App\Modules\Builder\Services\MobileBuildService::dispatch((int) $mobileJob['id']);
                    }
                }
            } catch (\Throwable $e) {
                \App\Services\Logger\Logger::warning('Mobile Builder payment hook failed: ' . $e->getMessage());
            }

            // Faz 6d: Otomatik provisioning (hosting/vps hesap açılışı)
            try {
                if (class_exists(\App\Modules\Hosting\Services\ProvisionService::class)) {
                    \App\Modules\Hosting\Services\ProvisionService::provisionOrder($orderId);
                }
            } catch (\Throwable $e) {
                \App\Services\Logger\Logger::warning('Provision hook failed: ' . $e->getMessage());
            }
        }
    }

    public static function generateNumber(): string
    {
        return 'INV-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
