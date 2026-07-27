<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('domain_registrars', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 100);
            $t->string('driver_class', 191);
            $t->boolean('is_active')->default(1);
            $t->boolean('is_default')->default(0);
            $t->boolean('test_mode')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique('slug');
        });
    }
    public function down(): void { Schema::dropIfExists('domain_registrars'); }
};
