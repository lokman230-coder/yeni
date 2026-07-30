<?php

use App\Core\Config;
use App\Core\Database\Connection;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ai_logs')) {
            return;
        }

        $db = Config::get('database.connections.mysql.database');
        $hasCost = Connection::selectOne(
            "SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = ? AND table_name = 'ai_logs' AND column_name = 'estimated_cost'",
            [$db]
        );

        if ((int) ($hasCost['c'] ?? 0) === 0) {
            Connection::pdo()->exec("ALTER TABLE `ai_logs` ADD COLUMN `estimated_cost` DECIMAL(12,6) NULL AFTER `tokens_used`");
        }
    }

    public function down(): void
    {
    }
};
