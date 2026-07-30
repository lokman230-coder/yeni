<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->enum('user_type', ['customer','admin']);
            $t->foreignId('user_id');
            $t->string('type', 100);
            $t->string('title', 255);
            $t->text('body')->nullable();
            $t->string('url', 500)->nullable();
            $t->string('icon', 32)->default('🔔');
            $t->boolean('is_read')->default(0);
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['user_type','user_id','is_read']);
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
