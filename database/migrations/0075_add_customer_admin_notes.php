<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

/**
 * customers.admin_notes — sadece admin görür, müşteriye açık değil.
 * WHMCS/Blesta parity.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->text('admin_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropColumn('admin_notes');
        });
    }
};
