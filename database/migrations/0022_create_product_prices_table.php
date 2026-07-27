<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id');
            $t->enum('period', ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially']);
            $t->char('source_currency', 3)->default('TRY');
            $t->decimal('source_price', 14, 4);
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'period', 'source_currency']);
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('product_prices'); }
};
