<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('username', 64);
            $t->string('email', 191);
            $t->string('password_hash', 255);
            $t->string('full_name', 191)->nullable();
            $t->string('avatar', 255)->nullable();
            $t->foreignId('role_id')->nullable();
            $t->boolean('is_active')->default(1);
            $t->dateTime('last_login_at')->nullable();
            $t->string('last_login_ip', 45)->nullable();
            $t->boolean('two_factor_enabled')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->unique('username');
            $t->unique('email');
            $t->index('role_id');
            $t->foreign('role_id', 'admin_roles', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void {
        Schema::dropIfExists('admins');
    }
};
