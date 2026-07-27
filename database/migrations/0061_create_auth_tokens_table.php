<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * auth_tokens — password reset, email verify, magic link vb.
 * Tek tablo, "purpose" kolonu ile ayrım.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth_tokens', function (Blueprint $t) {
            $t->id();
            $t->enum('user_type', ['customer', 'admin']);
            $t->foreignId('user_id');
            $t->enum('purpose', ['password_reset', 'email_verify', 'magic_link']);
            $t->string('token', 128);
            $t->string('email', 191)->nullable();
            $t->dateTime('expires_at');
            $t->dateTime('used_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique('token');
            $t->index(['user_type', 'user_id', 'purpose']);
            $t->index('expires_at');
        });
    }
    public function down(): void { Schema::dropIfExists('auth_tokens'); }
};
