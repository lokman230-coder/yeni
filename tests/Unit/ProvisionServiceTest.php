<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Hosting\Services\ProvisionService;
use PHPUnit\Framework\TestCase;

final class ProvisionServiceTest extends TestCase
{
    private int $orderId = 0;
    private int $itemId = 0;
    private array $createdAccountIds = [];

    protected function setUp(): void
    {
        // Test siparişi oluştur (ödenmemiş)
        $this->orderId = Connection::insert('orders', [
            'order_number'  => 'AHO-TEST-' . uniqid(),
            'customer_id'   => 1,
            'status'        => 'pending',
            'subtotal'      => 100, 'tax_total' => 20, 'total' => 120, 'currency' => 'TRY',
            'payment_method'=> 'manual',
        ]);
        $this->itemId = Connection::insert('order_items', [
            'order_id'     => $this->orderId,
            'product_id'   => 2,
            'product_name' => 'Test Hosting',
            'period'       => 'annually',
            'quantity'     => 1,
            'unit_price'   => 100, 'line_total' => 100,
            'domain_name'  => 'provisiontest' . random_int(1000, 9999) . '.com',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdAccountIds as $id) {
            try { Connection::query("DELETE FROM hosting_accounts WHERE id = ?", [$id]); } catch (\Throwable) {}
        }
        try {
            Connection::query("DELETE FROM hosting_accounts WHERE order_item_id = ?", [$this->itemId]);
            Connection::query("DELETE FROM order_items WHERE id = ?", [$this->itemId]);
            Connection::query("DELETE FROM orders WHERE id = ?", [$this->orderId]);
        } catch (\Throwable) {}
    }

    public function test_unpaid_order_returns_error(): void
    {
        $r = ProvisionService::provisionOrder($this->orderId);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('ödenmemiş', $r['errors'][0]);
    }

    public function test_paid_order_provisions_with_manual_queue_when_no_server(): void
    {
        Connection::update('orders', ['status' => 'paid'], 'id = ?', [$this->orderId]);
        // Sunucuları geçici deaktif et
        Connection::query("UPDATE hosting_servers SET is_active = 0");
        try {
            $r = ProvisionService::provisionOrder($this->orderId);
            $this->assertTrue($r['ok']);
            $this->assertSame(1, $r['provisioned']);

            $account = Connection::selectOne("SELECT * FROM hosting_accounts WHERE order_item_id = ?", [$this->itemId]);
            $this->assertNotNull($account);
            $this->createdAccountIds[] = (int) $account['id'];
            $this->assertSame('pending', $account['status']);
            $this->assertStringContainsString('manuel', $account['notes']);
        } finally {
            Connection::query("UPDATE hosting_servers SET is_active = 1");
        }
    }

    public function test_paid_order_marks_item_active_when_provisioned(): void
    {
        Connection::update('orders', ['status' => 'paid'], 'id = ?', [$this->orderId]);
        Connection::query("UPDATE hosting_servers SET is_active = 0");
        try {
            ProvisionService::provisionOrder($this->orderId);
            $item = Connection::selectOne("SELECT * FROM order_items WHERE id = ?", [$this->itemId]);
            $this->assertSame('active', $item['status']);
            $this->assertNotNull($item['activated_at']);
            $this->assertNotNull($item['next_due_date']);
        } finally {
            Connection::query("UPDATE hosting_servers SET is_active = 1");
        }
    }

    public function test_annually_next_due_date_is_1_year_forward(): void
    {
        Connection::update('orders', ['status' => 'paid'], 'id = ?', [$this->orderId]);
        Connection::query("UPDATE hosting_servers SET is_active = 0");
        try {
            ProvisionService::provisionOrder($this->orderId);
            $item = Connection::selectOne("SELECT next_due_date FROM order_items WHERE id = ?", [$this->itemId]);
            $due = strtotime((string) $item['next_due_date']);
            $now = time();
            $days = ($due - $now) / 86400;
            $this->assertGreaterThan(360, $days);
            $this->assertLessThan(370, $days);
        } finally {
            Connection::query("UPDATE hosting_servers SET is_active = 1");
        }
    }

    public function test_double_provisioning_skips_existing(): void
    {
        Connection::update('orders', ['status' => 'paid'], 'id = ?', [$this->orderId]);
        Connection::query("UPDATE hosting_servers SET is_active = 0");
        try {
            $r1 = ProvisionService::provisionOrder($this->orderId);
            $this->assertSame(1, $r1['provisioned']);
            $r2 = ProvisionService::provisionOrder($this->orderId);
            $this->assertSame(0, $r2['provisioned'], 'İkinci çağrıda tekrar açmamalı');
            $this->assertSame(1, $r2['skipped']);
        } finally {
            Connection::query("UPDATE hosting_servers SET is_active = 1");
        }
    }
}
