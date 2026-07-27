<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cms_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 191);
            $t->string('title', 191);
            $t->longText('content')->nullable();
            $t->string('seo_title', 191)->nullable();
            $t->string('seo_description', 255)->nullable();
            $t->boolean('is_published')->default(1);
            $t->timestamps();
            $t->unique('slug');
        });
    }
    public function down(): void {
        Schema::dropIfExists('cms_pages');
    }
};
