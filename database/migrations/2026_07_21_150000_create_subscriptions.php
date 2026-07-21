<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_price_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('license_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gateway')->index();
            $table->string('gateway_customer_id')->nullable()->index();
            $table->string('gateway_subscription_id')->nullable()->index();
            $table->string('status')->default('incomplete')->index();
            $table->string('billing_cycle');
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable()->index();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('product_price_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');
            $table->string('gateway_event_id');
            $table->string('event_type')->index();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['gateway', 'gateway_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });

        Schema::dropIfExists('subscriptions');
    }
};
