<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Builder\Services\BlockRegistry;
use PHPUnit\Framework\TestCase;

final class BlockRegistryTest extends TestCase
{
    public function test_all_blocks_have_required_fields(): void
    {
        foreach (BlockRegistry::all() as $type => $meta) {
            $this->assertArrayHasKey('category', $meta, "Block '{$type}' has no category");
            $this->assertArrayHasKey('label',    $meta, "Block '{$type}' has no label");
            $this->assertArrayHasKey('icon',     $meta, "Block '{$type}' has no icon");
        }
    }

    public function test_hosting_context_hides_radio_blocks(): void
    {
        // Şartname madde 23: hosting seçiliyken radyo blokları görünmez
        $blocks = BlockRegistry::forContext('site', 'hosting');
        $this->assertArrayNotHasKey('radio_player', $blocks);
        $this->assertArrayNotHasKey('dj_schedule',  $blocks);
        $this->assertArrayNotHasKey('song_request', $blocks);
    }

    public function test_radio_context_hides_ecommerce_blocks(): void
    {
        // Şartname madde 23: radyo seçiliyken e-ticaret blokları görünmez
        $blocks = BlockRegistry::forContext('site', 'radio');
        $this->assertArrayNotHasKey('cart',           $blocks);
        $this->assertArrayNotHasKey('checkout',       $blocks);
        $this->assertArrayNotHasKey('product_list',   $blocks);
    }

    public function test_ecommerce_context_has_cart(): void
    {
        // Şartname madde 23: e-ticaret seçiliyken sepet+ödeme blokları çıkar
        $blocks = BlockRegistry::forContext('site', 'ecommerce');
        $this->assertArrayHasKey('cart',     $blocks);
        $this->assertArrayHasKey('checkout', $blocks);
    }

    public function test_mobile_only_blocks_not_in_site(): void
    {
        $site = BlockRegistry::forContext('site', 'ecommerce');
        // "now_playing" mobile-only
        $this->assertArrayNotHasKey('now_playing', $site);
    }

    public function test_grouped_returns_categories(): void
    {
        $g = BlockRegistry::grouped('site', 'hosting');
        $this->assertArrayHasKey('content', $g);
        $this->assertArrayHasKey('structural', $g);
    }
}
