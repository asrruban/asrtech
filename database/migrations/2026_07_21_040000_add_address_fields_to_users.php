<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('address_1')->nullable()->after('phone');
            $table->string('address_2')->nullable()->after('address_1');
            $table->string('city')->nullable()->after('address_2');
            $table->string('state')->nullable()->after('city');
            $table->string('postcode', 20)->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'phone',
                'address_1',
                'address_2',
                'city',
                'state',
                'postcode',
                'country',
            ]);
        });
    }
};
