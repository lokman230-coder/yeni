<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->string('session_id', 128)->nullable();
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('product_id');
            $t->enum('period', ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'])->default('monthly');
            $t->integer('quantity')->default(1);
            $t->enum('domain_action', ['register','transfer','use_own','update_dns'])->nullable();
            $t->string('domain_name', 255)->nullable();
            $t->json('addons')->nullable();
            $t->json('custom_fields')->nullable();
            $t->decimal('unit_price', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->timestamps();
            $t->index('session_id');
            $t->index('customer_id');
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('cart_items'); }
};
