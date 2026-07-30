<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_addons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->nullable(); // NULL = genel/tekrar kullanılabilir
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->string('description', 500)->nullable();
            $t->decimal('price', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->enum('period', ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'])->default('monthly');
            $t->string('addon_type', 64)->nullable();
            $t->string('automation_code', 64)->nullable();
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['product_id', 'is_active']);
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('product_addons'); }
};
