<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_provider')->nullable()->after('password');
            $table->string('social_provider_id')->nullable()->after('social_provider');
            $table->unique(['social_provider', 'social_provider_id']);
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['social_provider', 'social_provider_id']);
            $table->dropColumn(['social_provider', 'social_provider_id']);
        });
    }
};
