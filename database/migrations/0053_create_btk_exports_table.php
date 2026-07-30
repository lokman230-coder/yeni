<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('btk_exports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id')->nullable();
            $t->enum('type', ['hosting','domains','customers','all'])->default('all');
            $t->string('file_path', 500);
            $t->integer('row_count')->default(0);
            $t->integer('size_bytes')->default(0);
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['type','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('btk_exports'); }
};
