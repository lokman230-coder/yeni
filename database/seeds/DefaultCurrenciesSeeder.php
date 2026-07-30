<?php

use App\Core\Database\Connection;

return new class {
    public function run(): void {
        $currencies = [
            ['currency' => 'TRY', 'rate' => 1.000000,  'margin_percent' => 0, 'source' => 'manual'],
            ['currency' => 'USD', 'rate' => 32.500000, 'margin_percent' => 2, 'source' => 'manual'],
            ['currency' => 'EUR', 'rate' => 35.200000, 'margin_percent' => 2, 'source' => 'manual'],
            ['currency' => 'GBP', 'rate' => 41.100000, 'margin_percent' => 2, 'source' => 'manual'],
        ];
        foreach ($currencies as $c) {
            $exists = Connection::selectOne("SELECT id FROM currency_rates WHERE currency = ?", [$c['currency']]);
            if (!$exists) {
                Connection::insert('currency_rates', array_merge($c, [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
};
