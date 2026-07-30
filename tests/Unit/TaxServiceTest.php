<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Tax\TaxService;
use PHPUnit\Framework\TestCase;

final class TaxServiceTest extends TestCase
{
    private array $rule = ['rate' => 20, 'apply_type' => 'exclusive'];

    public function test_exclusive_tax_added_on_top(): void
    {
        $r = TaxService::calculate(100, 0, $this->rule);
        $this->assertEquals(100, $r['subtotal']);
        $this->assertEquals(0, $r['discount']);
        $this->assertEquals(20, $r['tax']);
        $this->assertEquals(120, $r['total']);
    }

    public function test_discount_applied_before_tax(): void
    {
        $r = TaxService::calculate(100, 10, $this->rule);
        $this->assertEquals(10, $r['discount']);
        $this->assertEquals(90, $r['taxable']);
        $this->assertEquals(18, $r['tax']);   // 90 * 0.20
        $this->assertEquals(108, $r['total']);
    }

    public function test_zero_subtotal(): void
    {
        $r = TaxService::calculate(0, 0, $this->rule);
        $this->assertEquals(0, $r['total']);
    }

    public function test_inclusive_tax_extracted(): void
    {
        $rule = ['rate' => 20, 'apply_type' => 'inclusive'];
        $r = TaxService::calculate(120, 0, $rule);
        $this->assertEquals(120, $r['total']);
        $this->assertEquals(20, $r['tax']);
    }

    public function test_discount_greater_than_subtotal_becomes_zero(): void
    {
        $r = TaxService::calculate(50, 100, $this->rule);
        $this->assertEquals(0, $r['taxable']);
        $this->assertEquals(0, $r['tax']);
        $this->assertEquals(0, $r['total']);
    }
}
