<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('email', 191);
            $t->string('password_hash', 255);
            $t->string('first_name', 100)->nullable();
            $t->string('last_name', 100)->nullable();
            $t->string('phone', 32)->nullable();
            $t->string('company', 191)->nullable();
            $t->string('tax_id', 32)->nullable();
            $t->string('tax_office', 120)->nullable();
            $t->boolean('is_individual')->default(1);
            $t->char('country', 2)->nullable();
            $t->string('city', 100)->nullable();
            $t->text('address')->nullable();
            $t->string('postcode', 20)->nullable();
            $t->string('preferred_language', 5)->default('tr');
            $t->char('preferred_currency', 3)->default('TRY');
            $t->decimal('balance', 14, 4)->default(0);
            $t->enum('status', ['active','suspended','pending','closed'])->default('pending');
            $t->dateTime('email_verified_at')->nullable();
            $t->boolean('two_factor_enabled')->default(0);
            $t->dateTime('last_login_at')->nullable();
            $t->string('last_login_ip', 45)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique('email');
            $t->index('status');
            $t->index('country');
        });
    }
    public function down(): void {
        Schema::dropIfExists('customers');
    }
};
