<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_custom_fields', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id');
            $t->string('label', 191);
            $t->string('field_key', 100);
            $t->enum('field_type', ['text','textarea','number','ip','url','email','phone','select','radio','checkbox','file','image','password'])->default('text');
            $t->json('options')->nullable();
            $t->boolean('is_required')->default(0);
            $t->boolean('is_active')->default(1);
            $t->enum('show_on', ['order','profile','admin_only'])->default('order');
            $t->json('validation_rules')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index('product_id');
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('product_custom_fields'); }
};
