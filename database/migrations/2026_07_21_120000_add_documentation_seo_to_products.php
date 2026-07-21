<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('documentation_title')->nullable()->after('documentation_url');
            $table->string('documentation_meta_title')->nullable()->after('documentation_content');
            $table->text('documentation_meta_description')->nullable()->after('documentation_meta_title');
            $table->text('documentation_keywords')->nullable()->after('documentation_meta_description');
            $table->string('documentation_robots')->default('index,follow')->after('documentation_keywords');
            $table->string('documentation_open_graph_image', 2000)->nullable()->after('documentation_robots');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'documentation_title',
                'documentation_meta_title',
                'documentation_meta_description',
                'documentation_keywords',
                'documentation_robots',
                'documentation_open_graph_image',
            ]);
        });
    }
};
