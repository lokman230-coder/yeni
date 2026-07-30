<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class EmailVerificationTest extends TestCase
{
    private int $customerId = 0;
    private string $email = '';

    protected function setUp(): void
    {
        $this->email = 'verify-' . uniqid() . '@ahost.web.tr';
        $this->customerId = Connection::insert('customers', [
            'email'         => $this->email,
            'password_hash' => PasswordHasher::hash('Test1234!'),
            'first_name'    => 'V', 'last_name' => 'T',
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Connection::query("DELETE FROM auth_tokens WHERE user_id = ? AND user_type='customer'", [$this->customerId]);
        Connection::query("DELETE FROM customers WHERE id = ?", [$this->customerId]);
    }

    public function test_new_customer_is_not_verified_by_default(): void
    {
        $this->assertFalse(EmailVerificationService::isVerified('customer', $this->customerId));
    }

    public function test_valid_token_verifies_email(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_EMAIL_VERIFY, $this->email, 60);
        $r = EmailVerificationService::verify($token);
        $this->assertTrue($r['ok']);
        $this->assertTrue(EmailVerificationService::isVerified('customer', $this->customerId));
    }

    public function test_invalid_token_returns_error(): void
    {
        $r = EmailVerificationService::verify('invalid-token');
        $this->assertFalse($r['ok']);
    }

    public function test_token_consumed_after_use(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_EMAIL_VERIFY, $this->email, 60);
        EmailVerificationService::verify($token);
        // İkinci kez
        $r = EmailVerificationService::verify($token);
        $this->assertFalse($r['ok']);
    }

    public function test_resend_creates_new_token_when_unverified(): void
    {
        $ok = EmailVerificationService::resend('customer', $this->customerId);
        $this->assertTrue($ok);
        $t = Connection::selectOne("SELECT * FROM auth_tokens WHERE user_id=? AND purpose='email_verify' ORDER BY id DESC LIMIT 1", [$this->customerId]);
        $this->assertNotNull($t);
    }

    public function test_resend_returns_false_when_already_verified(): void
    {
        Connection::update('customers', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$this->customerId]);
        $ok = EmailVerificationService::resend('customer', $this->customerId);
        $this->assertFalse($ok);
    }
}
