<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Connection;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Import (data migration) altyapısı için 2 tablo:
 *   - import_jobs         → hangi kaynak, ne zaman, ne çekildi
 *   - import_mappings     → dış ID ↔ Ahost ID eşlemesi (duplicate önleme, delta sync)
 *
 * Ayrıca: müşteri/order/domain/hosting/ticket tablolarına 'imported_from' + 'external_id' alanları
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('source', 32);            // whmcs, wisecp, blesta
            $t->longText('config_encrypted');    // AES ile şifreli connection info
            $t->string('type', 32);              // customers, orders, invoices, ...
            $t->enum('status', ['pending','running','completed','failed'])->default('pending');
            $t->integer('total')->default(0);
            $t->integer('imported')->default(0);
            $t->integer('skipped')->default(0);
            $t->integer('errors')->default(0);
            $t->longText('error_log')->nullable();
            $t->foreignId('started_by_admin_id')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['source', 'type']);
            $t->index('status');
        });

        Schema::create('import_mappings', function (Blueprint $t) {
            $t->id();
            $t->string('source', 32);           // whmcs, wisecp
            $t->string('entity_type', 32);      // customer, order, invoice, domain, hosting, ticket, product
            $t->string('external_id', 64);      // kaynak sistemin ID'si
            $t->foreignId('local_id');          // Ahost'taki karşılığı
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique(['source', 'entity_type', 'external_id']);
            $t->index(['entity_type', 'local_id']);
        });

        // Mevcut tablolara "imported_from" + "external_id" nullable sütun ekle
        foreach (['customers', 'orders', 'invoices', 'domains', 'hosting_accounts', 'tickets', 'products'] as $tbl) {
            $cols = Connection::select("SHOW COLUMNS FROM $tbl");
            $names = array_column($cols, 'Field');
            if (!in_array('imported_from', $names, true)) {
                Connection::query("ALTER TABLE $tbl ADD COLUMN imported_from VARCHAR(32) NULL, ADD INDEX idx_{$tbl}_imported (imported_from)");
            }
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
        Schema::dropIfExists('import_jobs');
    }
};
