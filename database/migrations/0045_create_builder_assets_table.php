<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('builder_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id');
            $t->enum('kind', ['image','video','font','icon','file'])->default('image');
            $t->string('path', 500);
            $t->string('original_name', 255)->nullable();
            $t->integer('size_bytes')->default(0);
            $t->string('mime', 100)->nullable();
            $t->timestamps();
            $t->index('project_id');
            $t->foreign('project_id', 'builder_projects', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('builder_assets'); }
};
