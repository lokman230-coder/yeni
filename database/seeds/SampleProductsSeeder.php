<?php

use App\Core\Database\Connection;
use App\Support\Slug;

return new class {
    public function run(): void {
        // Grup oluştur
        $groups = [
            ['name' => 'Web Hosting',    'slug' => 'web-hosting',    'icon' => '🌐', 'sort_order' => 10],
            ['name' => 'VPS & Sunucu',   'slug' => 'vps-sunucu',     'icon' => '🖥️', 'sort_order' => 20],
            ['name' => 'Site Builder',   'slug' => 'site-builder',   'icon' => '🎨', 'sort_order' => 30],
        ];
        $groupIds = [];
        foreach ($groups as $g) {
            $exists = Connection::selectOne("SELECT id FROM product_groups WHERE slug = ?", [$g['slug']]);
            if ($exists) { $groupIds[$g['slug']] = (int) $exists['id']; continue; }
            $groupIds[$g['slug']] = Connection::insert('product_groups', array_merge($g, [
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        }

        $sampleProducts = [
            [
                'name' => 'Hosting Başlangıç', 'type' => 'hosting', 'group' => 'web-hosting',
                'short' => '10 GB NVMe · 100 GB Trafik · Ücretsiz SSL',
                'prices' => [
                    ['period' => 'monthly',  'price' => 39,  'currency' => 'TRY'],
                    ['period' => 'annually', 'price' => 390, 'currency' => 'TRY'],
                ],
            ],
            [
                'name' => 'Hosting Business', 'type' => 'hosting', 'group' => 'web-hosting',
                'short' => '50 GB NVMe · Sınırsız Trafik · LiteSpeed',
                'prices' => [
                    ['period' => 'monthly',  'price' => 89,  'currency' => 'TRY'],
                    ['period' => 'annually', 'price' => 890, 'currency' => 'TRY'],
                ],
            ],
            [
                'name' => 'Hosting Kurumsal', 'type' => 'hosting', 'group' => 'web-hosting',
                'short' => '200 GB NVMe · Ücretsiz Domain + SSL',
                'prices' => [
                    ['period' => 'monthly',  'price' => 189,  'currency' => 'TRY'],
                    ['period' => 'annually', 'price' => 1890, 'currency' => 'TRY'],
                ],
            ],
            [
                'name' => 'VPS Starter', 'type' => 'vps', 'group' => 'vps-sunucu',
                'short' => '2 vCPU · 4 GB RAM · 60 GB NVMe',
                'prices' => [
                    ['period' => 'monthly', 'price' => 249, 'currency' => 'TRY'],
                ],
            ],
            [
                'name' => 'Site Builder Pro', 'type' => 'site_builder', 'group' => 'site-builder',
                'short' => 'AI destekli site oluşturucu, sınırsız sayfa',
                'prices' => [
                    ['period' => 'monthly',  'price' => 149,  'currency' => 'TRY'],
                    ['period' => 'annually', 'price' => 1490, 'currency' => 'TRY'],
                ],
            ],
        ];

        foreach ($sampleProducts as $sp) {
            $exists = Connection::selectOne("SELECT id FROM products WHERE name = ?", [$sp['name']]);
            if ($exists) continue;

            $productId = Connection::insert('products', [
                'group_id'          => $groupIds[$sp['group']] ?? null,
                'type'              => $sp['type'],
                'name'              => $sp['name'],
                'slug'              => Slug::unique($sp['name'], 'products'),
                'short_description' => $sp['short'],
                'description'       => '<p>' . $sp['short'] . '</p><p>Faz 3 örnek ürünü. Admin panelinden içeriği düzenleyebilirsiniz.</p>',
                'status'            => 'active',
                'stock_type'        => 'unlimited',
                'payment_type'      => 'recurring',
                'setup_fee'         => 0,
                'setup_fee_currency'=> 'TRY',
                'sort_order'        => 0,
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            foreach ($sp['prices'] as $pr) {
                Connection::insert('product_prices', [
                    'product_id'      => $productId,
                    'period'          => $pr['period'],
                    'source_currency' => $pr['currency'],
                    'source_price'    => $pr['price'],
                    'is_active'       => 1,
                    'sort_order'      => 0,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Örnek kupon
        $exists = Connection::selectOne("SELECT id FROM coupons WHERE code = 'WELCOME10'");
        if (!$exists) {
            Connection::insert('coupons', [
                'code'        => 'WELCOME10',
                'name'        => 'Hoş Geldin İndirimi',
                'type'        => 'percent',
                'value'       => 10,
                'is_active'   => 1,
                'usage_count' => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // Örnek müşteri (test için)
        $exists = Connection::selectOne("SELECT id FROM customers WHERE email = 'test@ahost.web.tr'");
        if (!$exists) {
            Connection::insert('customers', [
                'email'         => 'test@ahost.web.tr',
                'password_hash' => \App\Services\Auth\PasswordHasher::hash('Test1234!'),
                'first_name'    => 'Test',
                'last_name'     => 'Müşteri',
                'phone'         => '05555555555',
                'country'       => 'TR',
                'city'          => 'İstanbul',
                'address'       => 'Test adres',
                'status'        => 'active',
                'balance'       => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            echo "  → Test müşteri: test@ahost.web.tr / Test1234!" . PHP_EOL;
        }
    }
};
