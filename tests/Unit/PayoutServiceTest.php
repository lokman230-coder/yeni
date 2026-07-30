<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Referral\Services\PayoutService;
use App\Modules\Referral\Services\ReferralService;
use App\Services\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PayoutServiceTest extends TestCase
{
    private int $customerId = 0;

    protected function setUp(): void
    {
        ReferralService::ensureDefaultSettings();
        $this->customerId = Connection::insert('customers', [
            'email'         => 'payout-' . uniqid() . '@ahost.web.tr',
            'password_hash' => PasswordHasher::hash('X'),
            'first_name'    => 'P', 'last_name' => 'T',
            'status'        => 'active',
            'balance'       => 500.00,
        ]);
    }

    protected function tearDown(): void
    {
        Connection::query("DELETE FROM payout_requests WHERE customer_id = ?", [$this->customerId]);
        Connection::query("DELETE FROM customers WHERE id = ?", [$this->customerId]);
    }

    public function test_valid_request_deducts_balance(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 200, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test User', 'bank_name' => 'Ziraat',
        ]);
        $this->assertTrue($r['ok']);
        $c = Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$this->customerId]);
        $this->assertEqualsWithDelta(300.0, (float) $c['balance'], 0.01);
    }

    public function test_below_minimum_rejected(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 10, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Minimum', $r['error']);
    }

    public function test_insufficient_balance_rejected(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 999999, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Yetersiz', $r['error']);
    }

    public function test_invalid_iban_rejected(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 200, 'iban' => 'ABC12',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('IBAN', $r['error']);
    }

    public function test_reject_refunds_balance(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 200, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        $this->assertTrue($r['ok']);
        $ok = PayoutService::reject($r['id'], 1, 'Test red');
        $this->assertTrue($ok);
        $c = Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$this->customerId]);
        $this->assertEqualsWithDelta(500.0, (float) $c['balance'], 0.01, 'Bakiye iade edilmeli');
    }

    public function test_cancel_by_customer_refunds_balance(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 200, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        PayoutService::cancel($r['id'], $this->customerId);
        $c = Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$this->customerId]);
        $this->assertEqualsWithDelta(500.0, (float) $c['balance'], 0.01);
    }

    public function test_approve_then_paid_workflow(): void
    {
        $r = PayoutService::request($this->customerId, [
            'amount' => 150, 'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test', 'bank_name' => 'Ziraat',
        ]);
        $this->assertTrue(PayoutService::approve($r['id'], 1));
        $this->assertTrue(PayoutService::markPaid($r['id'], 1, 'Havale referansı 12345'));
        $p = Connection::selectOne("SELECT status FROM payout_requests WHERE id = ?", [$r['id']]);
        $this->assertSame('paid', $p['status']);
    }
}
