<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id');
            $t->foreignId('permission_id');
            $t->timestamps();
            $t->unique(['role_id', 'permission_id']);
            $t->foreign('role_id', 'admin_roles');
            $t->foreign('permission_id', 'admin_permissions');
        });
    }
    public function down(): void {
        Schema::dropIfExists('admin_role_permissions');
    }
};
