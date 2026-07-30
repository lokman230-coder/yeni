<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cookie_analytics_events', function (Blueprint $t) {
            $t->id();
            $t->string('session_hash', 64);
            $t->enum('event_type', ['pageview','click','cart_add','cart_abandon','builder_use','tool_use']);
            $t->json('event_data')->nullable();
            $t->string('url', 500)->nullable();
            $t->string('referrer', 500)->nullable();
            $t->string('user_agent_hash', 64)->nullable();
            $t->string('ip_hash', 64)->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index(['event_type', 'created_at']);
            $t->index('session_hash');
        });
    }
    public function down(): void {
        Schema::dropIfExists('cookie_analytics_events');
    }
};
