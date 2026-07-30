<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_upsells', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id');
            $t->foreignId('related_product_id');
            $t->decimal('discount_percent', 5, 2)->default(0);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'related_product_id']);
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('related_product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('product_upsells'); }
};
