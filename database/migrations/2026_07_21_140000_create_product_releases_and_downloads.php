<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('version', 100);
            $table->string('title')->nullable();
            $table->text('release_notes')->nullable();
            $table->string('disk')->default('local');
            $table->string('file_path', 2000);
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->char('checksum_sha256', 64);
            $table->timestamp('released_at')->index();
            $table->timestamp('available_until')->nullable()->index();
            $table->unsignedInteger('download_limit')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'version']);
        });

        Schema::create('product_release_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('downloaded_at');

            $table->index(['license_id', 'product_release_id']);
            $table->index(['product_release_id', 'downloaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_release_downloads');
        Schema::dropIfExists('product_releases');
    }
};
