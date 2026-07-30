<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Referans (Affiliate) programı tabloları — 4 tablo.
 *
 *  1. referral_settings   → Program parametreleri (komisyon %, min payout vs)
 *  2. referral_codes      → Her müşteriye bir kod (customer_id ↔ code)
 *  3. referral_visits     → Link tıklamaları (cookie'siz de sayılabilir)
 *  4. referrals           → "Kim kimi getirdi" — referrer ↔ referred
 *  5. referral_commissions→ Ödemeden düşen komisyon kayıtları
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Ayarlar (tek satır, is_active kontrolü ile birden fazla profil eklenebilir)
        Schema::create('referral_settings', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->default('Varsayılan Program');
            $t->decimal('commission_percent', 6, 3)->default(10.000); // %10
            $t->integer('cookie_days')->default(60);
            $t->decimal('min_payout', 12, 2)->default(100.00);   // TRY
            $t->enum('payout_method', ['balance', 'bank_transfer', 'both'])->default('balance');
            $t->boolean('first_order_only')->default(1); // sadece ilk sipariş komisyon üretir
            $t->boolean('is_active')->default(1);
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->dateTime('updated_at')->nullable();
        });

        // 2) Kodlar
        Schema::create('referral_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('code', 32);
            $t->integer('total_visits')->default(0);
            $t->integer('total_signups')->default(0);
            $t->integer('total_conversions')->default(0);
            $t->decimal('total_earned', 12, 2)->default(0);
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique('customer_id');
            $t->unique('code');
        });

        // 3) Ziyaretler (anonim de sayılır)
        Schema::create('referral_visits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('referral_code_id');
            $t->string('code', 32);
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->string('landing_url', 500)->nullable();
            $t->string('referer_url', 500)->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['referral_code_id', 'created_at']);
        });

        // 4) Eşleşmeler (kim kimi getirdi)
        Schema::create('referrals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('referrer_customer_id');   // yönlendiren
            $t->foreignId('referred_customer_id');   // yönlendirilen
            $t->string('code_used', 32);
            $t->enum('status', ['pending', 'converted', 'expired', 'cancelled'])->default('pending');
            $t->dateTime('converted_at')->nullable(); // ilk ödeme yapıldı
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique('referred_customer_id');
            $t->index(['referrer_customer_id', 'status']);
        });

        // 5) Komisyon kayıtları (ödemeden alınan pay)
        Schema::create('referral_commissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('referral_id');
            $t->foreignId('referrer_customer_id');
            $t->foreignId('order_id')->nullable();
            $t->foreignId('payment_id')->nullable();
            $t->decimal('order_total', 12, 2);
            $t->decimal('commission_percent', 6, 3);
            $t->decimal('commission_amount', 12, 2);
            $t->string('currency', 3)->default('TRY');
            $t->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $t->text('note')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['referrer_customer_id', 'status']);
            $t->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_visits');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('referral_settings');
    }
};
