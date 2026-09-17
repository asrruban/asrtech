<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('platform', 30);
            $table->text('summary');
            $table->text('scope');
            $table->text('exclusions')->nullable();
            $table->text('support_arrangements')->nullable();
            $table->foreignId('product_price_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('published')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('plan_snapshot');
            $table->string('website', 2048)->nullable();
            $table->text('requirements');
            $table->string('status', 30)->default('requested')->index();
            $table->text('client_update')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('scope_acknowledged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('maintenance_plans');
    }
};
