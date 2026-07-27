<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $roles = [
            ['name' => 'Süper Admin',   'slug' => 'super_admin',       'description' => 'Sistemdeki tüm yetkilere sahip', 'is_system' => 1],
            ['name' => 'Admin',         'slug' => 'admin',             'description' => 'Genel yönetici', 'is_system' => 1],
            ['name' => 'Destek',        'slug' => 'support',           'description' => 'Destek personeli', 'is_system' => 1],
            ['name' => 'Muhasebe',      'slug' => 'accounting',        'description' => 'Muhasebe personeli', 'is_system' => 1],
            ['name' => 'Satıcı',        'slug' => 'marketplace_seller','description' => 'Marketplace satıcısı', 'is_system' => 1],
        ];

        foreach ($roles as $r) {
            $exists = Connection::selectOne("SELECT id FROM admin_roles WHERE slug = ?", [$r['slug']]);
            if (!$exists) {
                Connection::insert('admin_roles', array_merge($r, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
};
