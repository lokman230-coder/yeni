<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_number', 32);
            $t->foreignId('department_id')->nullable();
            $t->foreignId('customer_id');
            $t->foreignId('admin_id')->nullable();
            $t->string('subject', 255);
            $t->enum('priority', ['low','medium','high','urgent'])->default('medium');
            $t->enum('status', ['open','answered','customer_reply','closed','on_hold'])->default('open');
            $t->string('related_service_type', 64)->nullable();
            $t->foreignId('related_service_id')->nullable();
            $t->dateTime('last_reply_at')->nullable();
            $t->dateTime('closed_at')->nullable();
            $t->timestamps();
            $t->unique('ticket_number');
            $t->index(['customer_id','status']);
            $t->index('status');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('department_id', 'ticket_departments', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('tickets'); }
};
