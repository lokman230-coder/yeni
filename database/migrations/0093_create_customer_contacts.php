<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

// "Kullanıcılar" sekmesi — müşteri firmasındaki ek yetkili kişiler/alt hesaplar.
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('first_name', 100);
            $t->string('last_name', 100)->nullable();
            $t->string('email', 191);
            $t->string('phone', 32)->nullable();
            $t->string('role_label', 100)->nullable();
            $t->string('password_hash', 255)->nullable();
            $t->json('permissions')->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->index('customer_id');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('customer_contacts'); }
};
