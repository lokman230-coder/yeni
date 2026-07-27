<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Builder Export Jobs — APK/AAB/kaynak kod build queue.
 * Site zip: anında (queue'ya gerek yok, sync).
 * Mobile APK/AAB: async — build sunucusunda (veya CI/CD ile) işlenir.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('builder_export_jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id');
            $t->foreignId('customer_id');
            $t->foreignId('invoice_id')->nullable();     // Ödeme faturası (opsiyonel)
            $t->enum('export_type', ['site_zip','mobile_apk','mobile_aab','flutter_source','react_native_source','android_source'])->default('site_zip');
            $t->enum('status', ['pending','building','ready','failed','downloaded'])->default('pending');
            $t->decimal('price', 14, 4)->default(0);      // Bu export'un ücreti (0 = ücretsiz)
            $t->char('currency', 3)->default('TRY');
            $t->string('output_path', 500)->nullable();   // storage/exports/ altındaki dosya
            $t->string('output_url', 500)->nullable();    // İndirme URL'si (imzalı token ile)
            $t->text('build_log')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('expires_at')->nullable();      // İndirme linkinin son geçerliliği
            $t->timestamps();
            $t->index('project_id');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_export_jobs');
    }
};
