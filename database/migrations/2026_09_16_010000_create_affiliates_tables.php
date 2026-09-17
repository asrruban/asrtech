<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 24)->unique();
            $table->decimal('commission_rate', 5, 2)->default(10);
            $table->boolean('active')->default(true)->index();
            $table->decimal('pending_balance', 12, 2)->default(0);
            $table->decimal('approved_balance', 12, 2)->default(0);
            $table->decimal('paid_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('order_total', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by_affiliate_id')
                ->nullable()
                ->after('admin_notes')
                ->constrained('affiliates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_affiliate_id');
        });

        Schema::dropIfExists('referrals');
        Schema::dropIfExists('affiliates');
    }
};
