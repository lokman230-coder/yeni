<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Multi-vendor marketplace altyapısı.
 *
 * Bir vendor = bir müşteri (customer_id ile bağlı) + satıcı özellikleri.
 * Her marketplace_listing bir vendor'a bağlı.
 * Sipariş geçtiğinde: vendor'a (satış - komisyon) kredit, Ahost'a komisyon.
 */
return new class extends Migration {
    public function up(): void
    {
        // Vendor tanımı
        Schema::create('vendors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('shop_name', 191);
            $t->string('shop_slug', 191);
            $t->text('description')->nullable();
            $t->string('logo', 500)->nullable();
            $t->string('cover_image', 500)->nullable();
            $t->string('contact_email', 191)->nullable();
            $t->string('contact_phone', 32)->nullable();
            $t->string('website', 500)->nullable();
            $t->string('country', 2)->default('TR');
            $t->string('city', 100)->nullable();
            $t->string('tax_office', 120)->nullable();
            $t->string('tax_id', 32)->nullable();
            $t->string('iban', 34)->nullable();
            $t->string('iban_holder', 100)->nullable();
            $t->decimal('commission_rate', 5, 2)->default(15);   // %15 default (admin ayarlanabilir)
            $t->enum('status', ['pending','approved','suspended','rejected'])->default('pending');
            $t->text('rejection_reason')->nullable();
            $t->decimal('total_sales', 14, 4)->default(0);
            $t->decimal('total_commission_paid', 14, 4)->default(0);
            $t->decimal('pending_balance', 14, 4)->default(0);
            $t->integer('rating_count')->default(0);
            $t->decimal('rating_avg', 3, 2)->default(0);
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique('shop_slug');
            $t->index('customer_id');
            $t->index('status');
        });

        // Vendor komisyon işlemleri (her satışta 1 kayıt)
        Schema::create('vendor_earnings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id');
            $t->foreignId('order_id')->nullable();
            $t->foreignId('order_item_id')->nullable();
            $t->foreignId('listing_id')->nullable();
            $t->decimal('gross_amount', 14, 4);           // Ürün satış tutarı
            $t->decimal('commission_rate', 5, 2);
            $t->decimal('commission_amount', 14, 4);
            $t->decimal('net_earnings', 14, 4);           // Vendor'a kalan
            $t->char('currency', 3)->default('TRY');
            $t->enum('status', ['pending','available','paid_out','refunded','cancelled'])->default('pending');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('vendor_id');
            $t->index('status');
        });

        // Vendor payout istekleri (havale ile kazanç çekme)
        Schema::create('vendor_payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id');
            $t->decimal('amount', 14, 4);
            $t->char('currency', 3)->default('TRY');
            $t->string('method', 20)->default('bank_transfer');
            $t->string('iban', 34)->nullable();
            $t->string('iban_holder', 100)->nullable();
            $t->enum('status', ['requested','approved','sent','rejected','cancelled'])->default('requested');
            $t->text('notes')->nullable();
            $t->foreignId('processed_by')->nullable();      // Admin ID
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->index('vendor_id');
            $t->index('status');
        });

        // Vendor değerlendirmeleri (müşteriler puan verir)
        Schema::create('vendor_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id');
            $t->foreignId('customer_id');
            $t->foreignId('order_id')->nullable();
            $t->integer('rating');                          // 1-5
            $t->string('title', 191)->nullable();
            $t->text('comment')->nullable();
            $t->boolean('is_verified')->default(0);         // Gerçek satın alım
            $t->boolean('is_published')->default(1);
            $t->timestamps();
            $t->index('vendor_id');
        });

        // Marketplace listing'e vendor_id + komisyon kolonu ekle
        Schema::table('marketplace_listings', function (Blueprint $t) {
            $t->foreignId('vendor_id')->nullable();
            $t->decimal('commission_rate_override', 5, 2)->nullable(); // Kategori/ürün özel oran
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $t) {
            $t->dropColumn('vendor_id');
            $t->dropColumn('commission_rate_override');
        });
        Schema::dropIfExists('vendor_reviews');
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('vendor_earnings');
        Schema::dropIfExists('vendors');
    }
};
