<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Lisans yönetimi — script/uygulama satışı için (CodeCanyon uyumlu).
 * Ürün bazlı: "site_builder", "mobile_builder", "wp_plugin", "custom_script" vb.
 *
 * license_type:
 *   single_domain   → 1 domain
 *   multi_domain    → N domain (max_domains sütunu)
 *   unlimited       → sınırsız domain
 *   single_package  → 1 paket adı (Android package name vs)
 *   multi_package   → N paket
 */
return new class extends Migration {
    public function up(): void
    {
        // Lisans tanımı (ürüne + satın alma anına bağlı)
        Schema::create('licenses', function (Blueprint $t) {
            $t->id();
            $t->string('license_key', 64);        // AHOST-XXXXX-XXXXX-XXXXX-XXXXX
            $t->foreignId('customer_id');
            $t->foreignId('product_id')->nullable();
            $t->foreignId('order_id')->nullable();
            $t->foreignId('invoice_id')->nullable();
            $t->string('product_name', 191);
            $t->string('product_version', 32)->nullable();
            $t->enum('license_type', ['single_domain','multi_domain','unlimited','single_package','multi_package','trial'])->default('single_domain');
            $t->integer('max_domains')->default(1);
            $t->enum('status', ['active','suspended','expired','revoked','pending'])->default('pending');
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('expires_at')->nullable();          // null = süresiz
            $t->timestamp('last_verified_at')->nullable();
            $t->integer('verification_count')->default(0);
            $t->string('purchase_code', 64)->nullable();  // CodeCanyon envato purchase code
            $t->string('source', 32)->default('ahost');       // ahost | codecanyon | manual
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique('license_key');
            $t->unique('purchase_code');
            $t->index('customer_id');
            $t->index('status');
        });

        // Lisansın aktive olduğu domainler/paketler
        Schema::create('license_activations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('license_id');
            $t->string('identifier', 191);          // domain: "musteri.com" | package: "com.musteri.app"
            $t->enum('identifier_type', ['domain','package','ip','device_id'])->default('domain');
            $t->string('ip', 64)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->index(['license_id', 'identifier']);
            $t->index('identifier');
        });

        // Lisans doğrulama log (denetim + iptal ipuçları)
        Schema::create('license_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('license_id')->nullable();
            $t->string('license_key', 64);
            $t->string('identifier', 191)->nullable();
            $t->string('ip', 64)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->enum('result', ['valid','invalid','expired','revoked','domain_mismatch','rate_limited'])->default('valid');
            $t->text('response')->nullable();
            $t->timestamps();
            $t->index('license_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_verifications');
        Schema::dropIfExists('license_activations');
        Schema::dropIfExists('licenses');
    }
};
