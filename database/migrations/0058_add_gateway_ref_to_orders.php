<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

return new class extends Migration {
    public function up(): void {
        $col = Connection::selectOne("SHOW COLUMNS FROM orders LIKE 'gateway_ref'");
        if (!$col) {
            Connection::query("ALTER TABLE orders ADD COLUMN gateway_ref VARCHAR(191) NULL AFTER status");
            Connection::query("ALTER TABLE orders ADD INDEX idx_orders_gateway_ref (gateway_ref)");
        }
    }
    public function down(): void {
        $col = Connection::selectOne("SHOW COLUMNS FROM orders LIKE 'gateway_ref'");
        if ($col) {
            Connection::query("ALTER TABLE orders DROP INDEX idx_orders_gateway_ref");
            Connection::query("ALTER TABLE orders DROP COLUMN gateway_ref");
        }
    }
};
