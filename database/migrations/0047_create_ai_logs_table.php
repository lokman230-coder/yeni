<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_logs', function (Blueprint $t) {
            $t->id();
            $t->enum('context', ['public','customer','admin'])->default('public');
            $t->string('user_type', 32)->nullable();
            $t->foreignId('user_id')->nullable();
            $t->text('prompt');
            $t->text('response')->nullable();
            $t->string('action_taken', 191)->nullable();
            $t->integer('tokens_used')->nullable();
            $t->integer('latency_ms')->nullable();
            $t->text('error')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['context','created_at']);
            $t->index(['user_type','user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('ai_logs'); }
};
