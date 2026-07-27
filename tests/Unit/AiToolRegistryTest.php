<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Ai\Services\AiService;
use App\Modules\Ai\Services\AiToolRegistry;
use PHPUnit\Framework\TestCase;

final class AiToolRegistryTest extends TestCase
{
    public function test_customer_context_has_required_tools(): void
    {
        $tools = AiToolRegistry::forContext('customer');
        $names = array_keys($tools);
        $this->assertContains('create_ticket', $names);
        $this->assertContains('my_services_summary', $names);
        $this->assertContains('pay_invoice', $names);
        $this->assertContains('renew_domain', $names);
        $this->assertContains('request_password_reset', $names);
        $this->assertContains('navigate', $names);
    }

    public function test_admin_context_has_required_tools(): void
    {
        $tools = AiToolRegistry::forContext('admin');
        $names = array_keys($tools);
        $this->assertContains('create_coupon', $names);
        $this->assertContains('dashboard_summary', $names);
        $this->assertContains('find_customer', $names);
        $this->assertContains('maintenance_mode', $names);
        $this->assertContains('clear_cache', $names);
        $this->assertContains('health_check', $names);
    }

    public function test_builder_context_has_required_tools(): void
    {
        $tools = AiToolRegistry::forContext('builder');
        $names = array_keys($tools);
        $this->assertContains('add_block', $names);
        $this->assertContains('update_block_text', $names);
        $this->assertContains('change_color_palette', $names);
        $this->assertContains('delete_block', $names);
        $this->assertContains('list_blocks', $names);
    }

    public function test_destructive_tools_require_confirm(): void
    {
        $r = AiToolRegistry::call('admin', 'maintenance_mode', ['action' => 'on'], 1, 'admin');
        $this->assertFalse($r['ok']);
        $this->assertTrue($r['needs_confirm'] ?? false);
    }

    public function test_unknown_tool_returns_error(): void
    {
        $r = AiToolRegistry::call('customer', 'nonexistent_tool', [], 1, 'customer');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('bulunamadı', $r['message']);
    }

    public function test_openai_functions_format(): void
    {
        $fns = AiToolRegistry::toOpenAiFunctions('customer');
        $this->assertIsArray($fns);
        $this->assertGreaterThan(0, count($fns));
        $this->assertSame('function', $fns[0]['type']);
        $this->assertArrayHasKey('name', $fns[0]['function']);
        $this->assertArrayHasKey('parameters', $fns[0]['function']);
    }

    public function test_ask_with_tools_detects_ticket_creation(): void
    {
        $r = AiService::askWithTools('customer', 'ticket aç konu: test mesaj: deneme', 1, 'customer');
        $this->assertSame('create_ticket', $r['tool']);
    }

    public function test_ask_with_tools_detects_dashboard_summary(): void
    {
        $r = AiService::askWithTools('admin', 'dashboard özeti göster', 1, 'admin');
        $this->assertSame('dashboard_summary', $r['tool']);
        $this->assertTrue($r['ok']);
    }

    public function test_ask_with_tools_detects_builder_add_block(): void
    {
        $r = AiService::askWithTools('builder', 'hero blok ekle', 1, 'customer', ['project_id' => 1]);
        $this->assertSame('add_block', $r['tool']);
    }
}
