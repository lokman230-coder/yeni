<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Payment\Drivers\ShopierDriver;
use App\Services\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class ShopierDriverTest extends TestCase
{
    protected function setUp(): void
    {
        SettingsManager::reset();
        \App\Core\Database\Connection::query("DELETE FROM settings WHERE `key` LIKE 'shopier.%'");
        putenv('SHOPIER_API_KEY=');
        putenv('SHOPIER_API_SECRET=');
    }

    protected function tearDown(): void
    {
        \App\Core\Database\Connection::query("DELETE FROM settings WHERE `key` LIKE 'shopier.%'");
        SettingsManager::reset();
    }

    public function test_id_and_label(): void
    {
        $d = new ShopierDriver();
        $this->assertSame('shopier', $d->id());
        $this->assertSame('Shopier', $d->label());
    }

    public function test_missing_credentials_returns_error(): void
    {
        $d = new ShopierDriver();
        $r = $d->createCheckout(
            ['id'=>1, 'order_number'=>'AHO-X', 'total'=>100.0, 'currency'=>'TRY'],
            ['id'=>1, 'email'=>'a@b.c', 'first_name'=>'A', 'last_name'=>'B']
        );
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Shopier', $r['error']);
    }

    public function test_with_credentials_returns_html_form(): void
    {
        SettingsManager::set('shopier.api_key',    'test-key', 'encrypted', 'payment');
        SettingsManager::set('shopier.api_secret', 'test-secret-32chars', 'encrypted', 'payment');
        SettingsManager::reset();

        $d = new ShopierDriver();
        $r = $d->createCheckout(
            ['id'=>1, 'order_number'=>'AHO-TEST-001', 'total'=>250.75, 'currency'=>'TRY'],
            ['id'=>1, 'email'=>'test@example.com', 'first_name'=>'Ali', 'last_name'=>'Veli', 'phone'=>'5551234567']
        );
        $this->assertTrue($r['success']);
        $this->assertStringContainsString('<form', $r['html_form']);
        $this->assertStringContainsString('shopier.com/ShowProduct', $r['html_form']);
        $this->assertStringContainsString('AHO-TEST-001', $r['html_form']);
        $this->assertStringContainsString('250.75', $r['html_form']);
        $this->assertStringContainsString('signature', $r['html_form']);
    }

    public function test_callback_verifies_valid_signature(): void
    {
        $secret = 'my-secret-key';
        SettingsManager::set('shopier.api_secret', $secret, 'encrypted', 'payment');
        SettingsManager::reset();

        $randomNr = '123456';
        $orderNumber = 'AHO-TEST-XYZ';
        $data = $randomNr . $orderNumber;
        $signature = base64_encode(hash_hmac('SHA256', $data, $secret, true));

        $d = new ShopierDriver();
        $r = $d->handleCallback([
            'status' => 'success',
            'platform_order_id' => $orderNumber,
            'payment_id' => 'PAY-789',
            'random_nr' => $randomNr,
            'signature' => $signature,
        ]);
        $this->assertTrue($r['success']);
        $this->assertSame('PAY-789', $r['transaction_id']);
        $this->assertSame($orderNumber, $r['basket_id']);
    }

    public function test_callback_rejects_invalid_signature(): void
    {
        SettingsManager::set('shopier.api_secret', 'real-secret', 'encrypted', 'payment');
        SettingsManager::reset();

        $d = new ShopierDriver();
        $r = $d->handleCallback([
            'status' => 'success',
            'platform_order_id' => 'AHO-X',
            'payment_id' => 'PAY',
            'random_nr' => '111',
            'signature' => base64_encode('sahte-imza'),
        ]);
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Invalid signature', $r['message']);
    }

    public function test_callback_rejects_missing_params(): void
    {
        SettingsManager::set('shopier.api_secret', 'x', 'encrypted', 'payment');
        SettingsManager::reset();
        $d = new ShopierDriver();
        $r = $d->handleCallback(['status' => 'success']);
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Eksik', $r['message']);
    }
}
