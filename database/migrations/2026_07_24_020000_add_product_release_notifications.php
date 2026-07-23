<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_releases', function (Blueprint $table) {
            $table->timestamp('notification_queued_at')->nullable()->index()->after('status');
            $table->timestamp('notified_at')->nullable()->index()->after('notification_queued_at');
        });

        $now = now();

        DB::table('email_templates')->insertOrIgnore([
            'name' => 'Product Release Published',
            'slug' => 'product-release-published',
            'category' => 'product',
            'subject' => '{{product_name}} {{version}} is now available',
            'body' => <<<'HTML'
<p style="margin:0 0 8px;font-size:16px;color:#2e3442;">Hi {{client_name}},</p>
<p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#5b6472;">
    A new version of {{product_name}} is available for your active license.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;border-radius:10px;">
    <tr><td style="padding:18px 20px;">
        <p style="margin:0;font-size:13px;color:#737980;">Version</p>
        <p style="margin:2px 0 12px;font-size:22px;font-weight:700;color:#2e3442;">{{version}}</p>
        <p style="margin:0;font-size:13px;color:#737980;">{{release_title}} · {{release_date}}</p>
    </td></tr>
</table>
<p style="margin:20px 0 8px;font-size:13px;font-weight:700;color:#2e3442;">What changed</p>
<p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#5b6472;">{{release_notes}}</p>
<p style="margin:0;font-size:13px;line-height:1.7;">
    <a href="{{downloads_url}}" style="color:#357e37;font-weight:700;">View release and download</a>
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
            ->where('slug', 'product-release-published')
            ->delete();

        Schema::table('product_releases', function (Blueprint $table) {
            $table->dropColumn(['notification_queued_at', 'notified_at']);
        });
    }
};
