<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Saklanan kartlar + otomatik tahsilat aboneliği.
 *
 * PCI-DSS uyumu için: kart numarası ASLA saklanmaz.
 * Sadece gateway token (iyzico cardUserKey, PayTR customer_key vb.) saklanır.
 *
 * auto_billing_enabled: müşteri bu kartı otomatik yenileme için işaretlemişse
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('stored_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('gateway', 32);                      // paytr | iyzico | papara | shopier
            $t->string('gateway_customer_key', 191);        // Gateway'de müşteri token
            $t->string('gateway_card_key', 191);            // Gateway'de kart token
            $t->string('card_holder', 100)->nullable();
            $t->string('card_last4', 4)->nullable();        // ****1234
            $t->string('card_brand', 20)->nullable();       // Visa/Mastercard/Troy vb.
            $t->integer('exp_month')->nullable();
            $t->integer('exp_year')->nullable();
            $t->string('nickname', 100)->nullable();        // "Kişisel Kart", "İş Kartım"
            $t->boolean('is_default')->default(0);
            $t->boolean('auto_billing_enabled')->default(0); // Otomatik tahsilata izin
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
            $t->index('customer_id');
            $t->index(['customer_id', 'is_default']);
        });

        // Otomatik tahsilat denemeleri (log)
        Schema::create('recurring_charge_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id');
            $t->foreignId('customer_id');
            $t->foreignId('stored_card_id')->nullable();
            $t->enum('method_used', ['stored_card','balance','skipped'])->default('stored_card');
            $t->decimal('amount', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->enum('result', ['success','failed','insufficient_balance','no_card','gateway_error','disabled'])->default('failed');
            $t->text('response')->nullable();
            $t->timestamps();
            $t->index('invoice_id');
            $t->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_charge_attempts');
        Schema::dropIfExists('stored_cards');
    }
};
