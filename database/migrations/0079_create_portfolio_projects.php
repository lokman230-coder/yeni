<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Portfolio / Referans Projeler — Ahost Bilişim'in yaptığı işlerin galerisi.
 * Site tarafında /referanslar sayfasında listelenir.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('portfolio_projects', function (Blueprint $t) {
            $t->id();
            $t->string('title', 191);
            $t->string('slug', 191);
            $t->string('client_name', 191)->nullable();
            $t->enum('category', ['web','mobile','ecommerce','corporate','landing','custom','saas','marketplace','portfolio'])->default('web');
            $t->string('sector', 100)->nullable();       // restoran, klinik, e-ticaret vb.
            $t->text('description')->nullable();
            $t->text('challenge')->nullable();            // Sorun ne idi
            $t->text('solution')->nullable();             // Nasıl çözüldü
            $t->string('preview_url', 500)->nullable();  // Canlı site linki
            $t->string('thumbnail', 500)->nullable();    // Kapak görseli (data URI ya da yol)
            $t->text('gallery')->nullable();              // JSON: ekstra ekran görüntüleri
            $t->text('technologies')->nullable();         // JSON: ["PHP","Laravel","Vue"]
            $t->string('customer_quote', 500)->nullable();
            $t->string('customer_avatar', 500)->nullable();
            $t->integer('duration_days')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_featured')->default(0);
            $t->boolean('is_published')->default(1);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->index(['is_published','sort_order']);
            $t->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_projects');
    }
};
