<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * SMS/OTP tek kullanımlık kodlar — Rapor Madde 6.1
 * Kullanım: login (SMS ile giriş), phone_verify, password_reset_sms
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 20)->default('sms');   // sms | email | whatsapp
            $t->string('purpose', 30)->default('login'); // login | verify_phone | password_reset
            $t->string('identity', 191);                 // telefon veya e-posta
            $t->string('code_hash', 191);                // hash'lenmiş kod
            $t->integer('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip', 64)->nullable();
            $t->timestamps();
            $t->index(['identity', 'purpose']);
            $t->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
