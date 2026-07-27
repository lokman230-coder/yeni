<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('api_logs', function (Blueprint $t) {
            $t->id();
            $t->string('integration', 64);
            $t->string('endpoint', 500);
            $t->string('method', 10)->default('GET');
            $t->text('request_body')->nullable();
            $t->text('response_body')->nullable();
            $t->integer('http_code')->nullable();
            $t->integer('duration_ms')->nullable();
            $t->text('error')->nullable();
            $t->string('related_entity_type', 100)->nullable();
            $t->foreignId('related_entity_id')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['integration', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('api_logs'); }
};
