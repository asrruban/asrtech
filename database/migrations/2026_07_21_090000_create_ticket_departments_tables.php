<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WHMCS-style support departments (tblticketdepartments): behaviour
        // flags are stored now and consumed by the ticket system; the mail
        // importing block backs the future POP cron import method.
        Schema::create('ticket_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('email')->nullable();
            $table->boolean('clients_only')->default(false);
            $table->boolean('pipe_replies_only')->default(false);
            $table->boolean('no_autoresponder')->default(false);
            $table->boolean('feedback_request')->default(false);
            $table->boolean('prevent_client_closure')->default(false);
            $table->boolean('hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('mail_provider')->default('pop3imap');
            $table->string('mail_hostname')->nullable();
            $table->unsignedInteger('mail_port')->default(0);
            $table->string('mail_email')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_client_id')->nullable();
            $table->text('mail_client_secret')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_ticket_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->unique(['ticket_department_id', 'admin_id']);
        });

        // Per-department custom fields shown on the ticket submission form.
        Schema::create('ticket_department_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('text');
            $table->string('description')->nullable();
            $table->string('validation')->nullable();
            $table->text('select_options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('admin_only')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_department_fields');
        Schema::dropIfExists('admin_ticket_department');
        Schema::dropIfExists('ticket_departments');
    }
};
