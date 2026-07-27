<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Product\Services\OptionService;
use PHPUnit\Framework\TestCase;

final class OptionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            Connection::query("DELETE FROM cart_item_options");
            Connection::query("DELETE FROM product_option_values");
            Connection::query("DELETE FROM product_options");
        } catch (\Throwable) {}
    }

    public function test_save_creates_option_with_values(): void
    {
        $id = OptionService::save([
            'name'        => 'Test Lokasyon',
            'input_type'  => 'radio',
            'is_required' => 1,
            'is_active'   => 1,
        ], [
            ['label' => 'İstanbul', 'value_key' => 'ist', 'price_delta' => 0,   'currency' => 'TRY', 'period' => 'monthly', 'is_default' => 1, 'is_active' => 1],
            ['label' => 'Almanya',  'value_key' => 'de',  'price_delta' => 50,  'currency' => 'TRY', 'period' => 'monthly', 'is_default' => 0, 'is_active' => 1],
        ]);

        $this->assertGreaterThan(0, $id);
        $opt = OptionService::find($id);
        $this->assertNotNull($opt);
        $this->assertSame('Test Lokasyon', $opt['name']);
        $this->assertCount(2, $opt['values']);
    }

    public function test_save_generates_slug_when_missing(): void
    {
        $id = OptionService::save([
            'name'       => 'Panel Türü',
            'input_type' => 'select',
        ], []);
        $opt = OptionService::find($id);
        $this->assertStringContainsString('panel', $opt['slug']);
    }

    public function test_calculate_delta_sums_selected_values(): void
    {
        $id = OptionService::save(['name' => 'Test', 'input_type' => 'radio'], [
            ['label' => 'A', 'price_delta' => 10, 'currency' => 'TRY', 'period' => 'monthly'],
            ['label' => 'B', 'price_delta' => 25, 'currency' => 'TRY', 'period' => 'monthly'],
        ]);
        $opt = OptionService::find($id);
        $valueA = (int) $opt['values'][0]['id'];
        $valueB = (int) $opt['values'][1]['id'];

        $delta = OptionService::calculateDelta(1, [
            $id => $valueA,
        ]);
        $this->assertEqualsWithDelta(10.0, $delta, 0.001);

        $delta2 = OptionService::calculateDelta(1, [
            $id => $valueB,
        ]);
        $this->assertEqualsWithDelta(25.0, $delta2, 0.001);
    }

    public function test_calculate_delta_returns_zero_for_empty(): void
    {
        $this->assertSame(0.0, OptionService::calculateDelta(1, []));
    }

    public function test_delete_removes_option_and_values(): void
    {
        $id = OptionService::save(['name' => 'Silinecek', 'input_type' => 'select'], [
            ['label' => 'X', 'price_delta' => 0, 'currency' => 'TRY', 'period' => 'monthly'],
        ]);
        OptionService::delete($id);
        $this->assertNull(OptionService::find($id));
    }
}
