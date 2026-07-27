<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Currency\CurrencyService;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_symbol_maps(): void
    {
        $this->assertSame('₺', CurrencyService::symbol('TRY'));
        $this->assertSame('$', CurrencyService::symbol('USD'));
        $this->assertSame('€', CurrencyService::symbol('EUR'));
        $this->assertSame('£', CurrencyService::symbol('GBP'));
    }

    public function test_format_try_suffix(): void
    {
        $this->assertStringContainsString('₺', CurrencyService::format(199.90, 'TRY'));
        $this->assertStringContainsString('199,90', CurrencyService::format(199.90, 'TRY'));
    }

    public function test_supported_currencies(): void
    {
        $s = CurrencyService::supported();
        $this->assertContains('TRY', $s);
        $this->assertContains('USD', $s);
        $this->assertContains('EUR', $s);
        $this->assertContains('GBP', $s);
    }

    public function test_same_currency_no_conversion(): void
    {
        // DB'ye ihtiyaç yok: aynı para birimi doğrudan döner
        $this->assertSame(100.0, CurrencyService::convert(100, 'USD', 'USD'));
        $this->assertSame(50.5, CurrencyService::convert(50.5, 'TRY', 'TRY'));
    }
}
