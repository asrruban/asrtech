<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where the license is installed, WHMCS licensing-addon style:
        // recorded on activation, cleared by a reissue.
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('expires_at');
            $table->string('path')->nullable()->after('domain');
            $table->string('ip_address', 45)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['domain', 'path', 'ip_address']);
        });
    }
};
