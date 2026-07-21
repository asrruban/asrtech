<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_payment_id')->nullable()->after('payment_reference')->index();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('refund_number')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('gateway');
            $table->string('gateway_reference')->nullable();
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->text('reason');
            $table->boolean('record_only')->default(false);
            $table->boolean('revoke_access')->default(false);
            $table->text('failure_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('credit_note_number')->unique();
            $table->string('currency', 3);
            $table->decimal('net_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('tax_name')->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->text('reason');
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('refunds');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('gateway_payment_id');
        });
    }
};
