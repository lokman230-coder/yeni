<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Müşteri bakiye/kredi hareketleri.
 * customers.balance sütunu ile senkron tutulur (trigger benzeri PHP tarafında).
 *
 * Kaynak (source):
 *   admin_manual  → Admin panelden manuel ekleme
 *   payment       → Havale/kart ile bakiye yükleme
 *   invoice_pay   → Bakiye ile fatura ödeme (- işaretli)
 *   refund        → İade (+ işaretli)
 *   promo         → Kampanya/hediye
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->decimal('amount', 14, 4);            // + veya - olabilir
            $t->char('currency', 3)->default('TRY');
            $t->decimal('balance_after', 14, 4);     // Snapshot: bu işlemden sonra bakiye
            $t->string('source', 30);                // admin_manual/payment/invoice_pay/refund/promo
            $t->foreignId('admin_id')->nullable();   // Kim ekledi (source=admin_manual ise)
            $t->foreignId('invoice_id')->nullable(); // İlişkili fatura (varsa)
            $t->foreignId('payment_id')->nullable(); // İlişkili ödeme (varsa)
            $t->string('description', 500)->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'created_at']);
            $t->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credits');
    }
};
