<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

/**
 * Auto-apply kuponlar — belirli sepet tutarı üstünde otomatik uygulanır.
 * Örn: 500 TL üstü sepette WELCOME10 otomatik uygulanır.
 */
return new class extends Migration {
    public function up(): void
    {
        $col = Connection::selectOne("SHOW COLUMNS FROM coupons LIKE 'auto_apply'");
        if (!$col) {
            Connection::query("ALTER TABLE coupons ADD COLUMN auto_apply TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
            Connection::query("ALTER TABLE coupons ADD COLUMN priority INT NOT NULL DEFAULT 0 AFTER auto_apply");
            Connection::query("ALTER TABLE coupons ADD INDEX idx_coupons_autoapply (auto_apply, is_active, priority)");
        }
    }
    public function down(): void { /* non-destructive */ }
};
