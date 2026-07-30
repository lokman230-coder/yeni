<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Core\Database\Connection;
use App\Support\Slug;

/**
 * Vendor (satıcı) yönetim servisi.
 * Marketplace'te ürün satan 3. parti müşterilerin komisyon + kazanç işlemleri.
 */
final class VendorService
{
    public const DEFAULT_COMMISSION_RATE = 15.0;

    /** Yeni vendor başvurusu */
    public static function apply(int $customerId, array $data): array
    {
        $existing = Connection::selectOne("SELECT id FROM vendors WHERE customer_id = ?", [$customerId]);
        if ($existing) {
            return ['ok' => false, 'error' => 'Zaten bir vendor kaydınız var.'];
        }

        $shopName = trim((string) ($data['shop_name'] ?? ''));
        if ($shopName === '') {
            return ['ok' => false, 'error' => 'Mağaza adı zorunludur.'];
        }

        $slug = Slug::make($shopName);
        // Slug unique kontrolü
        $suffix = 0;
        while (Connection::selectOne("SELECT id FROM vendors WHERE shop_slug = ?", [$suffix ? "$slug-$suffix" : $slug])) {
            $suffix++;
        }
        $finalSlug = $suffix ? "$slug-$suffix" : $slug;

        $id = Connection::insert('vendors', [
            'customer_id'     => $customerId,
            'shop_name'       => $shopName,
            'shop_slug'       => $finalSlug,
            'description'     => (string) ($data['description'] ?? '') ?: null,
            'contact_email'   => (string) ($data['contact_email'] ?? '') ?: null,
            'contact_phone'   => (string) ($data['contact_phone'] ?? '') ?: null,
            'website'         => (string) ($data['website'] ?? '') ?: null,
            'country'         => strtoupper(substr((string)($data['country'] ?? 'TR'), 0, 2)),
            'city'            => (string) ($data['city'] ?? '') ?: null,
            'tax_office'      => (string) ($data['tax_office'] ?? '') ?: null,
            'tax_id'          => (string) ($data['tax_id'] ?? '') ?: null,
            'iban'            => (string) ($data['iban'] ?? '') ?: null,
            'iban_holder'     => (string) ($data['iban_holder'] ?? '') ?: null,
            'commission_rate' => (float) \App\Services\Settings\SettingsManager::get('marketplace.default_commission', self::DEFAULT_COMMISSION_RATE),
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'vendor_id' => $id];
    }

    /** Admin onayı */
    public static function approve(int $vendorId, int $adminId): bool
    {
        Connection::update('vendors', [
            'status'      => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$vendorId]);
        \App\Services\Logger\ActivityLog::log('vendor.approved', 'vendor', $vendorId, "Admin #$adminId tarafından onaylandı");
        return true;
    }

    /** Sipariş ödendiğinde vendor'lara komisyon kaydı düş */
    public static function recordSaleFromOrder(int $orderId): int
    {
        $items = Connection::select(
            "SELECT oi.*, ml.vendor_id, ml.commission_rate_override
             FROM order_items oi
             LEFT JOIN marketplace_listings ml ON ml.id = oi.listing_id
             WHERE oi.order_id = ? AND ml.vendor_id IS NOT NULL",
            [$orderId]
        );
        if (!$items) return 0;

        $count = 0;
        foreach ($items as $item) {
            $vendor = Connection::selectOne("SELECT * FROM vendors WHERE id = ?", [$item['vendor_id']]);
            if (!$vendor) continue;

            $rate = (float) ($item['commission_rate_override'] ?? $vendor['commission_rate']);
            $gross = (float) $item['line_total'];
            $commission = round($gross * $rate / 100, 2);
            $net = round($gross - $commission, 2);

            Connection::insert('vendor_earnings', [
                'vendor_id'         => (int) $vendor['id'],
                'order_id'          => $orderId,
                'order_item_id'     => (int) $item['id'],
                'listing_id'        => $item['listing_id'] ?? null,
                'gross_amount'      => $gross,
                'commission_rate'   => $rate,
                'commission_amount' => $commission,
                'net_earnings'      => $net,
                'currency'          => (string) $item['currency'],
                'status'            => 'pending',   // 14 gün sonra "available" olur (iade süresi)
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            // Vendor toplam satış güncelle
            Connection::query(
                "UPDATE vendors SET total_sales = total_sales + ?, updated_at = NOW() WHERE id = ?",
                [$gross, $vendor['id']]
            );
            $count++;
        }
        return $count;
    }

    /** Payout istekleri için müsait bakiye */
    public static function availableBalance(int $vendorId): float
    {
        $r = Connection::selectOne(
            "SELECT COALESCE(SUM(net_earnings),0) t FROM vendor_earnings WHERE vendor_id = ? AND status = 'available'",
            [$vendorId]
        );
        return (float) ($r['t'] ?? 0);
    }

    /** Vendor payout talebi */
    public static function requestPayout(int $vendorId, float $amount): array
    {
        $available = self::availableBalance($vendorId);
        if ($amount > $available) {
            return ['ok' => false, 'error' => "Müsait bakiye yetersiz: " . number_format($available, 2) . " TRY"];
        }

        $vendor = Connection::selectOne("SELECT * FROM vendors WHERE id = ?", [$vendorId]);
        if (!$vendor || empty($vendor['iban'])) {
            return ['ok' => false, 'error' => 'IBAN tanımlanmamış.'];
        }

        $id = Connection::insert('vendor_payouts', [
            'vendor_id'   => $vendorId,
            'amount'      => $amount,
            'currency'    => 'TRY',
            'method'      => 'bank_transfer',
            'iban'        => (string) $vendor['iban'],
            'iban_holder' => (string) ($vendor['iban_holder'] ?? ''),
            'status'      => 'requested',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'payout_id' => $id];
    }

    /** Pending kazançları 14 günden eski ise available yap (cron) */
    public static function maturePendingEarnings(int $holdDays = 14): int
    {
        $st = Connection::pdo()->prepare(
            "UPDATE vendor_earnings
             SET status = 'available', updated_at = NOW()
             WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $st->execute([$holdDays]);
        return $st->rowCount();
    }
}
