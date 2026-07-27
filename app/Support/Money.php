<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Currency\CurrencyService;

/**
 * Money helper — sık kullanılan format ve dönüşüm işlemleri için kısayol.
 */
final class Money
{
    public static function display(float $amount, ?string $currency = null): string
    {
        return CurrencyService::format($amount, $currency);
    }

    public static function convert(float $amount, string $from, string $to): float
    {
        return CurrencyService::convert($amount, $from, $to);
    }

    public static function inCurrent(float $amount, string $from): float
    {
        return CurrencyService::convert($amount, $from, CurrencyService::current());
    }

    /** Kaynak fiyatı geçerli müşteri para birimine dönüştürüp formatlı döndür. */
    public static function displayIn(float $amount, string $from, ?string $to = null): string
    {
        $to ??= CurrencyService::current();
        $converted = CurrencyService::convert($amount, $from, $to);
        return CurrencyService::format($converted, $to);
    }
}
