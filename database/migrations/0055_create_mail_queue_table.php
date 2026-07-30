<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mail_queue', function (Blueprint $t) {
            $t->id();
            $t->string('to_email', 191);
            $t->string('to_name', 191)->nullable();
            $t->string('subject', 255);
            $t->longText('body_html');
            $t->longText('body_text')->nullable();
            $t->string('template_key', 100)->nullable();
            $t->enum('status', ['pending','sending','sent','failed'])->default('pending');
            $t->integer('attempts')->default(0);
            $t->text('error')->nullable();
            $t->dateTime('sent_at')->nullable();
            $t->dateTime('scheduled_at')->nullable();
            $t->timestamps();
            $t->index(['status','scheduled_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('mail_queue'); }
};
