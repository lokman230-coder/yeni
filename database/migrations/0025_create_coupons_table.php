<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 64);
            $t->string('name', 191);
            $t->enum('type', ['percent','fixed'])->default('percent');
            $t->decimal('value', 10, 4);
            $t->char('currency', 3)->nullable();
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->integer('usage_limit')->nullable();
            $t->integer('usage_limit_per_customer')->nullable();
            $t->integer('usage_count')->default(0);
            $t->decimal('min_order_total', 14, 4)->nullable();
            $t->json('applicable_products')->nullable();
            $t->json('applicable_groups')->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->unique('code');
            $t->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('coupons'); }
};
