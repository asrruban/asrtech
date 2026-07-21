<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email_verified_at');
            $table->text('avatar')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['workos_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('workos_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
            $table->string('workos_id')->nullable()->unique();
        });
    }
};
