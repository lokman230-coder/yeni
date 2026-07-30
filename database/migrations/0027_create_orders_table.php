<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_number', 32);
            $t->foreignId('customer_id');
            $t->enum('status', ['pending','paid','processing','active','failed','cancelled','refunded'])->default('pending');
            $t->decimal('subtotal', 14, 4)->default(0);
            $t->decimal('discount_total', 14, 4)->default(0);
            $t->decimal('tax_total', 14, 4)->default(0);
            $t->decimal('total', 14, 4)->default(0);
            $t->char('currency', 3)->default('TRY');
            $t->foreignId('coupon_id')->nullable();
            $t->string('coupon_code', 64)->nullable();
            $t->string('payment_method', 32)->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->text('notes')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->dateTime('activated_at')->nullable();
            $t->timestamps();
            $t->unique('order_number');
            $t->index(['customer_id', 'status']);
            $t->foreign('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
