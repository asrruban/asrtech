<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('discount_type');
            $table->decimal('value', 12, 2);
            $table->string('currency', 3)->nullable();
            $table->decimal('minimum_subtotal', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable()->default(1);
            $table->string('scope')->default('all');
            $table->boolean('active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_promotion_code', function (Blueprint $table) {
            $table->foreignId('promotion_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_code_id', 'product_id']);
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2)->nullable()->index();
            $table->string('state')->nullable();
            $table->decimal('rate', 8, 4);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promotion_code_id')->nullable()->after('subscription_id')->constrained()->nullOnDelete();
            $table->string('promotion_code')->nullable()->after('promotion_code_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('currency');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('setup_fee');
            $table->decimal('tax_rate', 8, 4)->nullable()->after('tax_amount');
            $table->string('tax_name')->nullable()->after('tax_rate');
        });

        DB::table('orders')->update([
            'subtotal' => DB::raw('amount'),
        ]);

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_code_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->string('status')->default('reserved')->index();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemptions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_code_id');
            $table->dropColumn([
                'promotion_code',
                'subtotal',
                'discount_amount',
                'tax_amount',
                'tax_rate',
                'tax_name',
            ]);
        });

        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('product_promotion_code');
        Schema::dropIfExists('promotion_codes');
    }
};
