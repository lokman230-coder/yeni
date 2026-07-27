<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * Admin aktivite logu — kim ne yaptı.
 * audit_logs zaten var (customer + genel) ama admin-özel actions için ayrı tablo daha temiz.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id')->nullable();
            $t->string('admin_email', 191)->nullable();
            $t->string('action', 100);      // 'created', 'updated', 'deleted', 'approved', 'login' vb
            $t->string('resource_type', 64); // 'customer', 'order', 'payout', 'coupon' vb
            $t->foreignId('resource_id')->nullable();
            $t->string('summary', 500)->nullable();
            $t->longText('meta_json')->nullable();
            $t->string('ip', 45)->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['admin_id', 'created_at']);
            $t->index(['resource_type', 'resource_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('admin_activity_logs'); }
};
