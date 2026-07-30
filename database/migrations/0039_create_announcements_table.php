<?php
use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;
return new class extends Migration { public function up(): void { Schema::create('announcements', function(Blueprint $t){$t->id();$t->string('title',191);$t->string('slug',191);$t->text('content');$t->string('status',20)->default('draft');$t->dateTime('published_at')->nullable();$t->timestamps();$t->index('status');}); } public function down(): void { Schema::dropIfExists('announcements'); }};