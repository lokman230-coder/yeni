<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('builder_templates', function (Blueprint $t) {
            $t->id();
            $t->enum('kind', ['site','mobile'])->default('site');
            $t->string('sector', 50); // hosting, agency, radio, ecommerce, restaurant, ...
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->string('description', 500)->nullable();
            $t->string('preview_image', 255)->nullable();
            $t->longText('tree_json')->nullable();
            $t->boolean('is_pro')->default(0);
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['kind','slug']);
            $t->index(['kind','sector','is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('builder_templates'); }
};
