<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Referral\Services\ReferralService;
use App\Services\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

/**
 * ReferralService uçtan uca entegrasyon testi.
 * setUp/tearDown ile geçici müşteri + kod oluşturur, sonra siler.
 */
final class ReferralServiceTest extends TestCase
{
    private int $referrerId = 0;
    private int $referredId = 0;

    protected function setUp(): void
    {
        ReferralService::ensureDefaultSettings();
        // Referrer (yönlendiren) müşteri
        $this->referrerId = Connection::insert('customers', [
            'email'         => 'ref-referrer-' . uniqid() . '@ahost.web.tr',
            'password_hash' => PasswordHasher::hash('Test1234!'),
            'first_name'    => 'Referrer', 'last_name' => 'Test',
            'status'        => 'active',
        ]);
        // Referred (yönlendirilen) müşteri
        $this->referredId = Connection::insert('customers', [
            'email'         => 'ref-referred-' . uniqid() . '@ahost.web.tr',
            'password_hash' => PasswordHasher::hash('Test1234!'),
            'first_name'    => 'Referred', 'last_name' => 'Test',
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            Connection::query("DELETE FROM referral_commissions WHERE referrer_customer_id IN (?, ?)", [$this->referrerId, $this->referredId]);
            Connection::query("DELETE FROM referrals WHERE referrer_customer_id IN (?, ?) OR referred_customer_id IN (?, ?)", [$this->referrerId, $this->referredId, $this->referrerId, $this->referredId]);
            Connection::query("DELETE FROM referral_visits WHERE referral_code_id IN (SELECT id FROM referral_codes WHERE customer_id IN (?, ?))", [$this->referrerId, $this->referredId]);
            Connection::query("DELETE FROM referral_codes WHERE customer_id IN (?, ?)", [$this->referrerId, $this->referredId]);
            Connection::query("DELETE FROM customers WHERE id IN (?, ?)", [$this->referrerId, $this->referredId]);
        } catch (\Throwable) {}
    }

    public function test_code_generated_for_new_customer(): void
    {
        $code = ReferralService::codeForCustomer($this->referrerId);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $code);
    }

    public function test_same_customer_returns_same_code(): void
    {
        $code1 = ReferralService::codeForCustomer($this->referrerId);
        $code2 = ReferralService::codeForCustomer($this->referrerId);
        $this->assertSame($code1, $code2);
    }

    public function test_visit_increments_counter(): void
    {
        $code = ReferralService::codeForCustomer($this->referrerId);
        ReferralService::captureVisit($code, ['ip' => '1.2.3.4']);
        $row = Connection::selectOne("SELECT total_visits FROM referral_codes WHERE code = ?", [$code]);
        $this->assertSame(1, (int) $row['total_visits']);
    }

    public function test_invalid_code_visit_returns_false(): void
    {
        $ok = ReferralService::captureVisit('INVALIDCODE');
        $this->assertFalse($ok);
    }

    public function test_share_url_contains_code(): void
    {
        $url = ReferralService::shareUrl('ABC123');
        $this->assertStringContainsString('ref=ABC123', $url);
    }

    public function test_self_referral_not_attached(): void
    {
        $code = ReferralService::codeForCustomer($this->referrerId);
        ReferralService::captureVisit($code);
        // Cookie zaten $_COOKIE'de → attachOnSignup çağrılıyor
        $r = ReferralService::attachOnSignup($this->referrerId);
        $this->assertNull($r, 'Kendi kendine referans olmamalı');
    }

    public function test_full_flow_visit_signup_order_commission(): void
    {
        $code = ReferralService::codeForCustomer($this->referrerId);
        ReferralService::captureVisit($code);

        $refId = ReferralService::attachOnSignup($this->referredId);
        $this->assertNotNull($refId);
        $this->assertGreaterThan(0, $refId);

        $order = ['id' => 999999, 'customer_id' => $this->referredId, 'total' => 1000.0, 'currency' => 'TRY'];
        $commId = ReferralService::onOrderPaid($order);
        $this->assertNotNull($commId);

        $comm = Connection::selectOne("SELECT * FROM referral_commissions WHERE id = ?", [$commId]);
        // Default settings: %10
        $this->assertEqualsWithDelta(100.0, (float) $comm['commission_amount'], 0.01);
        $this->assertSame('pending', $comm['status']);

        // Onayla
        $balanceBefore = (float) (Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$this->referrerId])['balance'] ?? 0);
        $ok = ReferralService::approveCommission($commId);
        $this->assertTrue($ok);
        $balanceAfter = (float) (Connection::selectOne("SELECT balance FROM customers WHERE id = ?", [$this->referrerId])['balance'] ?? 0);
        $this->assertEqualsWithDelta($balanceBefore + 100.0, $balanceAfter, 0.01);
    }

    public function test_stats_returns_zero_for_new_customer(): void
    {
        $stats = ReferralService::statsFor($this->referrerId);
        $this->assertSame(0.0, $stats['pending_amount']);
        $this->assertSame(0.0, $stats['approved_amount']);
        $this->assertIsArray($stats['recent_commissions']);
    }

    public function test_reject_commission(): void
    {
        $code = ReferralService::codeForCustomer($this->referrerId);
        ReferralService::captureVisit($code);
        ReferralService::attachOnSignup($this->referredId);
        $order = ['id' => 999999, 'customer_id' => $this->referredId, 'total' => 500.0, 'currency' => 'TRY'];
        $commId = ReferralService::onOrderPaid($order);

        $ok = ReferralService::rejectCommission($commId, 'Şüpheli aktivite');
        $this->assertTrue($ok);
        $comm = Connection::selectOne("SELECT * FROM referral_commissions WHERE id = ?", [$commId]);
        $this->assertSame('rejected', $comm['status']);
        $this->assertStringContainsString('Şüpheli', $comm['note']);
    }
}
