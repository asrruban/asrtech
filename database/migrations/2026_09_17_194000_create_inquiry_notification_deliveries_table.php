<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('recipient');
            $table->string('sender');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['project_inquiry_id', 'kind']);
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_notification_deliveries');
    }
};
