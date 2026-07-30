<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('builder_revisions')) {
            return;
        }

        Schema::create('builder_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id');
            $t->foreignId('page_id')->nullable();
            $t->foreignId('customer_id')->nullable();
            $t->string('revision_type', 32)->default('tree');
            $t->longText('snapshot_json')->nullable();
            $t->string('label', 191)->nullable();
            $t->timestamps();
            $t->index(['project_id', 'page_id']);
            $t->index(['project_id', 'revision_type']);
            $t->foreign('project_id', 'builder_projects', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_revisions');
    }
};
