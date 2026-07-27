<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * Sepet terk edilme e-postası için reminder_sent_at sütunu.
 * Bir sepet için sadece bir kere hatırlatma gönderilir.
 */
return new class extends Migration {
    public function up(): void
    {
        $col = Connection::selectOne("SHOW COLUMNS FROM cart_items LIKE 'reminder_sent_at'");
        if (!$col) {
            Connection::query("ALTER TABLE cart_items ADD COLUMN reminder_sent_at DATETIME NULL AFTER created_at");
            Connection::query("ALTER TABLE cart_items ADD INDEX idx_cart_reminder (customer_id, reminder_sent_at, created_at)");
        }
    }
    public function down(): void
    {
        // non-destructive
    }
};
