<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Package Options (Paket Opsiyonu) — Rapor Madde 5.3
 * Örnek: Lokasyon (İstanbul/Almanya/USA), Panel (cPanel/DA/Plesk),
 * OS (CentOS/Ubuntu/Debian), PHP sürümü, Lisans süresi, Tema, Mobil platform
 * Her opsiyon çoktan seçmelidir; seçili değere göre fiyat farkı vardır.
 */
return new class extends Migration {
    public function up(): void
    {
        // Opsiyon grupları: Lokasyon, Panel, PHP versiyonu vb.
        Schema::create('product_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->nullable(); // NULL = genel/tekrar kullanılabilir
            $t->string('name', 191);                 // "Lokasyon"
            $t->string('slug', 100);                 // "lokasyon"
            $t->enum('input_type', ['select','radio','checkbox'])->default('select');
            $t->boolean('is_required')->default(1);
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->string('description', 500)->nullable();
            $t->timestamps();
            $t->index(['product_id', 'is_active']);
            $t->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });

        // Opsiyon değerleri: "İstanbul (+0 TL)", "Almanya (+50 TL/ay)"
        Schema::create('product_option_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('option_id');
            $t->string('label', 191);                       // "İstanbul"
            $t->string('value_key', 100);                   // "istanbul"
            $t->decimal('price_delta', 14, 4)->default(0); // Fiyat farkı (0 = bedava)
            $t->char('currency', 3)->default('TRY');
            $t->enum('period', ['onetime','monthly','quarterly','semiannually','annually','biennially','triennially'])->default('monthly');
            $t->boolean('is_default')->default(0);
            $t->boolean('is_active')->default(1);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['option_id', 'is_active']);
            $t->foreign('option_id', 'product_options', 'id', 'CASCADE', 'CASCADE');
        });

        // Sepet/sipariş kalemine bağlı seçilen opsiyonlar
        Schema::create('cart_item_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_item_id');
            $t->foreignId('option_id');
            $t->foreignId('value_id');
            $t->string('label_snapshot', 191);         // Snapshot: sepette değişse bile aynı kalsın
            $t->string('value_snapshot', 191);
            $t->decimal('price_delta_snapshot', 14, 4)->default(0);
            $t->timestamps();
            $t->index('cart_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item_options');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
