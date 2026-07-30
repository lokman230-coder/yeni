<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('registrar_configs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registrar_id');
            $t->string('config_key', 120);
            $t->text('config_value')->nullable();
            $t->boolean('is_encrypted')->default(0);
            $t->timestamps();
            $t->unique(['registrar_id', 'config_key']);
            $t->foreign('registrar_id', 'domain_registrars', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('registrar_configs'); }
};
