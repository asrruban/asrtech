<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WHMCS-style email templates (tblemailtemplates): system rows are
        // referenced by slug from mailables and cannot be deleted, only edited.
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('category')->default('general');
            $table->string('subject');
            $table->longText('body');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('email_templates')->insert([
            [
                'name' => 'Email Verification Code',
                'slug' => 'email-otp',
                'category' => 'general',
                'subject' => '{{otp_code}} is your {{company_name}} verification code',
                'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#5b6472;">
    Use this code to verify your email address. It expires in {{otp_expiry_minutes}} minutes.
</p>
<p style="margin:0 0 24px;text-align:center;">
    <span style="display:inline-block;background:#eff9ef;border:1px solid #4fb250;border-radius:10px;padding:14px 28px;font-size:30px;font-weight:700;letter-spacing:8px;color:#357e37;">{{otp_code}}</span>
</p>
<p style="margin:0;font-size:13px;line-height:1.7;color:#8b93a0;">
    If you did not create an account, you can safely ignore this email.
</p>
HTML,
                'enabled' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Invoice Notification',
                'slug' => 'invoice-notification',
                'category' => 'invoice',
                'subject' => 'Invoice {{invoice_number}} from {{company_name}}',
                'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
    Your invoice is ready. Please find the details below and the PDF attached.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
    <tr>
        <td style="padding:18px 20px;">
            <p style="margin:0;font-size:13px;color:#737980;">Invoice</p>
            <p style="margin:2px 0 12px;font-size:15px;font-weight:700;color:#2e3442;">{{invoice_number}}</p>
            <p style="margin:0;font-size:13px;color:#737980;">{{product_name}}</p>
            <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#2e3442;">{{invoice_total}}</p>
            <p style="margin:10px 0 0;font-size:13px;color:#8a6116;">{{invoice_due_date}}</p>
        </td>
    </tr>
</table>
<p style="margin:24px 0 0;font-size:12px;line-height:1.7;color:#8b93a0;">
    Questions? Reply to this email or contact {{support_email}}.
</p>
HTML,
                'enabled' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
