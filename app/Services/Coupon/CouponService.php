<?php

declare(strict_types=1);

namespace App\Services\Coupon;

use App\Core\Database\Connection;

/**
 * Kupon doğrulama ve indirim hesaplama.
 *
 * Kupon türleri: percent | fixed
 * Kısıtlar: süre, kullanım limiti, müşteri başı limit, min sepet tutarı,
 *          uygulanabilir ürünler / gruplar.
 */
final class CouponService
{
    /**
     * Kupon kodunu doğrula ve uygulanabilir mi kontrol et.
     *
     * @return array{ok:bool, coupon?:array, error?:string}
     */
    public static function validate(
        string $code,
        float $subtotal,
        string $currency = 'TRY',
        ?int $customerId = null,
        array $productIds = [],
        array $groupIds = []
    ): array {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['ok' => false, 'error' => 'Kupon kodu girilmedi.'];
        }

        try {
            $coupon = Connection::selectOne(
                "SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1",
                [$code]
            );
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Kupon sistemi şu anda kullanılamıyor.'];
        }

        if (!$coupon) {
            return ['ok' => false, 'error' => 'Kupon geçersiz veya pasif.'];
        }

        $now = time();
        if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > $now) {
            return ['ok' => false, 'error' => 'Kupon henüz aktif değil.'];
        }
        if (!empty($coupon['ends_at']) && strtotime($coupon['ends_at']) < $now) {
            return ['ok' => false, 'error' => 'Kuponun süresi dolmuş.'];
        }

        if (!empty($coupon['usage_limit']) && (int)$coupon['usage_count'] >= (int)$coupon['usage_limit']) {
            return ['ok' => false, 'error' => 'Kuponun toplam kullanım limiti dolmuş.'];
        }

        if ($customerId !== null && !empty($coupon['usage_limit_per_customer'])) {
            $used = Connection::selectOne(
                "SELECT COUNT(*) c FROM coupon_usages WHERE coupon_id = ? AND customer_id = ?",
                [$coupon['id'], $customerId]
            );
            if ((int)($used['c'] ?? 0) >= (int)$coupon['usage_limit_per_customer']) {
                return ['ok' => false, 'error' => 'Bu kuponu daha önce kullandınız.'];
            }
        }

        if (!empty($coupon['min_order_total']) && $subtotal < (float)$coupon['min_order_total']) {
            return ['ok' => false, 'error' => sprintf(
                'Bu kupon için minimum sepet tutarı %s %s.',
                number_format((float)$coupon['min_order_total'], 2, ',', '.'),
                $currency
            )];
        }

        // Uygulanabilir ürün/grup kısıtı
        if (!empty($coupon['applicable_products'])) {
            $allowed = json_decode((string)$coupon['applicable_products'], true) ?: [];
            if ($allowed && !array_intersect($productIds, $allowed)) {
                return ['ok' => false, 'error' => 'Kupon sepetteki ürünlere uygulanamaz.'];
            }
        }
        if (!empty($coupon['applicable_groups'])) {
            $allowed = json_decode((string)$coupon['applicable_groups'], true) ?: [];
            if ($allowed && !array_intersect($groupIds, $allowed)) {
                return ['ok' => false, 'error' => 'Kupon sepetteki ürün gruplarına uygulanamaz.'];
            }
        }

        return ['ok' => true, 'coupon' => $coupon];
    }

    /** İndirim tutarını hesapla. */
    public static function calculateDiscount(array $coupon, float $subtotal): float
    {
        $type = $coupon['type'] ?? 'percent';
        $value = (float) ($coupon['value'] ?? 0);

        if ($type === 'percent') {
            $discount = $subtotal * ($value / 100);
        } else {
            $discount = $value;
        }

        return round(min($discount, $subtotal), 4);
    }

    /** Kullanım kaydı oluştur. */
    /**
     * Sepete otomatik uygulanabilecek en iyi kuponu bul.
     * Şartlar: auto_apply=1, is_active=1, süre geçerli, min_order_total karşılanmış.
     * En yüksek priority + en yüksek indirim önce.
     */
    public static function findBestAutoApply(float $subtotal, ?int $customerId = null): ?array
    {
        try {
            $rows = \App\Core\Database\Connection::select(
                "SELECT * FROM coupons
                 WHERE auto_apply = 1 AND is_active = 1
                   AND (starts_at IS NULL OR starts_at <= NOW())
                   AND (ends_at IS NULL OR ends_at >= NOW())
                   AND (min_order_total IS NULL OR min_order_total <= ?)
                   AND (usage_limit IS NULL OR usage_count < usage_limit)
                 ORDER BY priority DESC, value DESC"
                , [$subtotal]
            );
            if (empty($rows)) return null;

            // Müşteri başına limit kontrolü
            foreach ($rows as $c) {
                if ($customerId && !empty($c['usage_limit_per_customer'])) {
                    $used = (int) (\App\Core\Database\Connection::selectOne(
                        "SELECT COUNT(*) c FROM coupon_usages WHERE coupon_id = ? AND customer_id = ?",
                        [$c['id'], $customerId]
                    )['c'] ?? 0);
                    if ($used >= (int) $c['usage_limit_per_customer']) continue;
                }
                return $c;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function recordUsage(int $couponId, int $customerId, ?int $orderId, float $amount, string $currency = 'TRY'): void
    {
        Connection::insert('coupon_usages', [
            'coupon_id'       => $couponId,
            'customer_id'     => $customerId,
            'order_id'        => $orderId,
            'discount_amount' => $amount,
            'currency'        => $currency,
        ]);
        Connection::query("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?", [$couponId]);
    }
}
