<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Admin > Müşteri adına panele giriş (Impersonate) — Rapor Madde 5.4
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('impersonation_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id');       // Giriş yapan admin (users.id)
            $t->foreignId('customer_id');    // Adına girilen müşteri (customers.id)
            $t->string('token', 128);
            $t->string('ip', 64)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->unique('token');
            $t->index('customer_id');
            $t->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_tokens');
    }
};
