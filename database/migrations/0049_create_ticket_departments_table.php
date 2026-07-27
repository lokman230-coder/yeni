<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket_departments', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('email', 191)->nullable();
            $t->boolean('is_active')->default(1);
            $t->foreignId('auto_assign_admin_id')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ticket_departments'); }
};
