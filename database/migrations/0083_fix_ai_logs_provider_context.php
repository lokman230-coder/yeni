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
        $hasProvider = Connection::selectOne(
            "SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = ? AND table_name = 'ai_logs' AND column_name = 'provider'",
            [$db]
        );

        if ((int) ($hasProvider['c'] ?? 0) === 0) {
            Connection::pdo()->exec("ALTER TABLE `ai_logs` ADD COLUMN `provider` VARCHAR(64) NULL AFTER `context`");
        }

        try {
            Connection::pdo()->exec("ALTER TABLE `ai_logs` MODIFY COLUMN `context` VARCHAR(64) NOT NULL DEFAULT 'public'");
        } catch (\Throwable) {
            // Some MySQL variants may already have a compatible column.
        }
    }

    public function down(): void
    {
        // Keep logs and compatibility columns.
    }
};
