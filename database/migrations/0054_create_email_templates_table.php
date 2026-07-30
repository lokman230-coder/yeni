<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_templates', function (Blueprint $t) {
            $t->id();
            $t->string('template_key', 100);
            $t->string('subject', 255);
            $t->longText('body_html');
            $t->longText('body_text')->nullable();
            $t->json('variables')->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamps();
            $t->unique('template_key');
        });
    }
    public function down(): void { Schema::dropIfExists('email_templates'); }
};
