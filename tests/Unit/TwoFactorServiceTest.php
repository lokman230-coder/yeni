<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Auth\PasswordHasher;
use App\Services\Auth\TwoFactorService;
use PHPUnit\Framework\TestCase;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorServiceTest extends TestCase
{
    private int $customerId = 0;

    protected function setUp(): void
    {
        $this->customerId = Connection::insert('customers', [
            'email'         => '2fa-test-' . uniqid() . '@ahost.web.tr',
            'password_hash' => PasswordHasher::hash('Test1234!'),
            'first_name'    => '2FA', 'last_name' => 'Test',
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        try { Connection::query("DELETE FROM customers WHERE id = ?", [$this->customerId]); } catch (\Throwable) {}
    }

    public function test_generate_secret_returns_32_char_base32(): void
    {
        $s = TwoFactorService::generateSecret();
        $this->assertSame(32, strlen($s));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $s);
    }

    public function test_qr_svg_is_valid_svg_with_uri(): void
    {
        $svg = TwoFactorService::qrCodeSvg('Ahost Bilişim', 'test@ahost.web.tr', TwoFactorService::generateSecret());
        $this->assertStringContainsString('<svg', $svg);
        $this->assertGreaterThan(500, strlen($svg));
    }

    public function test_setup_and_verify_full_flow(): void
    {
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('customer', $this->customerId, $secret);

        // Not confirmed yet
        $this->assertFalse(TwoFactorService::isEnabled('customer', $this->customerId));

        // Confirm ile aktif et
        $currentOtp = (new Google2FA())->getCurrentOtp($secret);
        $recovery = TwoFactorService::confirm('customer', $this->customerId, $currentOtp);
        $this->assertIsArray($recovery);
        $this->assertCount(10, $recovery);
        $this->assertTrue(TwoFactorService::isEnabled('customer', $this->customerId));

        // Verify current OTP
        $this->assertTrue(TwoFactorService::verify('customer', $this->customerId, $currentOtp));

        // Verify with recovery code
        $this->assertTrue(TwoFactorService::verify('customer', $this->customerId, $recovery[0]));

        // Same recovery code should NOT work again (tek kullanımlık)
        $this->assertFalse(TwoFactorService::verify('customer', $this->customerId, $recovery[0]));

        // Diğer recovery code çalışmalı
        $this->assertTrue(TwoFactorService::verify('customer', $this->customerId, $recovery[1]));
    }

    public function test_wrong_confirmation_code_rejected(): void
    {
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('customer', $this->customerId, $secret);
        $r = TwoFactorService::confirm('customer', $this->customerId, '000000');
        $this->assertNull($r);
        $this->assertFalse(TwoFactorService::isEnabled('customer', $this->customerId));
    }

    public function test_disable_clears_secret_and_codes(): void
    {
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('customer', $this->customerId, $secret);
        $otp = (new Google2FA())->getCurrentOtp($secret);
        TwoFactorService::confirm('customer', $this->customerId, $otp);
        $this->assertTrue(TwoFactorService::isEnabled('customer', $this->customerId));

        TwoFactorService::disable('customer', $this->customerId);
        $this->assertFalse(TwoFactorService::isEnabled('customer', $this->customerId));
        $this->assertNull(TwoFactorService::getSecret('customer', $this->customerId));
        $this->assertSame([], TwoFactorService::getRecoveryCodes('customer', $this->customerId));
    }

    public function test_recovery_codes_format(): void
    {
        $secret = TwoFactorService::generateSecret();
        TwoFactorService::saveSecret('customer', $this->customerId, $secret);
        $otp = (new Google2FA())->getCurrentOtp($secret);
        $recovery = TwoFactorService::confirm('customer', $this->customerId, $otp);
        foreach ($recovery as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code);
        }
    }
}
