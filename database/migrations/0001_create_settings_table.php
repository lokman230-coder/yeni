<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 120);
            $t->text('value')->nullable();
            $t->enum('type', ['string', 'int', 'bool', 'json', 'encrypted'])->default('string');
            $t->string('group', 64)->nullable();
            $t->boolean('is_public')->default(0);
            $t->timestamps();
            $t->unique('key');
        });
    }
    public function down(): void {
        Schema::dropIfExists('settings');
    }
};
