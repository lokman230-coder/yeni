<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id');
            $t->string('description', 500);
            $t->integer('quantity')->default(1);
            $t->decimal('unit_price', 14, 4);
            $t->decimal('tax_rate', 6, 3)->default(0);
            $t->decimal('line_total', 14, 4);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index('invoice_id');
            $t->foreign('invoice_id', 'invoices', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('invoice_items'); }
};
