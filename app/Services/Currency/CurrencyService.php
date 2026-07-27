<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Core\Database\Connection;
use App\Core\SessionManager;

/**
 * Kur yönetimi + para birimi dönüşümü.
 *
 * TRY base olarak kabul edilir. Diğer para birimlerinin `rate` değeri
 * "1 birim yabancı para = kaç TRY" olarak tutulur.
 * Ör: USD rate = 32.5 → 1 USD = 32.5 TRY
 *
 * Kar marjı (margin_percent): dönüşümde eklenen kar oranı.
 * Ör: USD margin=2 → gösterilen kur = 32.5 * 1.02 = 33.15
 */
final class CurrencyService
{
    private static array $ratesCache = [];
    private static array $marginCache = [];

    /** Kaynak → hedef dönüşüm (marj dahil). */
    public static function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);
        if ($from === $to) return $amount;

        // Kaynak → TRY (base)
        $inTry = $from === 'TRY' ? $amount : $amount * self::finalRate($from);

        // TRY → hedef
        if ($to === 'TRY') return $inTry;
        $rate = self::finalRate($to);
        return $rate > 0 ? $inTry / $rate : 0.0;
    }

    /** Bir para birimi için son kur (raw + kar marjı). */
    public static function finalRate(string $currency): float
    {
        $currency = strtoupper($currency);
        if (isset(self::$ratesCache[$currency])) {
            return self::$ratesCache[$currency];
        }

        if ($currency === 'TRY') {
            return self::$ratesCache['TRY'] = 1.0;
        }

        try {
            $row = Connection::selectOne(
                "SELECT rate, margin_percent FROM currency_rates WHERE currency = ?",
                [$currency]
            );
        } catch (\Throwable) {
            $row = null;
        }

        if (!$row) return self::$ratesCache[$currency] = 0.0;

        $raw = (float) $row['rate'];
        $margin = (float) ($row['margin_percent'] ?? 0);
        return self::$ratesCache[$currency] = $raw * (1 + $margin / 100);
    }

    /** Şu anki aktif kur bilgileri (admin panel için). */
    public static function all(): array
    {
        try {
            return Connection::select("SELECT * FROM currency_rates ORDER BY currency ASC");
        } catch (\Throwable) {
            return [];
        }
    }

    /** Session/cookie'deki müşterinin seçtiği para birimi. */
    public static function current(): string
    {
        return strtoupper((string) SessionManager::get('currency', 'TRY'));
    }

    /** Formatlanmış gösterim (₺, $, €, £). */
    public static function format(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? self::current());
        $symbol = self::symbol($currency);
        $formatted = number_format($amount, 2, ',', '.');

        return match ($currency) {
            'TRY'   => $formatted . ' ' . $symbol,
            default => $symbol . $formatted,
        };
    }

    public static function symbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => strtoupper($currency),
        };
    }

    /**
     * Müşteriye gösterilecek aktif para birimleri (DB'den).
     * TRY her zaman ilk sırada olur ve pasif edilse bile listeye eklenir.
     */
    public static function supported(): array
    {
        try {
            $rows = Connection::select(
                "SELECT currency FROM currency_rates
                 WHERE is_active = 1
                 ORDER BY (currency='TRY') DESC, currency ASC"
            );
            $codes = array_map(fn($r) => strtoupper($r['currency']), $rows);
            if (!in_array('TRY', $codes, true)) array_unshift($codes, 'TRY');
            return $codes;
        } catch (\Throwable) {
            return ['TRY', 'USD', 'EUR', 'GBP'];
        }
    }
}
