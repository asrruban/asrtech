<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WHMCS-style account details: VAT number (EU customers) and the
        // weekly newsletter opt-in.
        Schema::table('users', function (Blueprint $table) {
            $table->string('vat_number', 50)->nullable()->after('country');
            $table->boolean('newsletter')->default(false)->after('vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'newsletter']);
        });
    }
};
