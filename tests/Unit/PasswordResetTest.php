<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\PasswordHasher;
use App\Services\Auth\PasswordResetService;
use PHPUnit\Framework\TestCase;

final class PasswordResetTest extends TestCase
{
    private int $customerId = 0;
    private string $email = '';

    protected function setUp(): void
    {
        $this->email = 'reset-' . uniqid() . '@ahost.web.tr';
        $this->customerId = Connection::insert('customers', [
            'email'         => $this->email,
            'password_hash' => PasswordHasher::hash('OldPass1234!'),
            'first_name'    => 'Reset', 'last_name' => 'Test',
            'status'        => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            Connection::query("DELETE FROM auth_tokens WHERE user_id = ?", [$this->customerId]);
            Connection::query("DELETE FROM customers WHERE id = ?", [$this->customerId]);
        } catch (\Throwable) {}
    }

    public function test_request_creates_token_for_existing_user(): void
    {
        $r = PasswordResetService::request($this->email, 'customer');
        $this->assertTrue($r['ok']);
        $token = Connection::selectOne("SELECT * FROM auth_tokens WHERE user_id = ? AND purpose='password_reset'", [$this->customerId]);
        $this->assertNotNull($token);
        $this->assertNull($token['used_at']);
    }

    public function test_request_returns_ok_even_for_nonexistent_email_to_prevent_enumeration(): void
    {
        $r = PasswordResetService::request('does-not-exist-' . uniqid() . '@x.z', 'customer');
        $this->assertTrue($r['ok'], 'Enumeration önleme: hata sızdırılmamalı');
    }

    public function test_valid_token_reset_works(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        $r = PasswordResetService::reset($token, 'NewPass1234!');
        $this->assertTrue($r['ok']);

        $c = Connection::selectOne("SELECT password_hash FROM customers WHERE id = ?", [$this->customerId]);
        $this->assertTrue(password_verify('NewPass1234!', $c['password_hash']));
        $this->assertFalse(password_verify('OldPass1234!', $c['password_hash']));
    }

    public function test_token_becomes_invalid_after_use(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        PasswordResetService::reset($token, 'NewPass1234!');

        // İkinci kez aynı token
        $r = PasswordResetService::reset($token, 'AnotherPass1234!');
        $this->assertFalse($r['ok']);
    }

    public function test_short_password_rejected(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        $r = PasswordResetService::reset($token, '123');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('8 karakter', $r['error']);
    }

    public function test_invalid_token_rejected(): void
    {
        $r = PasswordResetService::reset('invalid-token-xxx', 'NewPass1234!');
        $this->assertFalse($r['ok']);
    }

    public function test_expired_token_rejected(): void
    {
        $token = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        // Manuel expire yap
        Connection::query("UPDATE auth_tokens SET expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE token = ?", [$token]);
        $r = PasswordResetService::reset($token, 'NewPass1234!');
        $this->assertFalse($r['ok']);
    }

    public function test_new_request_invalidates_previous_token(): void
    {
        $t1 = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        $t2 = AuthTokenService::issue('customer', $this->customerId, AuthTokenService::PURPOSE_PASSWORD_RESET, $this->email, 60);
        $this->assertNotSame($t1, $t2);

        // İlki artık geçersiz olmalı
        $this->assertNull(AuthTokenService::verify($t1, AuthTokenService::PURPOSE_PASSWORD_RESET));
        $this->assertNotNull(AuthTokenService::verify($t2, AuthTokenService::PURPOSE_PASSWORD_RESET));
    }
}
