<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Auth\OtpService;
use PHPUnit\Framework\TestCase;

final class OtpServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Test tablosunu temizle
        try {
            Connection::query("DELETE FROM otp_codes");
        } catch (\Throwable) {
            // tablo yoksa geç
        }
    }

    public function test_normalize_phone_variants(): void
    {
        // Log driver ile issue (SMS gerçekten yollamaz)
        Connection::query(
            "INSERT INTO settings (`key`, value, type, `group`) VALUES ('sms.driver', 'log', 'string', 'sms')
             ON DUPLICATE KEY UPDATE value='log'"
        );

        $r = OtpService::issue('05551234567', 'login', 'sms');
        $this->assertTrue($r['ok']);

        // Kod DB'de olmalı, normalize edilmiş (905551234567)
        $row = Connection::selectOne("SELECT * FROM otp_codes WHERE identity = ?", ['905551234567']);
        $this->assertNotNull($row);
        $this->assertSame('login', $row['purpose']);
    }

    public function test_rate_limit_blocks_second_send(): void
    {
        Connection::query(
            "INSERT INTO settings (`key`, value, type, `group`) VALUES ('sms.driver', 'log', 'string', 'sms')
             ON DUPLICATE KEY UPDATE value='log'"
        );

        $r1 = OtpService::issue('05559998877', 'login', 'sms');
        $this->assertTrue($r1['ok']);

        $r2 = OtpService::issue('05559998877', 'login', 'sms');
        $this->assertFalse($r2['ok']);
        $this->assertStringContainsString('bekle', $r2['error']);
    }

    public function test_verify_returns_false_for_unknown_code(): void
    {
        $ok = OtpService::verify('05551112233', '000000', 'login');
        $this->assertFalse($ok);
    }

    public function test_verify_marks_code_used(): void
    {
        // Manuel bir OTP oluştur
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        Connection::insert('otp_codes', [
            'channel'    => 'sms',
            'purpose'    => 'login',
            'identity'   => '905551119999',
            'code_hash'  => $hash,
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + 300),
        ]);

        $ok = OtpService::verify('05551119999', '123456', 'login');
        $this->assertTrue($ok);

        // used_at set edilmiş olmalı
        $row = Connection::selectOne("SELECT used_at FROM otp_codes WHERE identity = ?", ['905551119999']);
        $this->assertNotEmpty($row['used_at']);

        // İkinci kez aynı kodla verify: false
        $ok2 = OtpService::verify('05551119999', '123456', 'login');
        $this->assertFalse($ok2);
    }

    public function test_verify_wrong_code_increments_attempts(): void
    {
        $hash = password_hash('654321', PASSWORD_DEFAULT);
        Connection::insert('otp_codes', [
            'channel'    => 'sms',
            'purpose'    => 'login',
            'identity'   => '905550001122',
            'code_hash'  => $hash,
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + 300),
        ]);

        $ok = OtpService::verify('05550001122', '111111', 'login');
        $this->assertFalse($ok);

        $row = Connection::selectOne("SELECT attempts FROM otp_codes WHERE identity = ?", ['905550001122']);
        $this->assertSame(1, (int) $row['attempts']);
    }
}
