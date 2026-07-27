<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_roles', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 100);
            $t->string('description', 255)->nullable();
            $t->boolean('is_system')->default(0);
            $t->timestamps();
            $t->unique('slug');
        });
    }
    public function down(): void {
        Schema::dropIfExists('admin_roles');
    }
};
