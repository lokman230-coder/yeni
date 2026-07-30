<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Connection;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

// Ürün formunda artık "sunucu grubu" değil, tek bir sunucu (hosting_servers) seçiliyor.
return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $t) {
            $t->foreignId('server_id')->nullable();
        });
    }
    public function down(): void {
        Connection::pdo()->exec("ALTER TABLE `products` DROP COLUMN `server_id`");
    }
};
