<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tokenized card storage in the spirit of WHMCS tblpaymethods:
        // brand, last four, and expiry for display, plus the gateway's
        // customer/payment-method token (encrypted). Full card numbers
        // are never stored.
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->string('type')->default('card');
            $table->string('card_brand')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->unsignedTinyInteger('card_expiry_month')->nullable();
            $table->unsignedSmallInteger('card_expiry_year')->nullable();
            $table->text('token')->nullable();
            $table->string('name_on_card')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
