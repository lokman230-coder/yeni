<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Core\Database\Connection;

/**
 * Vergi hesaplama.
 *
 * Kural (şartname madde 14):
 *   - Sepette ANLIK hesaplanır (ödeme adımında değil)
 *   - İndirim yoksa: sadece vergi
 *   - İndirim varsa: önce indirim → sonra vergi
 *
 * exclusive (default): fiyata KDV eklenir → 100 → +20 → 120
 * inclusive: fiyat KDV'yi içerir → 120 → net 100 + KDV 20
 */
final class TaxService
{
    /** Aktif varsayılan vergi kuralı. */
    public static function defaultRule(): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT * FROM tax_rules WHERE is_active = 1 ORDER BY id ASC LIMIT 1"
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sepet toplamı için vergi hesabı.
     *
     * @param float $subtotal Ürünlerin ara toplamı (indirim öncesi)
     * @param float $discount İndirim tutarı (kupon vb.)
     * @return array{subtotal:float, discount:float, taxable:float, tax:float, total:float, tax_rate:float}
     */
    public static function calculate(float $subtotal, float $discount = 0.0, ?array $rule = null): array
    {
        $rule ??= self::defaultRule();
        $rate = (float) ($rule['rate'] ?? 0);
        $applyType = $rule['apply_type'] ?? 'exclusive';

        $taxable = max(0, $subtotal - $discount);

        if ($applyType === 'inclusive') {
            // Toplam vergiyi içeriyor: net = taxable / (1 + rate/100)
            $net = $rate > 0 ? $taxable / (1 + $rate / 100) : $taxable;
            $tax = $taxable - $net;
            $total = $taxable;
        } else {
            // exclusive: vergi üste eklenir
            $tax = $taxable * ($rate / 100);
            $total = $taxable + $tax;
        }

        return [
            'subtotal' => round($subtotal, 4),
            'discount' => round($discount, 4),
            'taxable'  => round($taxable, 4),
            'tax'      => round($tax, 4),
            'total'    => round($total, 4),
            'tax_rate' => $rate,
        ];
    }
}
