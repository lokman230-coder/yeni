<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Ai\Services\ContextBuilder;
use PHPUnit\Framework\TestCase;

final class AiContextBuilderTest extends TestCase
{
    public function test_admin_prompt_forbids_public_navigation(): void
    {
        $p = ContextBuilder::systemPrompt('admin');
        $this->assertStringContainsString('YASAK', $p);
        $this->assertStringContainsString('/admin', $p);
    }

    public function test_public_action_allowed_only_to_public_urls(): void
    {
        $this->assertTrue(ContextBuilder::isActionAllowed('public', 'navigate', '/hosting'));
        $this->assertTrue(ContextBuilder::isActionAllowed('public', 'navigate', '/domain'));
        $this->assertFalse(ContextBuilder::isActionAllowed('public', 'navigate', '/admin/musteriler'));
        $this->assertFalse(ContextBuilder::isActionAllowed('public', 'navigate', '/panel'));
    }

    public function test_admin_action_only_admin_urls(): void
    {
        $this->assertTrue(ContextBuilder::isActionAllowed('admin',  'navigate', '/admin/urun-merkezi'));
        $this->assertFalse(ContextBuilder::isActionAllowed('admin', 'navigate', '/hosting'));
        $this->assertFalse(ContextBuilder::isActionAllowed('admin', 'navigate', '/panel'));
    }

    public function test_customer_action_allows_panel_cart_checkout(): void
    {
        $this->assertTrue(ContextBuilder::isActionAllowed('customer',  'navigate', '/panel/destek'));
        $this->assertTrue(ContextBuilder::isActionAllowed('customer',  'navigate', '/sepet'));
        $this->assertTrue(ContextBuilder::isActionAllowed('customer',  'navigate', '/odeme'));
        $this->assertFalse(ContextBuilder::isActionAllowed('customer', 'navigate', '/admin/musteriler'));
    }

    public function test_context_constants_match(): void
    {
        $this->assertSame('public',   ContextBuilder::CTX_PUBLIC);
        $this->assertSame('customer', ContextBuilder::CTX_CUSTOMER);
        $this->assertSame('admin',    ContextBuilder::CTX_ADMIN);
    }
}
