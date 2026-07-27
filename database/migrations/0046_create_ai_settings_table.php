<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_settings', function (Blueprint $t) {
            $t->id();
            $t->string('config_key', 120);
            $t->text('config_value')->nullable();
            $t->boolean('is_encrypted')->default(0);
            $t->timestamps();
            $t->unique('config_key');
        });
    }
    public function down(): void { Schema::dropIfExists('ai_settings'); }
};
