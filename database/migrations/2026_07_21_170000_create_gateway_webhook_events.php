<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('external_id');
            $table->string('event_type')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->json('payload')->nullable();
            $table->json('headers')->nullable();
            $table->char('payload_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'external_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_webhook_events');
    }
};
