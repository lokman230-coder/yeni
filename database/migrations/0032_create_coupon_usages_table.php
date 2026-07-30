<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupon_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id');
            $t->foreignId('customer_id');
            $t->foreignId('order_id')->nullable();
            $t->decimal('discount_amount', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->dateTime('used_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['coupon_id', 'customer_id']);
            $t->foreign('coupon_id', 'coupons', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('coupon_usages'); }
};
