<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * 2FA için secret + recovery codes sütunlarını ekle.
 * Encrypted (AES-256-GCM ile) olarak tutulur.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['admins', 'customers'] as $table) {
            $cols = Connection::select("SHOW COLUMNS FROM $table");
            $names = array_column($cols, 'Field');
            if (!in_array('two_factor_secret_encrypted', $names, true)) {
                Connection::query("ALTER TABLE $table ADD COLUMN two_factor_secret_encrypted TEXT NULL AFTER two_factor_enabled");
            }
            if (!in_array('two_factor_confirmed_at', $names, true)) {
                Connection::query("ALTER TABLE $table ADD COLUMN two_factor_confirmed_at DATETIME NULL AFTER two_factor_secret_encrypted");
            }
            if (!in_array('two_factor_recovery_codes', $names, true)) {
                Connection::query("ALTER TABLE $table ADD COLUMN two_factor_recovery_codes TEXT NULL AFTER two_factor_confirmed_at");
            }
        }
    }
    public function down(): void
    {
        // Non-destructive
    }
};
