<?php

use App\Core\Database\Blueprint;
use App\Core\Database\Migration;
use App\Core\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('marketplace_files')) {
            Schema::create('marketplace_files', function (Blueprint $t) {
                $t->id();
                $t->foreignId('listing_id');
                $t->string('version', 32)->default('1.0.0');
                $t->string('file_name', 255);
                $t->string('file_path', 500);
                $t->string('checksum_sha256', 64)->nullable();
                $t->longText('changelog')->nullable();
                $t->boolean('is_active')->default(1);
                $t->timestamps();
                $t->index(['listing_id', 'is_active']);
                $t->foreign('listing_id', 'marketplace_listings', 'id', 'CASCADE', 'CASCADE');
            });
        }

        if (!Schema::hasTable('marketplace_purchases')) {
            Schema::create('marketplace_purchases', function (Blueprint $t) {
                $t->id();
                $t->foreignId('listing_id');
                $t->foreignId('buyer_id');
                $t->foreignId('order_id')->nullable();
                $t->foreignId('invoice_id')->nullable();
                $t->decimal('amount', 14, 4)->default(0);
                $t->char('currency', 3)->default('TRY');
                $t->string('status', 32)->default('pending');
                $t->timestamps();
                $t->index(['buyer_id', 'status']);
                $t->index('listing_id');
            });
        }

        if (!Schema::hasTable('marketplace_download_tokens')) {
            Schema::create('marketplace_download_tokens', function (Blueprint $t) {
                $t->id();
                $t->foreignId('purchase_id');
                $t->foreignId('file_id');
                $t->foreignId('customer_id');
                $t->string('token_hash', 128);
                $t->dateTime('expires_at');
                $t->integer('download_count')->default(0);
                $t->integer('max_downloads')->default(5);
                $t->dateTime('last_downloaded_at')->nullable();
                $t->timestamps();
                $t->unique('token_hash');
                $t->index(['customer_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_download_tokens');
        Schema::dropIfExists('marketplace_purchases');
        Schema::dropIfExists('marketplace_files');
    }
};
