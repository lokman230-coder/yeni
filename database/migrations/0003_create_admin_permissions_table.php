<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('key', 120);
            $t->string('label', 191);
            $t->string('group', 64)->nullable();
            $t->timestamps();
            $t->unique('key');
        });
    }
    public function down(): void {
        Schema::dropIfExists('admin_permissions');
    }
};
