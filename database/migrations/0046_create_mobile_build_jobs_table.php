<?php
use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;
return new class extends Migration { public function up(): void { Schema::create('mobile_build_jobs', function(Blueprint $t){$t->id();$t->foreignId('customer_id')->nullable();$t->foreignId('project_id')->nullable();$t->string('build_type',20)->default('apk');$t->string('status',20)->default('queued');$t->unsignedInteger('progress')->default(0);$t->string('worker_job_id',100)->nullable();$t->string('output_path',500)->nullable();$t->text('error_log')->nullable();$t->dateTime('started_at')->nullable();$t->dateTime('completed_at')->nullable();$t->timestamps();$t->index(['customer_id','status']);}); } public function down(): void { Schema::dropIfExists('mobile_build_jobs'); }};