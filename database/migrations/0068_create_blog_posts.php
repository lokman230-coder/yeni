<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('author_id')->nullable(); // admin_id
            $t->string('title', 191);
            $t->string('slug', 191);
            $t->text('excerpt')->nullable();
            $t->longText('body_html');
            $t->string('featured_image', 500)->nullable();
            $t->string('category', 64)->nullable();
            $t->string('tags', 500)->nullable();
            $t->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $t->string('seo_title', 191)->nullable();
            $t->string('seo_description', 500)->nullable();
            $t->integer('views')->default(0);
            $t->dateTime('published_at')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->dateTime('updated_at')->nullable();
            $t->unique('slug');
            $t->index(['status', 'published_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('blog_posts'); }
};
