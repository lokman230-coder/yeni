<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Auth\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function test_short_password_rejected(): void
    {
        $r = PasswordPolicy::validate('Abc12');
        $this->assertFalse($r['ok']);
        $this->assertNotEmpty($r['errors']);
    }

    public function test_missing_uppercase_rejected(): void
    {
        $r = PasswordPolicy::validate('abcdefgh1');
        $this->assertFalse($r['ok']);
    }

    public function test_missing_lowercase_rejected(): void
    {
        $r = PasswordPolicy::validate('ABCDEFGH1');
        $this->assertFalse($r['ok']);
    }

    public function test_missing_digit_rejected(): void
    {
        $r = PasswordPolicy::validate('Abcdefghi');
        $this->assertFalse($r['ok']);
    }

    public function test_common_password_rejected(): void
    {
        $r = PasswordPolicy::validate('Password123');
        $this->assertFalse($r['ok']);
    }

    public function test_strong_password_accepted(): void
    {
        $r = PasswordPolicy::validate('MyStr0ngP@ss!');
        $this->assertTrue($r['ok']);
    }

    public function test_strength_score(): void
    {
        $this->assertGreaterThan(0, PasswordPolicy::strength('Test1234!'));
        $this->assertGreaterThan(70, PasswordPolicy::strength('MyReallyLongP@ssw0rd2026!'));
        $this->assertLessThan(50, PasswordPolicy::strength('abc'));
    }

    public function test_too_long_rejected(): void
    {
        $r = PasswordPolicy::validate(str_repeat('A1b', 50));
        $this->assertFalse($r['ok']);
    }
}
