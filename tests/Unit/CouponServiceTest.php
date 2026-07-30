<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Coupon\CouponService;
use PHPUnit\Framework\TestCase;

final class CouponServiceTest extends TestCase
{
    public function test_empty_code_returns_error(): void
    {
        $r = CouponService::validate('', 100, 'TRY');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('girilmedi', $r['error']);
    }

    public function test_percent_discount_calculation(): void
    {
        $coupon = ['type' => 'percent', 'value' => 15];
        $this->assertEquals(15.0, CouponService::calculateDiscount($coupon, 100));
        $this->assertEquals(45.0, CouponService::calculateDiscount($coupon, 300));
    }

    public function test_fixed_discount_calculation(): void
    {
        $coupon = ['type' => 'fixed', 'value' => 25];
        $this->assertEquals(25.0, CouponService::calculateDiscount($coupon, 100));
    }

    public function test_discount_never_exceeds_subtotal(): void
    {
        $coupon = ['type' => 'fixed', 'value' => 500];
        $this->assertEquals(100.0, CouponService::calculateDiscount($coupon, 100));
    }
}
