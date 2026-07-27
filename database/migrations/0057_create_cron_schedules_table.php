<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cron_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('command', 100);
            $t->string('description', 255)->nullable();
            $t->string('expression', 100)->default('* * * * *'); // dakika saat gün ay hafta_günü
            $t->boolean('is_active')->default(1);
            $t->dateTime('last_run_at')->nullable();
            $t->dateTime('next_run_at')->nullable();
            $t->integer('last_duration_ms')->nullable();
            $t->enum('last_status', ['success','failed','running','never'])->default('never');
            $t->timestamps();
            $t->unique('command');
        });
    }
    public function down(): void { Schema::dropIfExists('cron_schedules'); }
};
