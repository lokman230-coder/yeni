<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('hosting_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_item_id')->nullable();
            $t->foreignId('customer_id');
            $t->foreignId('product_id')->nullable();
            $t->foreignId('server_id')->nullable();
            $t->string('domain', 255);
            $t->string('username', 100)->nullable();
            $t->text('password_encrypted')->nullable();
            $t->string('package', 120)->nullable();
            $t->enum('status', ['pending','active','suspended','terminated'])->default('pending');
            $t->integer('disk_usage_mb')->nullable();
            $t->integer('bandwidth_usage_mb')->nullable();
            $t->dateTime('usage_updated_at')->nullable();
            $t->date('next_due_date')->nullable();
            $t->text('notes')->nullable();
            $t->dateTime('suspended_at')->nullable();
            $t->dateTime('terminated_at')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'status']);
            $t->index('server_id');
            $t->foreign('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
            $t->foreign('server_id', 'hosting_servers', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('hosting_accounts'); }
};
