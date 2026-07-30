<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $items = [
            ['name' => 'Domain',        'slug' => 'domain',     'icon' => '🌐', 'sort_order' => 10],
            ['name' => 'Web Tasarım',   'slug' => 'web-tasarim','icon' => '🎨', 'sort_order' => 20],
            ['name' => 'Logo Tasarım',  'slug' => 'logo',       'icon' => '✏️', 'sort_order' => 30],
            ['name' => 'Yazılım/Script','slug' => 'script',     'icon' => '⚙️', 'sort_order' => 40],
            ['name' => 'Mobil Uygulama','slug' => 'mobile-app', 'icon' => '📱', 'sort_order' => 50],
            ['name' => 'SEO Hizmeti',   'slug' => 'seo',        'icon' => '🔍', 'sort_order' => 60],
            ['name' => 'Dijital İçerik','slug' => 'digital',    'icon' => '📄', 'sort_order' => 70],
        ];
        foreach ($items as $c) {
            $exists = Connection::selectOne("SELECT id FROM marketplace_categories WHERE slug = ?", [$c['slug']]);
            if ($exists) continue;
            Connection::insert('marketplace_categories', array_merge($c, [
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        }

        // Örnek 3 ilan
        $testCustomer = Connection::selectOne("SELECT id FROM customers WHERE email = 'test@ahost.web.tr'");
        if ($testCustomer) {
            $sellerId = (int) $testCustomer['id'];
            $sample = [
                ['title' => 'Premium Domain: hosting.tr', 'category_slug' => 'domain', 'price' => 15000, 'desc' => 'Kısa, akılda kalıcı, hosting sektörü için ideal domain.'],
                ['title' => 'Modern Logo Tasarımı', 'category_slug' => 'logo', 'price' => 750, 'desc' => '48 saatte teslim, 3 revizyon, AI + PNG + SVG dosyaları.'],
                ['title' => 'Kurumsal Web Site Paketi', 'category_slug' => 'web-tasarim', 'price' => 4500, 'desc' => 'Anahtar teslim kurumsal site, mobil uyumlu, SEO temel ayarları.'],
            ];
            foreach ($sample as $s) {
                $exists = Connection::selectOne("SELECT id FROM marketplace_listings WHERE title = ?", [$s['title']]);
                if ($exists) continue;
                $cat = Connection::selectOne("SELECT id FROM marketplace_categories WHERE slug = ?", [$s['category_slug']]);
                Connection::insert('marketplace_listings', [
                    'seller_id'   => $sellerId,
                    'category_id' => $cat['id'] ?? null,
                    'title'       => $s['title'],
                    'slug'        => \App\Support\Slug::unique($s['title'], 'marketplace_listings', 'slug'),
                    'description' => $s['desc'],
                    'price'       => $s['price'],
                    'currency'    => 'TRY',
                    'status'      => 'active',
                    'commission_rate' => 5,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
};
