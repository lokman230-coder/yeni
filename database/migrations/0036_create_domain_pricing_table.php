<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('domain_pricing', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registrar_id')->nullable();
            $t->string('tld', 20);
            $t->integer('period_years')->default(1);
            $t->decimal('register_price', 14, 4);
            $t->decimal('transfer_price', 14, 4)->default(0);
            $t->decimal('renew_price', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->unique(['tld', 'period_years']);
            $t->foreign('registrar_id', 'domain_registrars', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('domain_pricing'); }
};
