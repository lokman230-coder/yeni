<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cron_logs', function (Blueprint $t) {
            $t->id();
            $t->string('command', 100);
            $t->enum('status', ['running','success','failed'])->default('running');
            $t->dateTime('started_at')->default('CURRENT_TIMESTAMP', true);
            $t->dateTime('finished_at')->nullable();
            $t->text('output')->nullable();
            $t->text('error')->nullable();
            $t->index(['command', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('cron_logs'); }
};
