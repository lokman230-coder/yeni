<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->nullable();
            $t->enum('type', [
                'hosting','vps','dedicated','domain','ssl','email_hosting',
                'radio_hosting','site_builder','mobile_builder','web_design',
                'mobile_app','digital_service','license','marketplace','custom'
            ])->default('hosting');
            $t->string('name', 191);
            $t->string('slug', 191);
            $t->string('short_description', 500)->nullable();
            $t->longText('description')->nullable();
            $t->string('image', 255)->nullable();
            $t->enum('status', ['active','hidden','disabled'])->default('active');
            $t->enum('stock_type', ['unlimited','limited'])->default('unlimited');
            $t->integer('stock_qty')->nullable();
            $t->enum('payment_type', ['free','onetime','recurring'])->default('recurring');
            $t->decimal('setup_fee', 14, 4)->default(0);
            $t->char('setup_fee_currency', 3)->default('TRY');
            $t->foreignId('tax_rule_id')->nullable();
            $t->string('automation_module', 64)->nullable();
            $t->foreignId('server_group_id')->nullable();
            $t->json('free_domain_rules')->nullable();
            $t->string('seo_title', 191)->nullable();
            $t->string('seo_description', 255)->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->unique('slug');
            $t->index(['type', 'status']);
            $t->index('group_id');
            $t->foreign('group_id', 'product_groups', 'id', 'SET NULL', 'CASCADE');
            $t->foreign('tax_rule_id', 'tax_rules', 'id', 'SET NULL', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
