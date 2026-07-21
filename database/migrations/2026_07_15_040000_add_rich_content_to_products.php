<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('badge')->nullable()->after('type');
            $table->string('version')->nullable()->after('badge');
            $table->date('release_date')->nullable()->after('version');
            $table->string('compatibility')->nullable()->after('release_date');
            $table->string('php_compatibility')->nullable()->after('compatibility');
            $table->string('purchase_url', 2000)->nullable()->after('documentation_url');
            $table->string('trial_url', 2000)->nullable()->after('purchase_url');
            $table->longText('documentation_content')->nullable()->after('trial_url');
            $table->json('gallery')->nullable()->after('documentation_content');
            $table->json('feature_groups')->nullable()->after('gallery');
            $table->json('requirements')->nullable()->after('feature_groups');
            $table->json('changelog')->nullable()->after('requirements');
            $table->json('addons')->nullable()->after('changelog');
            $table->json('reviews')->nullable()->after('addons');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->string('name')->nullable()->after('billing_cycle');
            $table->text('description')->nullable()->after('name');
            $table->string('purchase_url', 2000)->nullable()->after('setup_fee');
            $table->json('features')->nullable()->after('purchase_url');
            $table->boolean('featured')->default(false)->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'purchase_url',
                'features',
                'featured',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'badge',
                'version',
                'release_date',
                'compatibility',
                'php_compatibility',
                'purchase_url',
                'trial_url',
                'documentation_content',
                'gallery',
                'feature_groups',
                'requirements',
                'changelog',
                'addons',
                'reviews',
            ]);
        });
    }
};
