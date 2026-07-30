<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

// "Notlar" sekmesi — WHMCS'teki gibi tarihli, admin'e özel not listesi.
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id');
            $t->string('admin_email', 191)->nullable();
            $t->text('note');
            $t->boolean('is_sticky')->default(0);
            $t->timestamps();
            $t->index('customer_id');
            $t->foreign('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        });
    }
    public function down(): void { Schema::dropIfExists('customer_notes'); }
};
