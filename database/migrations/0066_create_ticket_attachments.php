<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id');
            $t->foreignId('reply_id')->nullable();
            $t->enum('uploader_type', ['customer', 'admin']);
            $t->foreignId('uploader_id');
            $t->string('original_name', 255);
            $t->string('stored_name', 128);
            $t->string('mime', 100);
            $t->integer('size_bytes');
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->index('ticket_id');
        });
    }
    public function down(): void { Schema::dropIfExists('ticket_attachments'); }
};
