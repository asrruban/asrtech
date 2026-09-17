<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('email_templates')->insertOrIgnore([
            [
                'name' => 'Subscription Dunning Reminder',
                'slug' => 'subscription-dunning-reminder',
                'category' => 'invoice',
                'subject' => 'Reminder {{attempt_number}}: payment outstanding for {{product_name}}',
                'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
    The renewal payment for your {{product_name}} subscription is still outstanding. Please update your payment method to avoid losing access{{days_until_suspension}}.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;">
    <tr><td style="padding:18px 20px;">
        <p style="margin:0;font-size:13px;color:#9a3412;">Amount due</p>
        <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#7c2d12;">{{subscription_amount}}</p>
    </td></tr>
</table>
<p style="margin:24px 0 0;font-size:13px;line-height:1.7;"><a href="{{payment_method_url}}" style="color:#357e37;font-weight:700;">Update payment method</a></p>
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
        DB::table('email_templates')->where('slug', 'subscription-dunning-reminder')->delete();
    }
};
