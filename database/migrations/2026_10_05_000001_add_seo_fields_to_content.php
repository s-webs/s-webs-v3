<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['project_categories', 'projects', 'prices', 'teams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if ($tableName !== 'prices') {
                    $table->string('seo_title')->nullable();
                    $table->text('seo_description')->nullable();
                    $table->text('seo_keywords')->nullable();
                }
                $table->string('seo_h1')->nullable();
                $table->string('seo_canonical', 2048)->nullable();
                $table->string('seo_robots', 32)->default('index,follow');
                $table->string('seo_og_title')->nullable();
                $table->text('seo_og_description')->nullable();
                $table->text('seo_og_image')->nullable();
                $table->string('seo_image_alt')->nullable();
            });
        }

        Schema::table('seo', function (Blueprint $table): void {
            $table->string('canonical', 2048)->nullable();
            $table->string('robots', 32)->default('index,follow');
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();
            $table->string('image_alt')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['project_categories', 'projects', 'prices', 'teams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $columns = [
                    'seo_h1', 'seo_canonical', 'seo_robots', 'seo_og_title',
                    'seo_og_description', 'seo_og_image', 'seo_image_alt',
                ];
                if ($tableName !== 'prices') {
                    array_push($columns, 'seo_title', 'seo_description', 'seo_keywords');
                }
                $table->dropColumn($columns);
            });
        }

        Schema::table('seo', function (Blueprint $table): void {
            $table->dropColumn(['canonical', 'robots', 'og_title', 'og_description', 'og_image', 'image_alt']);
        });
    }
};
