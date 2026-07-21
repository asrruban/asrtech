<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WHMCS-style configuration store: tblconfiguration uses
        // `setting` / `value` columns; ours adds encryption at rest.
        Schema::rename('settings', 'configuration');

        Schema::table('configuration', function (Blueprint $table) {
            $table->renameColumn('key', 'setting');
        });
    }

    public function down(): void
    {
        Schema::table('configuration', function (Blueprint $table) {
            $table->renameColumn('setting', 'key');
        });

        Schema::rename('configuration', 'settings');
    }
};
