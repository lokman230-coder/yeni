<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Auth\AuthService;
use PHPUnit\Framework\TestCase;

final class CustomerRegistrationTest extends TestCase
{
    private array $createdIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdIds as $id) {
            try {
                Connection::query("DELETE FROM referrals WHERE referred_customer_id = ?", [$id]);
                Connection::query("DELETE FROM customers WHERE id = ?", [$id]);
            } catch (\Throwable) {}
        }
    }

    public function test_valid_registration_creates_customer(): void
    {
        $email = 'test-reg-' . uniqid() . '@ahost.web.tr';
        $r = AuthService::registerCustomer([
            'email' => $email, 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => 'Ali', 'last_name' => 'Veli', 'kvkk' => 1,
        ]);
        $this->assertTrue($r['ok']);
        $this->assertIsInt($r['customer_id']);
        $this->createdIds[] = $r['customer_id'];

        $row = Connection::selectOne("SELECT * FROM customers WHERE id = ?", [$r['customer_id']]);
        $this->assertSame($email, $row['email']);
        $this->assertSame('active', $row['status']);
        $this->assertTrue(password_verify('Test1234!', $row['password_hash']));
    }

    public function test_invalid_email_returns_error(): void
    {
        $r = AuthService::registerCustomer([
            'email' => 'not-an-email', 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => 'A', 'last_name' => 'B', 'kvkk' => 1,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('email', $r['errors']);
    }

    public function test_short_password_rejected(): void
    {
        $r = AuthService::registerCustomer([
            'email' => 'x@y.z', 'password' => '123', 'password_confirm' => '123',
            'first_name' => 'A', 'last_name' => 'B', 'kvkk' => 1,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('password', $r['errors']);
    }

    public function test_password_mismatch_rejected(): void
    {
        $r = AuthService::registerCustomer([
            'email' => 'x@y.z', 'password' => 'Test1234!', 'password_confirm' => 'Different1!',
            'first_name' => 'A', 'last_name' => 'B', 'kvkk' => 1,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('password_confirm', $r['errors']);
    }

    public function test_missing_kvkk_rejected(): void
    {
        $r = AuthService::registerCustomer([
            'email' => 'x@y.z', 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => 'A', 'last_name' => 'B',
        ]);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('kvkk', $r['errors']);
    }

    public function test_duplicate_email_rejected(): void
    {
        $email = 'dup-' . uniqid() . '@ahost.web.tr';
        $r1 = AuthService::registerCustomer([
            'email' => $email, 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => 'A', 'last_name' => 'B', 'kvkk' => 1,
        ]);
        $this->createdIds[] = $r1['customer_id'];

        $r2 = AuthService::registerCustomer([
            'email' => $email, 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => 'C', 'last_name' => 'D', 'kvkk' => 1,
        ]);
        $this->assertFalse($r2['ok']);
        $this->assertArrayHasKey('email', $r2['errors']);
    }

    public function test_missing_names_rejected(): void
    {
        $r = AuthService::registerCustomer([
            'email' => 'test@y.z', 'password' => 'Test1234!', 'password_confirm' => 'Test1234!',
            'first_name' => '', 'last_name' => '', 'kvkk' => 1,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('first_name', $r['errors']);
        $this->assertArrayHasKey('last_name', $r['errors']);
    }
}
