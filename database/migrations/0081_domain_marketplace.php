<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Domain marketplace + TLD gereksinimleri + Backorder + Fiyat markup.
 *
 * - tld_configs: her TLD için satış kuralları (kar marjı, belge gereksinimi vs)
 * - domain_documents: TLD'nin talep ettiği belgeler (com.tr için TCKN/vergi vb.)
 * - domain_backorders: alınmış domain için "boşalınca beni haberdar et"
 * - domain_marketplace_listings: 2. el domain satışları
 */
return new class extends Migration {
    public function up(): void
    {
        // TLD yapılandırması — admin: fiyat markup, gerekli belgeler, satılabilir mi?
        Schema::create('tld_configs', function (Blueprint $t) {
            $t->id();
            $t->string('tld', 20);                        // "com", "com.tr", "io"
            $t->string('label', 100);
            $t->foreignId('default_registrar_id')->nullable();
            $t->enum('markup_type', ['percent','fixed'])->default('percent');
            $t->decimal('markup_value', 10, 4)->default(30);   // %30 kar veya sabit ekleme
            $t->decimal('min_price', 14, 4)->nullable();  // Minimum satış fiyatı guard
            $t->boolean('requires_documents')->default(0);
            $t->text('required_documents_json')->nullable(); // ["tckn","tax_id","trademark_cert"]
            $t->boolean('allow_transfer')->default(1);
            $t->boolean('allow_backorder')->default(1);
            $t->integer('min_years')->default(1);
            $t->integer('max_years')->default(10);
            $t->integer('grace_days')->default(30);
            $t->boolean('is_popular')->default(0);
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique('tld');
        });

        // Domain siparişleri için gerekli belge upload
        Schema::create('domain_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('domain_id')->nullable();
            $t->foreignId('order_item_id')->nullable();
            $t->foreignId('customer_id');
            $t->string('document_type', 50);              // tckn, tax_id, trademark_cert, id_card, company_reg
            $t->string('document_number', 100)->nullable(); // TCKN/Vergi no
            $t->string('file_path', 500)->nullable();     // Upload edilen dosya
            $t->string('file_name', 255)->nullable();
            $t->string('file_mime', 100)->nullable();
            $t->enum('status', ['pending','approved','rejected'])->default('pending');
            $t->text('notes')->nullable();
            $t->foreignId('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index('domain_id');
            $t->index('customer_id');
        });

        // Backorder — alınmış bir domain için "boşalırsa haberdar et / otomatik yakala"
        Schema::create('domain_backorders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('domain_name', 255);
            $t->enum('mode', ['notify_only','auto_catch'])->default('notify_only');
            $t->decimal('max_bid', 14, 4)->nullable();     // auto_catch için max ödeme
            $t->char('currency', 3)->default('TRY');
            $t->date('expected_expiry')->nullable();
            $t->enum('status', ['watching','triggered','caught','failed','cancelled','expired'])->default('watching');
            $t->timestamp('triggered_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('customer_id');
            $t->index('domain_name');
        });

        // 2. el domain marketplace — kullanıcı sahibi olduğu domaini satışa çıkarır
        Schema::create('domain_marketplace_listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');                  // Satıcı
            $t->foreignId('domain_id')->nullable();        // Kendi domain kayıtlarımızdaysa
            $t->string('domain_name', 255);
            $t->decimal('price', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->enum('sale_type', ['fixed_price','auction','make_offer'])->default('fixed_price');
            $t->decimal('min_offer', 14, 4)->nullable();
            $t->text('description')->nullable();
            $t->integer('valuation_score')->nullable();    // ValuationService puanı (0-100)
            $t->decimal('estimated_value', 14, 4)->nullable();
            $t->enum('status', ['draft','pending','active','sold','cancelled','expired'])->default('draft');
            $t->foreignId('sold_to_customer_id')->nullable();
            $t->decimal('sold_price', 14, 4)->nullable();
            $t->timestamp('sold_at')->nullable();
            $t->integer('view_count')->default(0);
            $t->decimal('commission_rate', 5, 2)->default(10); // %10 komisyon default
            $t->timestamps();
            $t->index('status');
            $t->index('domain_name');
        });

        // Domain teklifleri (make_offer / auction için)
        Schema::create('domain_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id');
            $t->foreignId('customer_id');
            $t->decimal('amount', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->text('message')->nullable();
            $t->enum('status', ['pending','accepted','rejected','withdrawn','expired'])->default('pending');
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_offers');
        Schema::dropIfExists('domain_marketplace_listings');
        Schema::dropIfExists('domain_backorders');
        Schema::dropIfExists('domain_documents');
        Schema::dropIfExists('tld_configs');
    }
};
