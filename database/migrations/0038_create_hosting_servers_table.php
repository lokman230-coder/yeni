<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('hosting_servers', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('hostname', 191);
            $t->string('ip', 45)->nullable();
            $t->enum('panel', ['cpanel','da','plesk','manual'])->default('cpanel');
            $t->string('username', 100)->nullable();
            $t->text('password_encrypted')->nullable();
            $t->text('api_key_encrypted')->nullable();
            $t->integer('port')->default(2087);
            $t->boolean('use_ssl')->default(1);
            $t->boolean('is_active')->default(1);
            $t->integer('max_accounts')->nullable();
            $t->integer('current_accounts')->default(0);
            $t->string('server_group', 100)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('hosting_servers'); }
};
