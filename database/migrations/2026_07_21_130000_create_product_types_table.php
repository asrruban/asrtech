<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        $defaults = [
            ['name' => 'WHMCS Modules', 'key' => 'whmcs_module', 'slug' => 'whmcs'],
            ['name' => 'Templates', 'key' => 'template', 'slug' => 'templates'],
            ['name' => 'Web Development', 'key' => 'web_development', 'slug' => 'web-development'],
            ['name' => 'Licenses', 'key' => 'license', 'slug' => 'licenses'],
            ['name' => 'Digital Products', 'key' => 'other_digital', 'slug' => 'digital-products'],
            ['name' => 'General Products', 'key' => 'standard', 'slug' => 'general'],
        ];

        $knownKeys = array_column($defaults, 'key');
        $knownSlugs = array_column($defaults, 'slug');
        $existingKeys = DB::table('products')->distinct()->pluck('type');

        foreach ($existingKeys as $key) {
            $key = (string) $key;

            if ($key === '' || in_array($key, $knownKeys, true)) {
                continue;
            }

            $slugBase = Str::slug($key) ?: 'product-type';
            $slug = $slugBase;
            $counter = 2;

            while (in_array($slug, $knownSlugs, true)) {
                $slug = "{$slugBase}-{$counter}";
                $counter++;
            }

            $defaults[] = [
                'name' => Str::of($key)->replace(['-', '_'], ' ')->title()->toString(),
                'key' => $key,
                'slug' => $slug,
            ];
            $knownSlugs[] = $slug;
        }

        $timestamp = now();

        DB::table('product_types')->insert(array_map(
            fn (array $type): array => [
                ...$type,
                'description' => null,
                'status' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $defaults,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
