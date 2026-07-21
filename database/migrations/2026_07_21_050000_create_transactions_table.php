<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment ledger, WHMCS-style: one row per payment or refund
        // applied to an order and its invoice.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('payment');
            $table->string('gateway');
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('fees', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
