<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->decimal('rate', 6, 3);
            $t->char('country', 2)->nullable();
            $t->enum('apply_type', ['inclusive','exclusive'])->default('exclusive');
            $t->boolean('is_active')->default(1);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tax_rules');
    }
};
