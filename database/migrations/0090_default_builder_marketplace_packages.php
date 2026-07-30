<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

return new class extends Migration {
    public function up(): void
    {
        $groups = [
            ['name' => 'Site Builder', 'slug' => 'site-builder', 'icon' => '🎨', 'sort_order' => 30],
            ['name' => 'Mobile Builder', 'slug' => 'mobile-builder', 'icon' => '📱', 'sort_order' => 35],
            ['name' => 'Marketplace', 'slug' => 'marketplace', 'icon' => '🛍️', 'sort_order' => 40],
        ];

        $groupIds = [];
        foreach ($groups as $group) {
            $existing = Connection::selectOne("SELECT id FROM product_groups WHERE slug = ?", [$group['slug']]);
            if ($existing) {
                $groupIds[$group['slug']] = (int) $existing['id'];
                continue;
            }
            $groupIds[$group['slug']] = Connection::insert('product_groups', [
                'name' => $group['name'],
                'slug' => $group['slug'],
                'description' => $group['name'] . ' paketleri',
                'icon' => $group['icon'],
                'sort_order' => $group['sort_order'],
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $products = [
            [
                'group' => 'site-builder',
                'type' => 'site_builder',
                'name' => 'Site Builder Baslangic',
                'slug' => 'site-builder-baslangic',
                'short' => 'AI olmadan surukle-birak site olusturma ve yayina hazir taslak.',
                'payment_type' => 'recurring',
                'prices' => [['monthly', 99], ['annually', 990]],
            ],
            [
                'group' => 'site-builder',
                'type' => 'site_builder',
                'name' => 'Site Builder Pro',
                'slug' => 'site-builder-pro',
                'short' => 'Site Builder, coklu sayfa, ZIP export ve gelismis bloklar.',
                'payment_type' => 'recurring',
                'prices' => [['monthly', 149], ['annually', 1490]],
            ],
            [
                'group' => 'site-builder',
                'type' => 'site_builder',
                'name' => 'Site Builder Kaynak Kod Paketi',
                'slug' => 'site-builder-kaynak-kod-paketi',
                'short' => 'Olusturulan siteyi kaynak kod / ZIP olarak teslim alma paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 499]],
            ],
            [
                'group' => 'mobile-builder',
                'type' => 'mobile_builder',
                'name' => 'Mobile Builder PWA Paketi',
                'slug' => 'mobile-builder-pwa-paketi',
                'short' => 'Mobil tasarimi PWA olarak yayina alma paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 299]],
            ],
            [
                'group' => 'mobile-builder',
                'type' => 'mobile_builder',
                'name' => 'Mobile Builder APK Paketi',
                'slug' => 'mobile-builder-apk-paketi',
                'short' => 'Android APK build ve teslim paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 599]],
            ],
            [
                'group' => 'mobile-builder',
                'type' => 'mobile_builder',
                'name' => 'Mobile Builder AAB Paketi',
                'slug' => 'mobile-builder-aab-paketi',
                'short' => 'Google Play icin Android App Bundle build paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 799]],
            ],
            [
                'group' => 'mobile-builder',
                'type' => 'mobile_builder',
                'name' => 'Mobile Builder Kaynak Kod Paketi',
                'slug' => 'mobile-builder-kaynak-kod-paketi',
                'short' => 'Flutter / React Native / Android kaynak kod teslim paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 1499]],
            ],
            [
                'group' => 'marketplace',
                'type' => 'marketplace',
                'name' => 'Marketplace Satici Baslangic',
                'slug' => 'marketplace-satici-baslangic',
                'short' => 'Marketplace uzerinde satisa baslama ve temel magaza paketi.',
                'payment_type' => 'recurring',
                'prices' => [['monthly', 99], ['annually', 990]],
            ],
            [
                'group' => 'marketplace',
                'type' => 'marketplace',
                'name' => 'Marketplace Satici Pro',
                'slug' => 'marketplace-satici-pro',
                'short' => 'Daha fazla ilan, one cikan magaza ve gelismis satici haklari.',
                'payment_type' => 'recurring',
                'prices' => [['monthly', 199], ['annually', 1990]],
            ],
            [
                'group' => 'marketplace',
                'type' => 'digital_service',
                'name' => 'Marketplace One Cikarma Paketi',
                'slug' => 'marketplace-one-cikarma-paketi',
                'short' => 'Ilanlari vitrine ve kategori ust siralarina tasima paketi.',
                'payment_type' => 'onetime',
                'prices' => [['onetime', 249]],
            ],
        ];

        foreach ($products as $product) {
            $existing = Connection::selectOne("SELECT id FROM products WHERE slug = ?", [$product['slug']]);
            $productId = $existing ? (int) $existing['id'] : Connection::insert('products', [
                'group_id' => $groupIds[$product['group']] ?? null,
                'type' => $product['type'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'short_description' => $product['short'],
                'description' => '<p>' . htmlspecialchars($product['short'], ENT_QUOTES, 'UTF-8') . '</p><p>Fiyat ve icerigi admin panelinden duzenleyebilirsiniz.</p>',
                'status' => 'active',
                'stock_type' => 'unlimited',
                'payment_type' => $product['payment_type'],
                'setup_fee' => 0,
                'setup_fee_currency' => 'TRY',
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($product['prices'] as [$period, $price]) {
                $priceExists = Connection::selectOne(
                    "SELECT id FROM product_prices WHERE product_id = ? AND period = ? AND source_currency = ?",
                    [$productId, $period, 'TRY']
                );
                if ($priceExists) continue;
                Connection::insert('product_prices', [
                    'product_id' => $productId,
                    'period' => $period,
                    'source_currency' => 'TRY',
                    'source_price' => $price,
                    'is_active' => 1,
                    'sort_order' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down(): void {}
};
