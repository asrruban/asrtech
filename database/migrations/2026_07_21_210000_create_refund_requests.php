<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('request_number')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->text('reason');
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('email_templates')->insertOrIgnore([
            [
                'name' => 'Refund Request Received',
                'slug' => 'refund-request-received',
                'category' => 'invoice',
                'subject' => 'Refund request {{request_number}} received',
                'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">We received your refund request and our billing team will review it.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;"><tr><td style="padding:18px 20px;">
<p style="margin:0;font-size:13px;color:#737980;">Request {{request_number}} · Invoice {{invoice_number}}</p>
<p style="margin:5px 0 0;font-size:22px;font-weight:700;color:#2e3442;">{{request_amount}}</p>
</td></tr></table>
<p style="margin:24px 0 0;font-size:13px;"><a href="{{invoice_url}}" style="color:#357e37;font-weight:700;">Track this request</a></p>
HTML,
                'enabled' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Refund Request Decision',
                'slug' => 'refund-request-decision',
                'category' => 'invoice',
                'subject' => 'Refund request {{request_number}} {{request_status}}',
                'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">Your refund request has been <strong>{{request_status}}</strong>.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;"><tr><td style="padding:18px 20px;">
<p style="margin:0;font-size:13px;color:#737980;">Request {{request_number}} · Invoice {{invoice_number}}</p>
<p style="margin:5px 0 12px;font-size:22px;font-weight:700;color:#2e3442;">{{request_amount}}</p>
<p style="margin:0;font-size:13px;color:#5b6472;">{{decision_note}}</p>
</td></tr></table>
<p style="margin:24px 0 0;font-size:13px;"><a href="{{invoice_url}}" style="color:#357e37;font-weight:700;">View invoice and request</a></p>
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
        DB::table('email_templates')
            ->whereIn('slug', ['refund-request-received', 'refund-request-decision'])
            ->delete();
        Schema::dropIfExists('refund_requests');
    }
};
