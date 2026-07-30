<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('live_chat_conversations')) {
            Schema::create('live_chat_conversations', function (Blueprint $t) {
                $t->id();
                $t->string('visitor_name', 120)->nullable();
                $t->string('visitor_email', 191)->nullable();
                $t->string('visitor_ip', 64)->nullable();
                $t->foreignId('customer_id')->nullable();
                $t->string('department', 100)->default('general');
                $t->string('status', 24)->default('pending');
                $t->string('source', 32)->default('widget');
                $t->dateTime('last_message_at')->nullable();
                $t->dateTime('closed_at')->nullable();
                $t->timestamps();
                $t->index(['status', 'last_message_at']);
                $t->index('customer_id');
            });
        }

        if (!Schema::hasTable('live_chat_messages')) {
            Schema::create('live_chat_messages', function (Blueprint $t) {
                $t->id();
                $t->foreignId('conversation_id');
                $t->string('sender_type', 24)->default('visitor');
                $t->foreignId('sender_id')->nullable();
                $t->text('message');
                $t->boolean('is_read')->default(0);
                $t->timestamps();
                $t->index(['conversation_id', 'created_at']);
                $t->foreign('conversation_id', 'live_chat_conversations', 'id', 'CASCADE', 'CASCADE');
            });
        }

        if (!Schema::hasTable('form_builder_forms')) {
            Schema::create('form_builder_forms', function (Blueprint $t) {
                $t->id();
                $t->string('name', 191);
                $t->string('slug', 191);
                $t->longText('schema_json')->nullable();
                $t->string('status', 24)->default('active');
                $t->string('notify_email', 191)->nullable();
                $t->timestamps();
                $t->unique('slug');
                $t->index('status');
            });
        }

        if (!Schema::hasTable('form_builder_submissions')) {
            Schema::create('form_builder_submissions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('form_id');
                $t->longText('payload_json')->nullable();
                $t->string('submitter_email', 191)->nullable();
                $t->string('submitter_ip', 64)->nullable();
                $t->string('status', 24)->default('new');
                $t->timestamps();
                $t->index(['form_id', 'created_at']);
                $t->foreign('form_id', 'form_builder_forms', 'id', 'CASCADE', 'CASCADE');
            });
        }

        if (!Schema::hasTable('popup_builder_popups')) {
            Schema::create('popup_builder_popups', function (Blueprint $t) {
                $t->id();
                $t->string('name', 191);
                $t->string('trigger_type', 32)->default('time_delay');
                $t->longText('content_json')->nullable();
                $t->string('status', 24)->default('active');
                $t->integer('display_limit')->default(1);
                $t->timestamps();
                $t->index(['status', 'trigger_type']);
            });
        }

        if (!Schema::hasTable('popup_builder_events')) {
            Schema::create('popup_builder_events', function (Blueprint $t) {
                $t->id();
                $t->foreignId('popup_id')->nullable();
                $t->string('event_type', 32);
                $t->string('visitor_key', 128)->nullable();
                $t->string('url', 500)->nullable();
                $t->timestamps();
                $t->index(['popup_id', 'event_type']);
            });
        }

        if (!Schema::hasTable('seo_audits')) {
            Schema::create('seo_audits', function (Blueprint $t) {
                $t->id();
                $t->string('url', 500);
                $t->integer('score')->default(0);
                $t->longText('findings_json')->nullable();
                $t->timestamps();
                $t->index('score');
            });
        }

        if (!Schema::hasTable('integration_webhooks')) {
            Schema::create('integration_webhooks', function (Blueprint $t) {
                $t->id();
                $t->string('name', 191);
                $t->string('event_name', 100);
                $t->string('target_url', 500);
                $t->string('secret', 128)->nullable();
                $t->boolean('is_active')->default(1);
                $t->timestamps();
                $t->index(['event_name', 'is_active']);
            });
        }

        if (!Schema::hasTable('integration_events')) {
            Schema::create('integration_events', function (Blueprint $t) {
                $t->id();
                $t->string('event_name', 100);
                $t->longText('payload_json')->nullable();
                $t->string('delivery_status', 32)->default('queued');
                $t->integer('attempts')->default(0);
                $t->text('last_error')->nullable();
                $t->timestamps();
                $t->index(['event_name', 'delivery_status']);
            });
        }

        if (!Schema::hasTable('white_label_profiles')) {
            Schema::create('white_label_profiles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('customer_id')->nullable();
                $t->string('brand_name', 191);
                $t->string('domain', 191)->nullable();
                $t->longText('settings_json')->nullable();
                $t->string('status', 24)->default('active');
                $t->timestamps();
                $t->unique('domain');
                $t->index(['customer_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_profiles');
        Schema::dropIfExists('integration_events');
        Schema::dropIfExists('integration_webhooks');
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('popup_builder_events');
        Schema::dropIfExists('popup_builder_popups');
        Schema::dropIfExists('form_builder_submissions');
        Schema::dropIfExists('form_builder_forms');
        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('live_chat_conversations');
    }
};
