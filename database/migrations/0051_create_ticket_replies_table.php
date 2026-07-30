<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket_replies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id');
            $t->enum('author_type', ['customer','admin','system']);
            $t->foreignId('author_id')->nullable();
            $t->longText('message');
            $t->json('attachments')->nullable();
            $t->boolean('is_internal')->default(0);
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index('ticket_id');
            $t->foreign('ticket_id', 'tickets', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('ticket_replies'); }
};
