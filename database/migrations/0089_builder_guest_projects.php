<?php

use App\Core\Database\Connection;
use App\Core\Database\Migration;

return new class extends Migration {
    public function up(): void
    {
        $pdo = Connection::pdo();
        $db = (string) \App\Core\Config::get('database.connections.mysql.database');

        $hasGuest = Connection::selectOne(
            "SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = ? AND table_name = 'builder_projects' AND column_name = 'guest_token'",
            [$db]
        );
        if ((int)($hasGuest['c'] ?? 0) === 0) {
            $pdo->exec("ALTER TABLE `builder_projects` ADD COLUMN `guest_token` VARCHAR(64) NULL AFTER `customer_id`");
            $pdo->exec("ALTER TABLE `builder_projects` ADD INDEX `idx_builder_projects_guest_token` (`guest_token`)");
        }

        try {
            $pdo->exec("ALTER TABLE `builder_projects` DROP FOREIGN KEY `fk_builder_projects_customer_id`");
        } catch (\Throwable) {}

        try {
            $pdo->exec("ALTER TABLE `builder_projects` MODIFY `customer_id` BIGINT UNSIGNED NULL");
        } catch (\Throwable) {}

        try {
            $pdo->exec("ALTER TABLE `builder_projects` ADD CONSTRAINT `fk_builder_projects_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        } catch (\Throwable) {}
    }

    public function down(): void {}
};
