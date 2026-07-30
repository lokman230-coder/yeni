<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->nullable();
            $t->foreignId('order_id')->nullable();
            $t->foreignId('customer_id');
            $t->string('method', 32);
            $t->decimal('amount', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->string('gateway_transaction_id', 191)->nullable();
            $t->enum('status', ['pending','success','failed','refunded'])->default('pending');
            $t->json('gateway_response')->nullable();
            $t->dateTime('processed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('invoice_id');
            $t->index('customer_id');
            $t->index('status');
            $t->foreign('invoice_id', 'invoices', 'id', 'SET NULL', 'CASCADE');
            $t->foreign('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
            $t->foreign('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
