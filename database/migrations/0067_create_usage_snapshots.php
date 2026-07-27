<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Hosting hesap kullanım geçmişi — günlük snapshot.
 * Chart.js için "son 30 gün disk + bandwidth" grafiği çizeriz.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hosting_usage_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id');
            $t->date('snap_date');
            $t->integer('disk_mb')->nullable();
            $t->integer('bandwidth_mb')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique(['account_id', 'snap_date']);
            $t->index(['account_id', 'snap_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('hosting_usage_snapshots'); }
};
