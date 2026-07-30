<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * currency_rates tablosunu genişlet:
 * - symbol: para birimi sembolü (₺, $, €)
 * - is_active: müşteri seçimi için gösterilsin mi?
 * - source: 'tcmb', 'exchangerate.host' eklendi
 */
return new class extends Migration {
    public function up(): void {
        $cols = Connection::select("SHOW COLUMNS FROM currency_rates");
        $names = array_column($cols, 'Field');

        if (!in_array('symbol', $names, true)) {
            Connection::query("ALTER TABLE currency_rates ADD COLUMN symbol VARCHAR(8) NULL AFTER currency");
        }
        if (!in_array('is_active', $names, true)) {
            Connection::query("ALTER TABLE currency_rates ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER margin_percent");
        }
        // source enum'u genişlet
        Connection::query("ALTER TABLE currency_rates MODIFY COLUMN source VARCHAR(32) NOT NULL DEFAULT 'manual'");

        // Sembolleri set et
        foreach ([
            'TRY' => '₺', 'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        ] as $c => $s) {
            Connection::update('currency_rates', ['symbol' => $s], 'currency = ? AND (symbol IS NULL OR symbol = "")', [$c]);
        }
    }
    public function down(): void {
        // no destructive rollback (data preservation)
    }
};
