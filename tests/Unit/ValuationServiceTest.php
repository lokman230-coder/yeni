<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Domain\Services\ValuationService;
use PHPUnit\Framework\TestCase;

final class ValuationServiceTest extends TestCase
{
    public function test_short_com_is_high_value(): void
    {
        $r = ValuationService::evaluate('go.com');
        $this->assertSame('go', $r['sld']);
        $this->assertSame('com', $r['tld']);
        $this->assertGreaterThan(80, $r['scores']['tld']);
        $this->assertGreaterThan(80, $r['scores']['length']);
    }

    public function test_long_xyz_is_low_value(): void
    {
        $r = ValuationService::evaluate('supercalifragilisticexpialidocious.xyz');
        $this->assertLessThan(50, $r['scores']['overall']);
    }

    public function test_double_tld_com_tr(): void
    {
        $r = ValuationService::evaluate('example.com.tr');
        $this->assertSame('example', $r['sld']);
        $this->assertSame('com.tr', $r['tld']);
    }

    public function test_hyphen_penalizes_brand_score(): void
    {
        $normal = ValuationService::evaluate('example.com');
        $withHyphen = ValuationService::evaluate('ex-am-ple.com');
        $this->assertGreaterThan($withHyphen['scores']['brand'], $normal['scores']['brand']);
    }

    public function test_risks_detected(): void
    {
        $r = ValuationService::evaluate('super-long-domain-with-123.xyz');
        $this->assertNotEmpty($r['risks']);
        $risks = implode(' ', $r['risks']);
        $this->assertStringContainsString('Tire', $risks);
        $this->assertStringContainsString('Rakam', $risks);
    }
}
