<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $settings = [
            ['key' => 'site.name',        'value' => 'Ahost Bilişim', 'type' => 'string', 'group' => 'general', 'is_public' => 1],
            ['key' => 'site.tagline',     'value' => 'Modern Hosting & Dijital Hizmet Platformu', 'type' => 'string', 'group' => 'general', 'is_public' => 1],
            ['key' => 'company.name',     'value' => 'Ahost Bilişim',  'type' => 'string', 'group' => 'company'],
            ['key' => 'company.address',  'value' => 'İstanbul, Türkiye', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company.phone',    'value' => '0850 000 00 00', 'type' => 'string', 'group' => 'company', 'is_public' => 1],
            ['key' => 'company.email',    'value' => 'destek@ahost.web.tr', 'type' => 'string', 'group' => 'company', 'is_public' => 1],
            ['key' => 'company.tax_office','value' => 'İstanbul VD', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company.tax_id',   'value' => '1234567890', 'type' => 'string', 'group' => 'company'],
            ['key' => 'default.currency', 'value' => 'TRY', 'type' => 'string', 'group' => 'localization'],
            ['key' => 'default.locale',   'value' => 'tr',  'type' => 'string', 'group' => 'localization'],
            ['key' => 'default.tax_rate', 'value' => '20',  'type' => 'int', 'group' => 'billing'],
        ];
        foreach ($settings as $s) {
            $exists = Connection::selectOne("SELECT id FROM settings WHERE `key` = ?", [$s['key']]);
            if (!$exists) {
                Connection::insert('settings', array_merge($s, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
};
