<?php
use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;
return new class extends Migration { public function up(): void { Schema::table('mobile_build_jobs', function(Blueprint $t){$t->foreignId('invoice_id')->nullable();$t->index('invoice_id');$t->foreign('invoice_id','invoices','id','SET NULL','CASCADE');}); } public function down(): void { Schema::table('mobile_build_jobs', function(Blueprint $t){$t->dropForeign('invoice_id');$t->dropColumn('invoice_id');}); }};