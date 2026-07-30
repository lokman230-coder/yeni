<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vps_provisioning_jobs')) {
            return;
        }

        Schema::create('vps_provisioning_jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hosting_account_id')->nullable();
            $t->foreignId('order_item_id')->nullable();
            $t->foreignId('customer_id')->nullable();
            $t->string('provider', 64)->default('manual');
            $t->string('plan', 191)->nullable();
            $t->longText('payload_json')->nullable();
            $t->string('status', 32)->default('queued');
            $t->string('remote_id', 191)->nullable();
            $t->text('last_error')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'provider']);
            $t->index('hosting_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_provisioning_jobs');
    }
};
