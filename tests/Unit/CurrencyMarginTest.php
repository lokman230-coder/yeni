<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Currency\CurrencyService;
use PHPUnit\Framework\TestCase;

/**
 * Kar marjı ile kur dönüşümü doğrulaması.
 *
 * Kritik davranış: currency_rates.margin_percent değeri, hem finalRate()
 * hem de convert() sonucuna yansımalıdır. Marj değişirse fiyat da anında değişir.
 */
final class CurrencyMarginTest extends TestCase
{
    private float $originalMargin = 0.0;
    private float $originalRate = 0.0;

    protected function setUp(): void
    {
        $row = Connection::selectOne("SELECT rate, margin_percent FROM currency_rates WHERE currency = ?", ['USD']);
        if (!$row) {
            $this->markTestSkipped('USD kur kaydı yok');
        }
        $this->originalMargin = (float) $row['margin_percent'];
        $this->originalRate = (float) $row['rate'];
        // Sabit değerlerle test için
        Connection::update('currency_rates',
            ['rate' => 40.0, 'margin_percent' => 0.0],
            'currency = ?', ['USD']
        );
        // CurrencyService cache sıfırla (static property)
        $ref = new \ReflectionClass(CurrencyService::class);
        foreach (['ratesCache', 'marginCache'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setValue(null, []);
            }
        }
    }

    protected function tearDown(): void
    {
        Connection::update('currency_rates',
            ['rate' => $this->originalRate, 'margin_percent' => $this->originalMargin],
            'currency = ?', ['USD']
        );
    }

    public function test_zero_margin_gives_raw_rate(): void
    {
        $this->resetCache();
        $this->assertEqualsWithDelta(40.0, CurrencyService::finalRate('USD'), 0.0001);
    }

    public function test_two_percent_margin_adds_2_percent(): void
    {
        Connection::update('currency_rates', ['margin_percent' => 2.0], 'currency = ?', ['USD']);
        $this->resetCache();
        $this->assertEqualsWithDelta(40.8, CurrencyService::finalRate('USD'), 0.0001);
    }

    public function test_convert_usd_to_try_uses_margin(): void
    {
        Connection::update('currency_rates', ['margin_percent' => 5.0], 'currency = ?', ['USD']);
        $this->resetCache();
        // 100 USD × 40 × 1.05 = 4200 TRY
        $this->assertEqualsWithDelta(4200.0, CurrencyService::convert(100, 'USD', 'TRY'), 0.01);
    }

    public function test_convert_try_to_usd_reverse_with_margin(): void
    {
        Connection::update('currency_rates', ['margin_percent' => 5.0], 'currency = ?', ['USD']);
        $this->resetCache();
        // 4200 TRY / (40 × 1.05) = 100 USD
        $this->assertEqualsWithDelta(100.0, CurrencyService::convert(4200, 'TRY', 'USD'), 0.01);
    }

    public function test_try_to_try_is_identity(): void
    {
        $this->assertSame(123.45, CurrencyService::convert(123.45, 'TRY', 'TRY'));
    }

    public function test_supported_includes_try_first(): void
    {
        $s = CurrencyService::supported();
        $this->assertSame('TRY', $s[0]);
    }

    private function resetCache(): void
    {
        $ref = new \ReflectionClass(CurrencyService::class);
        foreach (['ratesCache', 'marginCache'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setValue(null, []);
            }
        }
    }
}
