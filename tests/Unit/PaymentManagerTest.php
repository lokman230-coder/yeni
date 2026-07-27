<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Drivers\IyzicoDriver;
use App\Modules\Payment\Drivers\PaparaDriver;
use App\Modules\Payment\Drivers\PayTrDriver;
use App\Modules\Payment\PaymentManager;
use PHPUnit\Framework\TestCase;

final class PaymentManagerTest extends TestCase
{
    public function test_all_registered_drivers_exist(): void
    {
        $ids = array_keys(PaymentManager::all());
        $this->assertContains('paytr', $ids);
        $this->assertContains('iyzico', $ids);
        $this->assertContains('papara', $ids);
        $this->assertContains('shopier', $ids);
    }

    public function test_driver_returns_instance_of_interface(): void
    {
        foreach (['paytr', 'iyzico', 'papara', 'shopier'] as $id) {
            $d = PaymentManager::driver($id);
            $this->assertInstanceOf(PaymentGatewayInterface::class, $d, "$id driver çalışmadı");
            $this->assertSame($id, $d->id());
            $this->assertNotEmpty($d->label());
        }
    }

    public function test_unknown_driver_returns_null(): void
    {
        $this->assertNull(PaymentManager::driver('bilinmeyen-gateway'));
    }

    public function test_iyzico_returns_error_when_no_credentials(): void
    {
        $d = new IyzicoDriver();
        // Env'i boşalt
        putenv('IYZICO_API_KEY=');
        putenv('IYZICO_SECRET_KEY=');
        $r = $d->createCheckout(
            ['id'=>1, 'order_number'=>'X', 'total'=>10.0, 'currency'=>'TRY'],
            ['id'=>1, 'email'=>'a@b.c', 'first_name'=>'A', 'last_name'=>'B']
        );
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('IYZICO', $r['error']);
    }

    public function test_papara_returns_error_when_no_credentials(): void
    {
        $d = new PaparaDriver();
        putenv('PAPARA_API_KEY=');
        $r = $d->createCheckout(
            ['id'=>1, 'order_number'=>'X', 'total'=>10.0, 'currency'=>'TRY'],
            ['id'=>1, 'email'=>'a@b.c']
        );
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('PAPARA', $r['error']);
    }

    public function test_papara_currency_mapping(): void
    {
        // Reflection ile private metodu test edemeyiz, ama davranışsal test:
        // (mapping doğru olmalı — 0=TRY, 1=USD, 2=EUR, 3=GBP)
        $r = new \ReflectionMethod(PaparaDriver::class, 'currencyCode');
        $r->setAccessible(true);
        $d = new PaparaDriver();
        $this->assertSame(0, $r->invoke($d, 'TRY'));
        $this->assertSame(1, $r->invoke($d, 'USD'));
        $this->assertSame(2, $r->invoke($d, 'EUR'));
        $this->assertSame(3, $r->invoke($d, 'GBP'));
        $this->assertSame(0, $r->invoke($d, 'XYZ')); // fallback
    }

    public function test_paytr_driver_still_works(): void
    {
        $d = PaymentManager::driver('paytr');
        $this->assertInstanceOf(PayTrDriver::class, $d);
        $this->assertSame('PayTR Kredi Kartı', $d->label());
    }
}
