<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $exists = Connection::selectOne("SELECT id FROM tax_rules WHERE name = 'KDV %20'");
        if (!$exists) {
            Connection::insert('tax_rules', [
                'name'       => 'KDV %20',
                'rate'       => 20.000,
                'country'    => 'TR',
                'apply_type' => 'exclusive',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
};
