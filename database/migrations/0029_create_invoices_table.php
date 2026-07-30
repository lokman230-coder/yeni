<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number', 32);
            $t->foreignId('order_id')->nullable();
            $t->foreignId('customer_id');
            $t->enum('status', ['draft','unpaid','paid','partially_paid','overdue','cancelled','refunded'])->default('unpaid');
            $t->date('issue_date');
            $t->date('due_date');
            $t->dateTime('paid_at')->nullable();
            $t->decimal('subtotal', 14, 4)->default(0);
            $t->decimal('discount_total', 14, 4)->default(0);
            $t->decimal('tax_total', 14, 4)->default(0);
            $t->decimal('total', 14, 4)->default(0);
            $t->decimal('paid_total', 14, 4)->default(0);
            $t->decimal('balance', 14, 4)->default(0);
            $t->char('currency', 3)->default('TRY');
            $t->text('notes')->nullable();
            $t->text('terms')->nullable();
            $t->string('pdf_path', 255)->nullable();
            $t->timestamps();
            $t->unique('invoice_number');
            $t->index(['customer_id', 'status']);
            $t->index('due_date');
            $t->foreign('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
            $t->foreign('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('invoices'); }
};
