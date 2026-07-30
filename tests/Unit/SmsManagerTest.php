<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Sms\Drivers\LogDriver;
use App\Services\Sms\SmsManager;
use PHPUnit\Framework\TestCase;

final class SmsManagerTest extends TestCase
{
    public function test_default_driver_list_contains_expected_drivers(): void
    {
        $drivers = SmsManager::drivers();
        $this->assertContains('log', $drivers);
        $this->assertContains('netgsm', $drivers);
        $this->assertContains('iletimerkezi', $drivers);
        $this->assertContains('twilio', $drivers);
    }

    public function test_log_driver_writes_to_file(): void
    {
        $driver = new LogDriver();
        $result = $driver->send('905551234567', 'Test SMS mesajı');
        $this->assertTrue($result['ok']);
        $this->assertSame('logged', $result['response']);

        $logFile = __DIR__ . '/../../storage/logs/sms.log';
        $this->assertFileExists($logFile);
        $content = (string) @file_get_contents($logFile);
        $this->assertStringContainsString('905551234567', $content);
    }

    public function test_manager_defaults_to_log_when_driver_not_set(): void
    {
        try {
            Connection::query("DELETE FROM settings WHERE `key` = 'sms.driver'");
        } catch (\Throwable) {}

        $result = SmsManager::send('905551234567', 'default-driver-test');
        $this->assertTrue($result['ok']);
    }
}
