<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $items = [
            ['name' => 'Genel', 'email' => 'destek@ahost.web.tr', 'sort_order' => 10],
            ['name' => 'Teknik', 'email' => 'teknik@ahost.web.tr', 'sort_order' => 20],
            ['name' => 'Muhasebe / Fatura', 'email' => 'muhasebe@ahost.web.tr', 'sort_order' => 30],
            ['name' => 'Satış', 'email' => 'satis@ahost.web.tr', 'sort_order' => 40],
        ];
        foreach ($items as $d) {
            $exists = Connection::selectOne("SELECT id FROM ticket_departments WHERE name = ?", [$d['name']]);
            if ($exists) continue;
            Connection::insert('ticket_departments', array_merge($d, [
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }
};
