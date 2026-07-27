<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id');
            $t->foreignId('product_id');
            $t->string('product_name', 191);
            $t->enum('period', ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'])->default('monthly');
            $t->integer('quantity')->default(1);
            $t->enum('domain_action', ['register','transfer','use_own','update_dns'])->nullable();
            $t->string('domain_name', 255)->nullable();
            $t->json('addons')->nullable();
            $t->json('custom_fields')->nullable();
            $t->decimal('unit_price', 14, 4);
            $t->decimal('setup_fee', 14, 4)->default(0);
            $t->decimal('line_total', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->enum('status', ['pending','active','suspended','terminated','cancelled'])->default('pending');
            $t->dateTime('activated_at')->nullable();
            $t->dateTime('next_due_date')->nullable();
            $t->timestamps();
            $t->index('order_id');
            $t->index(['product_id', 'status']);
            $t->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); }
};
