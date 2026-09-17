<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_compatibilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('minimum_version', 11);
            $table->string('maximum_version', 11);
            $table->unsignedInteger('minimum_version_number');
            $table->unsignedInteger('maximum_version_number');
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->index(['product_id', 'platform', 'published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_compatibilities');
    }
};
