<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_inquiries', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->date('follow_up_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('project_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('assigned_admin_id');
            $table->dropConstrainedForeignId('quote_id');
            $table->dropColumn(['internal_notes', 'follow_up_at']);
        });
    }
};
