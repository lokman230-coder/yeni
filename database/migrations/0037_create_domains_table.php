<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('domains', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->foreignId('order_id')->nullable();
            $t->string('domain_name', 255);
            $t->foreignId('registrar_id')->nullable();
            $t->date('registration_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->date('next_due_date')->nullable();
            $t->enum('status', ['active','pending','pending_transfer','expired','cancelled','suspended'])->default('pending');
            $t->boolean('auto_renew')->default(1);
            $t->boolean('transfer_lock')->default(1);
            $t->boolean('whois_privacy')->default(0);
            $t->json('nameservers')->nullable();
            $t->string('epp_code', 191)->nullable();
            $t->integer('period_years')->default(1);
            $t->decimal('recurring_amount', 14, 4)->default(0);
            $t->char('currency', 3)->default('TRY');
            $t->timestamps();
            $t->unique('domain_name');
            $t->index(['customer_id', 'status']);
            $t->index('expiry_date');
            $t->foreign('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
            $t->foreign('registrar_id', 'domain_registrars', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('domains'); }
};
