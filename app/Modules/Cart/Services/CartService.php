<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Core\Database\Connection;
use App\Core\SessionManager;
use App\Modules\Product\Services\OptionService;
use App\Modules\Product\Services\PricingService;
use App\Modules\Product\Services\ProductRepository;
use App\Services\Coupon\CouponService;
use App\Services\Currency\CurrencyService;
use App\Services\Tax\TaxService;

/**
 * Sepet servisi.
 *
 * - Ziyaretçi: session_id ile bağlı (cart_items.session_id)
 * - Müşteri: customer_id ile bağlı
 * - Şu anki para birimi ile tüm fiyatlar tutarlı gösterilir.
 */
final class CartService
{
    public static function ownerKey(): array
    {
        $customerId = SessionManager::get('customer_id');
        if ($customerId) {
            return ['customer_id', (int) $customerId];
        }
        $sid = self::sessionId();
        return ['session_id', $sid];
    }

    private static function sessionId(): string
    {
        return session_id() ?: 'anon';
    }

    public static function items(): array
    {
        try {
            [$col, $val] = self::ownerKey();
            $rows = Connection::select(
                "SELECT ci.*, p.name AS product_name, p.slug AS product_slug, p.type AS product_type
                 FROM cart_items ci
                 JOIN products p ON p.id = ci.product_id
                 WHERE ci.{$col} = ?
                 ORDER BY ci.id ASC",
                [$val]
            );
        } catch (\Throwable) {
            return [];
        }

        $currency = CurrencyService::current();
        foreach ($rows as &$r) {
            // Sepetteki fiyat kaydedildiği anki para biriminde tutulur.
            // Şu anki para birimine çevrilerek gösterilir.
            $displayPrice = CurrencyService::convert((float)$r['unit_price'], $r['currency'], $currency);
            $r['display_price'] = $displayPrice;
            $r['display_currency'] = $currency;
            $r['display_formatted'] = CurrencyService::format($displayPrice, $currency);
            $r['line_total'] = $displayPrice * max(1, (int)$r['quantity']);
            $r['line_total_formatted'] = CurrencyService::format($r['line_total'], $currency);
            $r['period_label'] = PricingService::periodLabel($r['period']);
            $r['addons_parsed'] = $r['addons'] ? json_decode((string)$r['addons'], true) : [];
            $r['custom_fields_parsed'] = $r['custom_fields'] ? json_decode((string)$r['custom_fields'], true) : [];

            // Paket opsiyonları (Rapor 5.3)
            $r['options_parsed'] = OptionService::forCartItem((int)$r['id']);
            $optionsDelta = 0.0;
            foreach ($r['options_parsed'] as $opt) {
                $optionsDelta += (float) $opt['price_delta_snapshot'];
            }
            if ($optionsDelta !== 0.0) {
                $deltaConverted = CurrencyService::convert($optionsDelta, $r['currency'], $currency);
                $r['options_delta'] = $deltaConverted;
                $r['line_total'] += $deltaConverted * max(1, (int)$r['quantity']);
                $r['line_total_formatted'] = CurrencyService::format($r['line_total'], $currency);
            } else {
                $r['options_delta'] = 0.0;
            }
        }
        return $rows;
    }

    public static function itemCount(): int
    {
        try {
            [$col, $val] = self::ownerKey();
            $row = Connection::selectOne(
                "SELECT COUNT(*) c FROM cart_items WHERE {$col} = ?", [$val]
            );
            return (int) ($row['c'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function add(int $productId, string $period, array $options = []): array
    {
        $product = ProductRepository::find($productId);
        if (!$product) return ['ok' => false, 'error' => 'Ürün bulunamadı.'];

        $price = PricingService::priceFor($productId, $period);
        if (!$price) return ['ok' => false, 'error' => 'Bu periyot için fiyat bulunamadı.'];

        [$col, $val] = self::ownerKey();
        $addons = $options['addons'] ?? [];
        $customFields = $options['custom_fields'] ?? [];
        $domainAction = $options['domain_action'] ?? null;
        $domainName   = $options['domain_name'] ?? null;

        $cartItemId = Connection::insert('cart_items', [
            $col               => $val,
            'product_id'       => $productId,
            'period'           => $period,
            'quantity'         => (int) ($options['quantity'] ?? 1),
            'domain_action'    => $domainAction,
            'domain_name'      => $domainName,
            'addons'           => $addons ? json_encode($addons, JSON_UNESCAPED_UNICODE) : null,
            'custom_fields'    => $customFields ? json_encode($customFields, JSON_UNESCAPED_UNICODE) : null,
            'unit_price'       => $price['price'],
            'currency'         => $price['currency'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        // Paket Opsiyonları (Rapor 5.3) — seçili opsiyonları snapshot olarak kaydet
        $selectedOptions = $options['options'] ?? [];
        if (is_array($selectedOptions) && $selectedOptions && $cartItemId) {
            OptionService::attachToCartItem((int)$cartItemId, $selectedOptions);
        }

        return ['ok' => true, 'cart_item_id' => $cartItemId];
    }

    public static function remove(int $itemId): void
    {
        [$col, $val] = self::ownerKey();
        Connection::query(
            "DELETE FROM cart_items WHERE id = ? AND {$col} = ?",
            [$itemId, $val]
        );
    }

    public static function clear(): void
    {
        [$col, $val] = self::ownerKey();
        Connection::query("DELETE FROM cart_items WHERE {$col} = ?", [$val]);
    }

    /**
     * Sepet toplamı: ara toplam, indirim, vergi, toplam.
     */
    public static function totals(?string $couponCode = null): array
    {
        $items = self::items();
        $currency = CurrencyService::current();
        $subtotal = 0.0;
        $productIds = [];

        foreach ($items as $it) {
            $subtotal += (float) $it['line_total'];
            $productIds[] = (int) $it['product_id'];
        }

        // Kupon
        $discount = 0.0;
        $coupon = null;
        $couponError = null;
        $autoApplied = false;
        $customerId = SessionManager::get('customer_id');
        $customerId = $customerId ? (int) $customerId : null;

        if ($couponCode !== null && $couponCode !== '') {
            $check = CouponService::validate(
                $couponCode, $subtotal, $currency,
                $customerId,
                $productIds
            );
            if ($check['ok']) {
                $coupon = $check['coupon'];
                $discount = CouponService::calculateDiscount($coupon, $subtotal);
            } else {
                $couponError = $check['error'] ?? 'Kupon geçersiz.';
            }
        }

        // Kullanıcı kod girmediyse veya kod geçersizse — otomatik kupon devreye girer
        if ($coupon === null && $subtotal > 0) {
            $auto = CouponService::findBestAutoApply($subtotal, $customerId);
            if ($auto) {
                $coupon = $auto;
                $discount = CouponService::calculateDiscount($auto, $subtotal);
                $couponCode = (string) $auto['code'];
                $autoApplied = true;
            }
        }

        // Vergi
        $tax = TaxService::calculate($subtotal, $discount);

        return [
            'items'    => $items,
            'currency' => $currency,
            'subtotal' => $tax['subtotal'],
            'discount' => $tax['discount'],
            'taxable'  => $tax['taxable'],
            'tax'      => $tax['tax'],
            'tax_rate' => $tax['tax_rate'],
            'total'    => $tax['total'],
            'coupon'   => $coupon,
            'coupon_auto_applied' => $autoApplied,
            'coupon_code'  => $couponCode,
            'coupon_error' => $couponError,
            'formatted' => [
                'subtotal' => CurrencyService::format($tax['subtotal'], $currency),
                'discount' => CurrencyService::format($tax['discount'], $currency),
                'tax'      => CurrencyService::format($tax['tax'], $currency),
                'total'    => CurrencyService::format($tax['total'], $currency),
            ],
        ];
    }

    /** Ziyaretçi sepetini müşteri hesabına aktar (login sonrası). */
    public static function mergeGuestToCustomer(int $customerId): void
    {
        $sid = self::sessionId();
        Connection::query(
            "UPDATE cart_items SET customer_id = ?, session_id = NULL
             WHERE session_id = ? AND customer_id IS NULL",
            [$customerId, $sid]
        );
    }
}
