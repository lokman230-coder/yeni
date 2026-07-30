<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Referral kazançları için çekim (payout) istekleri.
 * Müşteri bakiyesindeki tutarı banka havalesi ile talep eder.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3)->default('TRY');
            $t->enum('method', ['bank_transfer', 'papara', 'balance'])->default('bank_transfer');
            $t->string('iban', 34)->nullable();
            $t->string('account_holder', 191)->nullable();
            $t->string('bank_name', 100)->nullable();
            $t->text('note')->nullable();
            $t->enum('status', ['pending', 'approved', 'paid', 'rejected', 'cancelled'])->default('pending');
            $t->text('admin_note')->nullable();
            $t->foreignId('processed_by_admin_id')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->dateTime('rejected_at')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->dateTime('updated_at')->nullable();
            $t->index(['customer_id', 'status']);
            $t->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('payout_requests'); }
};
