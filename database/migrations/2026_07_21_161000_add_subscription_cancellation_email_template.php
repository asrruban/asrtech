<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('email_templates')->insertOrIgnore([
            'name' => 'Subscription Cancellation Scheduled',
            'slug' => 'subscription-cancellation-scheduled',
            'category' => 'invoice',
            'subject' => 'Cancellation scheduled for {{product_name}}',
            'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
    Automatic renewal for {{product_name}} has been turned off. Your service remains active until {{service_end_date}}.
</p>
<p style="margin:24px 0 0;font-size:13px;line-height:1.7;">
    Changed your mind? <a href="{{subscriptions_url}}" style="color:#357e37;font-weight:700;">Restore automatic renewal</a>
</p>
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
            ->where('slug', 'subscription-cancellation-scheduled')
            ->delete();
    }
};
