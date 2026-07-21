<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedInteger('reissue_count')->default(0)->after('ip_address');
        });

        // Every license check from an installation, WHMCS-style.
        Schema::create('license_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('path')->nullable();
            $table->string('result');
            $table->timestamps();

            $table->index(['license_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_access_logs');

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('reissue_count');
        });
    }
};
