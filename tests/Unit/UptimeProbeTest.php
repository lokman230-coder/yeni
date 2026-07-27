<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Health\Services\UptimeProbe;
use PHPUnit\Framework\TestCase;

final class UptimeProbeTest extends TestCase
{
    public function test_returns_expected_structure(): void
    {
        // Bilinen geçersiz URL — ağ hatası bekleniyor ama exception değil
        $r = UptimeProbe::check('http://this-domain-should-not-exist-xxxx.invalid', 3);
        $this->assertIsArray($r);
        $this->assertArrayHasKey('ok', $r);
        $this->assertArrayHasKey('http_code', $r);
        $this->assertArrayHasKey('response_time_ms', $r);
        $this->assertFalse($r['ok']);
    }

    public function test_check_many_returns_map(): void
    {
        $r = UptimeProbe::checkMany([
            'http://invalid-x.invalid',
            'http://invalid-y.invalid',
        ], 2);
        $this->assertIsArray($r);
        $this->assertCount(2, $r);
    }
}
