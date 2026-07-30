<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $items = [
            [
                'name'         => 'DomainNameAPI (dm.domainnameapi.com)',
                'slug'         => 'domainnameapi',
                'driver_class' => 'App\\Modules\\Registrar\\Drivers\\DomainNameApiDriver',
                'is_active'    => 1,
                'is_default'   => 1,
                'test_mode'    => 1,
                'sort_order'   => 10,
            ],
            [
                'name'         => 'Manuel Registrar (WHOIS + DNS)',
                'slug'         => 'manual',
                'driver_class' => 'App\\Modules\\Registrar\\Drivers\\ManualDriver',
                'is_active'    => 1,
                'is_default'   => 0,
                'test_mode'    => 0,
                'sort_order'   => 90,
            ],
        ];
        foreach ($items as $r) {
            $exists = Connection::selectOne("SELECT id FROM domain_registrars WHERE slug = ?", [$r['slug']]);
            if (!$exists) {
                $regId = Connection::insert('domain_registrars', array_merge($r, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));

                // DomainNameAPI için default config anahtarları — admin panelden doldurulacak
                if ($r['slug'] === 'domainnameapi') {
                    $defaults = [
                        ['reseller_id', '', 0],
                        ['api_key',     '', 1],
                        ['test_mode',   '1', 0],
                        ['api_url',     'https://api-sandbox.domainnameapi.com/v1', 0],
                    ];
                    foreach ($defaults as $d) {
                        try {
                            Connection::insert('registrar_configs', [
                                'registrar_id' => $regId,
                                'config_key'   => $d[0],
                                'config_value' => $d[1],
                                'is_encrypted' => $d[2],
                                'created_at'   => date('Y-m-d H:i:s'),
                                'updated_at'   => date('Y-m-d H:i:s'),
                            ]);
                        } catch (\Throwable) {}
                    }
                }
            }
        }
    }
};
