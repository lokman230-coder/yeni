<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('currency_rates', function (Blueprint $t) {
            $t->id();
            $t->char('currency', 3);
            $t->decimal('rate', 14, 6);
            $t->decimal('margin_percent', 6, 3)->default(0);
            $t->enum('source', ['manual','api'])->default('manual');
            $t->dateTime('updated_at')->nullable();
            $t->dateTime('created_at')->default('CURRENT_TIMESTAMP', true);
            $t->unique('currency');
        });
    }
    public function down(): void {
        Schema::dropIfExists('currency_rates');
    }
};
