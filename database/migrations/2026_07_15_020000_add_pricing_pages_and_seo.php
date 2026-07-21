<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable();
            $table->string('featured_image')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->boolean('featured')->default(false)->index();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('billing_cycle')->index();
            $table->char('currency', 3)->default('USD');
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'billing_cycle']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('template')->default('default');
            $table->boolean('status')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->text('keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index,follow');
            $table->string('open_graph_title')->nullable();
            $table->string('open_graph_description', 500)->nullable();
            $table->string('open_graph_image')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->json('schema_json')->nullable();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('product_prices');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'short_description',
                'featured_image',
                'demo_url',
                'documentation_url',
                'featured',
            ]);
        });
    }
};
