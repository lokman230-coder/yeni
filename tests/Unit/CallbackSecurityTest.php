<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Payment\Services\CallbackSecurity;
use PHPUnit\Framework\TestCase;

final class CallbackSecurityTest extends TestCase
{
    public function test_no_whitelist_allows_all_ips(): void
    {
        $this->assertTrue(CallbackSecurity::isAllowedIp('iyzico', '1.2.3.4'));
        $this->assertTrue(CallbackSecurity::isAllowedIp('papara', '9.9.9.9'));
    }

    public function test_paytr_whitelist_accepts_known_ip(): void
    {
        $this->assertTrue(CallbackSecurity::isAllowedIp('paytr', '193.192.59.100'));
    }

    public function test_paytr_whitelist_rejects_unknown_ip(): void
    {
        $this->assertFalse(CallbackSecurity::isAllowedIp('paytr', '8.8.8.8'));
    }

    public function test_mark_processed_returns_true_for_new_id(): void
    {
        $uniqueId = 'TEST-' . uniqid() . '-' . random_int(1000, 9999);
        $this->assertTrue(CallbackSecurity::markProcessed('shopier', $uniqueId));
    }

    public function test_audit_does_not_throw(): void
    {
        // Sadece exception fırlatmıyor mu kontrol
        CallbackSecurity::audit('paytr', 'CB-123', true, ['status' => 'success'], '1.2.3.4');
        $this->assertTrue(true);
    }
}
