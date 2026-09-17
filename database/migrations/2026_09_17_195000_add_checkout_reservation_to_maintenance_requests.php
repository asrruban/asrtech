<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some local previews applied these fields with the initial plan migration.
        if (! Schema::hasColumn('maintenance_requests', 'checkout_order_id')) {
            Schema::table('maintenance_requests', function (Blueprint $table): void {
                $table->foreignId('checkout_order_id')->nullable()->unique()->constrained('orders')->restrictOnDelete();
            });
        }
        if (! Schema::hasColumn('maintenance_requests', 'checkout_redirect_url')) {
            Schema::table('maintenance_requests', function (Blueprint $table): void {
                $table->text('checkout_redirect_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('checkout_order_id');
            $table->dropColumn('checkout_redirect_url');
        });
    }
};
