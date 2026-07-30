<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

// "Faturalandırılabilir Ürünler" sekmesi — tek seferlik ek ücret/kalem, seçilip faturaya çevrilir.
return new class extends Migration {
    public function up(): void {
        Schema::create('billable_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('description', 500);
            $t->integer('quantity')->default(1);
            $t->decimal('unit_price', 14, 4);
            $t->decimal('tax_rate', 6, 3)->default(0);
            $t->char('currency', 3)->default('TRY');
            $t->enum('status', ['pending', 'invoiced', 'cancelled'])->default('pending');
            $t->foreignId('invoice_id')->nullable();
            $t->timestamps();
            $t->index('customer_id');
            $t->index('status');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('invoice_id', 'invoices', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('billable_items'); }
};
