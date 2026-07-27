<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Database\Connection;
use App\Services\Currency\CurrencyService;

/**
 * Ürün fiyatlandırma.
 *
 * Bir üründe birden fazla periyot fiyatı olabilir. Her fiyat kendi kaynak
 * para biriminde tutulur. Görüntülerken:
 *   1. Kaynak para birimi = hedef mi? → doğrudan kaynak fiyat
 *   2. Aksi halde CurrencyService::convert() ile hedefe çevrilir (marj dahil)
 */
final class PricingService
{
    /**
     * Ürün için tüm aktif fiyatları döner (hedef para birimine dönüştürülmüş).
     */
    public static function activePrices(int $productId, ?string $targetCurrency = null): array
    {
        $target = strtoupper($targetCurrency ?? CurrencyService::current());
        $rows = Connection::select(
            "SELECT * FROM product_prices
             WHERE product_id = ? AND is_active = 1
             ORDER BY sort_order ASC, id ASC",
            [$productId]
        );

        $out = [];
        foreach ($rows as $row) {
            $source   = strtoupper($row['source_currency']);
            $sourceAmount = (float) $row['source_price'];
            $converted = CurrencyService::convert($sourceAmount, $source, $target);
            $out[] = [
                'id'              => (int) $row['id'],
                'period'          => $row['period'],
                'period_label'    => self::periodLabel($row['period']),
                'source_currency' => $source,
                'source_price'    => $sourceAmount,
                'currency'        => $target,
                'price'           => round($converted, 2),
                'formatted'       => CurrencyService::format($converted, $target),
            ];
        }

        return $out;
    }

    /** Belirli bir periyot fiyatını hedef para biriminde döner. */
    public static function priceFor(int $productId, string $period, ?string $targetCurrency = null): ?array
    {
        $target = strtoupper($targetCurrency ?? CurrencyService::current());
        $prices = self::activePrices($productId, $target);
        foreach ($prices as $p) {
            if ($p['period'] === $period) return $p;
        }
        return null;
    }

    public static function periodLabel(string $period): string
    {
        return match ($period) {
            'onetime'       => 'Tek Seferlik',
            'monthly'       => 'Aylık',
            'quarterly'     => '3 Aylık',
            'semiannually'  => '6 Aylık',
            'annually'      => 'Yıllık',
            'biennially'    => '2 Yıllık',
            'triennially'   => '3 Yıllık',
            default         => ucfirst($period),
        };
    }

    public static function periodMonths(string $period): int
    {
        return match ($period) {
            'onetime'      => 0,
            'monthly'      => 1,
            'quarterly'    => 3,
            'semiannually' => 6,
            'annually'     => 12,
            'biennially'   => 24,
            'triennially'  => 36,
            default        => 1,
        };
    }

    public static function allPeriods(): array
    {
        return ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'];
    }
}
