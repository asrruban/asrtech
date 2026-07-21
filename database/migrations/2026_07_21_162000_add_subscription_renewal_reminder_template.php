<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('email_templates')->insertOrIgnore([
            'name' => 'Subscription Renewal Reminder',
            'slug' => 'subscription-renewal-reminder',
            'category' => 'invoice',
            'subject' => 'Upcoming renewal for {{product_name}}',
            'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
    Your {{product_name}} subscription will renew on {{renewal_date}}.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
    <tr><td style="padding:18px 20px;">
        <p style="margin:0;font-size:13px;color:#737980;">Recurring charge</p>
        <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#2e3442;">{{subscription_amount}}</p>
    </td></tr>
</table>
<p style="margin:24px 0 0;font-size:13px;line-height:1.7;"><a href="{{subscriptions_url}}" style="color:#357e37;font-weight:700;">Manage subscription</a></p>
HTML,
            'enabled' => true,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->where('slug', 'subscription-renewal-reminder')
            ->delete();
    }
};
