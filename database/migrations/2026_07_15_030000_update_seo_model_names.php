<?php

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('seo_metadata')
            ->where('seoable_type', 'App\\Modules\\Catalog\\Models\\Product')
            ->update(['seoable_type' => Product::class]);

        DB::table('seo_metadata')
            ->where('seoable_type', 'App\\Modules\\Pages\\Models\\Page')
            ->update(['seoable_type' => Page::class]);
    }

    public function down(): void
    {
        DB::table('seo_metadata')
            ->where('seoable_type', Product::class)
            ->update(['seoable_type' => 'App\\Modules\\Catalog\\Models\\Product']);

        DB::table('seo_metadata')
            ->where('seoable_type', Page::class)
            ->update(['seoable_type' => 'App\\Modules\\Pages\\Models\\Page']);
    }
};
