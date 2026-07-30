<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('builder_pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id');
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->boolean('is_homepage')->default(0);
            $t->longText('tree_json')->nullable();
            $t->string('seo_title', 191)->nullable();
            $t->string('seo_description', 255)->nullable();
            $t->string('seo_image', 255)->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(1);
            $t->timestamps();
            $t->unique(['project_id','slug']);
            $t->foreign('project_id', 'builder_projects', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('builder_pages'); }
};
