<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_price_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('price_name')->nullable();
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->string('billing_cycle');
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
