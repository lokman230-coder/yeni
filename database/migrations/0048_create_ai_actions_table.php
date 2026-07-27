<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_actions', function (Blueprint $t) {
            $t->id();
            $t->string('action_key', 120);
            $t->string('label', 191);
            $t->enum('context', ['public','customer','admin'])->default('public');
            $t->string('handler_class', 191);
            $t->string('required_permission', 120)->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->unique('action_key');
            $t->index(['context','is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('ai_actions'); }
};
