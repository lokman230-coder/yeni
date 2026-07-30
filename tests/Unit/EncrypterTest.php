<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Encrypter;
use PHPUnit\Framework\TestCase;

final class EncrypterTest extends TestCase
{
    public function test_encrypt_and_decrypt_roundtrip(): void
    {
        $plain = 'my-secret-value-123!@#';
        $cipher = Encrypter::encrypt($plain);
        $this->assertNotEmpty($cipher);
        $this->assertNotSame($plain, $cipher);
        $this->assertSame($plain, Encrypter::decrypt($cipher));
    }

    public function test_encrypts_differently_each_time(): void
    {
        $plain = 'same-input';
        $a = Encrypter::encrypt($plain);
        $b = Encrypter::encrypt($plain);
        $this->assertNotSame($a, $b, 'Her şifreleme random IV ile olmalı.');
        $this->assertSame($plain, Encrypter::decrypt($a));
        $this->assertSame($plain, Encrypter::decrypt($b));
    }

    public function test_mask_hides_middle(): void
    {
        $masked = Encrypter::mask('sk_live_abc123def456ghi789');
        $this->assertStringContainsString('***', $masked);
        $this->assertStringStartsWith('sk_l', $masked);
    }

    public function test_unicode_roundtrip(): void
    {
        $plain = 'Türkçe ışığında değer: ğüşiöç!';
        $this->assertSame($plain, Encrypter::decrypt(Encrypter::encrypt($plain)));
    }
}
