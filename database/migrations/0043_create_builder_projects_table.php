<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('builder_projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->nullable();
            $t->string('guest_token', 64)->nullable();
            $t->enum('kind', ['site','mobile'])->default('site');
            $t->foreignId('template_id')->nullable();
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->string('sector', 50)->nullable();
            $t->foreignId('package_id')->nullable();
            $t->json('settings')->nullable(); // logo, colors, fonts, app_name...
            $t->enum('status', ['draft','published','exported'])->default('draft');
            $t->string('published_url', 255)->nullable();
            $t->dateTime('last_export_at')->nullable();
            $t->timestamps();
            $t->unique(['customer_id','slug']);
            $t->index('guest_token');
            $t->index(['customer_id','kind','status']);
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $t->foreign('template_id', 'builder_templates', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('builder_projects'); }
};
