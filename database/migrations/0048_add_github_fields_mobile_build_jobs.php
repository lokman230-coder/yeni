<?php
use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;
return new class extends Migration { public function up(): void { Schema::table('mobile_build_jobs', function(Blueprint $t){$t->unsignedBigInteger('github_run_id')->nullable();$t->unsignedBigInteger('github_artifact_id')->nullable();$t->string('artifact_path',500)->nullable();$t->index('github_run_id');}); } public function down(): void { Schema::table('mobile_build_jobs', function(Blueprint $t){$t->dropColumn('github_run_id');$t->dropColumn('github_artifact_id');$t->dropColumn('artifact_path');}); }};