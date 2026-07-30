<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

// "Teklifler" sekmesi — teklif oluştur, gönder, onaylanınca faturaya çevir.
return new class extends Migration {
    public function up(): void {
        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->string('quote_number', 32);
            $t->foreignId('customer_id');
            $t->string('subject', 191);
            $t->enum('status', ['draft', 'sent', 'accepted', 'declined', 'expired'])->default('draft');
            $t->date('valid_until')->nullable();
            $t->decimal('subtotal', 14, 4)->default(0);
            $t->decimal('tax_total', 14, 4)->default(0);
            $t->decimal('total', 14, 4)->default(0);
            $t->char('currency', 3)->default('TRY');
            $t->text('notes')->nullable();
            $t->foreignId('converted_invoice_id')->nullable();
            $t->dateTime('sent_at')->nullable();
            $t->dateTime('accepted_at')->nullable();
            $t->dateTime('declined_at')->nullable();
            $t->timestamps();
            $t->unique('quote_number');
            $t->index('customer_id');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('converted_invoice_id', 'invoices', 'id', 'SET NULL', 'CASCADE');
        });

        Schema::create('quote_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quote_id');
            $t->string('description', 500);
            $t->integer('quantity')->default(1);
            $t->decimal('unit_price', 14, 4);
            $t->decimal('tax_rate', 6, 3)->default(0);
            $t->decimal('line_total', 14, 4);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index('quote_id');
            $t->foreign('quote_id', 'quotes', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
