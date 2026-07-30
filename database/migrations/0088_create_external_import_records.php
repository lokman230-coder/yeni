<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_import_records', function (Blueprint $t) {
            $t->id();
            $t->string('source', 32);
            $t->string('entity_type', 64);
            $t->string('external_id', 128);
            $t->string('source_table', 128)->nullable();
            $t->longText('payload_json');
            $t->enum('map_status', ['archived','mapped','needs_review'])->default('archived');
            $t->text('notes')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->dateTime('updated_at')->nullable();
            $t->unique(['source', 'entity_type', 'external_id']);
            $t->index(['source', 'entity_type']);
            $t->index('map_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_import_records');
    }
};
