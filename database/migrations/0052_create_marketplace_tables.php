<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('marketplace_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable();
            $t->string('name', 100);
            $t->string('slug', 100);
            $t->string('icon', 50)->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->unique('slug');
        });

        Schema::create('marketplace_listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id');
            $t->foreignId('category_id')->nullable();
            $t->string('title', 191);
            $t->string('slug', 191);
            $t->longText('description')->nullable();
            $t->decimal('price', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->json('images')->nullable();
            $t->json('attributes')->nullable();
            $t->enum('status', ['draft','pending','active','sold','rejected'])->default('pending');
            $t->decimal('commission_rate', 5, 2)->default(5);
            $t->integer('views')->default(0);
            $t->dateTime('sold_at')->nullable();
            $t->timestamps();
            $t->unique('slug');
            $t->index(['status','category_id']);
            $t->foreign('seller_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('category_id', 'marketplace_categories', 'id', 'SET NULL', 'CASCADE');
        });

        Schema::create('marketplace_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id');
            $t->foreignId('buyer_id');
            $t->decimal('amount', 14, 4);
            $t->text('message')->nullable();
            $t->enum('status', ['pending','accepted','rejected','cancelled'])->default('pending');
            $t->timestamps();
            $t->index(['listing_id','status']);
            $t->foreign('listing_id', 'marketplace_listings', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('buyer_id',   'customers', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void {
        Schema::dropIfExists('marketplace_offers');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('marketplace_categories');
    }
};
