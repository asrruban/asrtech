<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 50);
            $table->string('gateway_dispute_id');
            $table->string('payment_intent_id')->nullable()->index();
            $table->string('charge_id')->nullable()->index();
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status')->index();
            $table->string('reason')->nullable();
            $table->string('gateway_reason')->nullable();
            $table->timestamp('evidence_due_at')->nullable()->index();
            $table->boolean('has_evidence')->default(false);
            $table->boolean('evidence_past_due')->default(false);
            $table->unsignedInteger('submission_count')->default(0);
            $table->boolean('livemode')->default(false);
            $table->text('admin_note')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'gateway_dispute_id']);
        });

        Schema::create('payment_dispute_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_dispute_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_event_id')->unique();
            $table->string('event_type');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('payment_dispute_license_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status');
            $table->string('action');
            $table->timestamp('acted_at');
            $table->timestamps();
            $table->unique(['payment_dispute_id', 'license_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_dispute_license_actions');
        Schema::dropIfExists('payment_dispute_events');
        Schema::dropIfExists('payment_disputes');
    }
};
