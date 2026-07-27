<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->string('description', 500)->nullable();
            $t->string('icon', 100)->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(1);
            $t->string('seo_title', 191)->nullable();
            $t->string('seo_description', 255)->nullable();
            $t->timestamps();
            $t->unique('slug');
        });
    }
    public function down(): void { Schema::dropIfExists('product_groups'); }
};
