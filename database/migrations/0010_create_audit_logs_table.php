<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('user_type', 32)->nullable();
            $t->foreignId('user_id')->nullable();
            $t->string('action', 120);
            $t->string('entity_type', 120)->nullable();
            $t->foreignId('entity_id')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['entity_type', 'entity_id']);
            $t->index(['user_type', 'user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};
