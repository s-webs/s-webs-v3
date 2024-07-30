<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->longText('description')->after('short_description')->nullable();
            $table->string('slug')->unique()->after('link')->nullable();
            $table->string('seo_title')->nullable()->after('name')->nullable();
            $table->longText('seo_description')->after('description')->nullable();
            $table->longText('seo_keywords')->after('seo_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('slug');
            $table->dropColumn('seo_title');
            $table->dropColumn('seo_description');
            $table->dropColumn('seo_keywords');
        });
    }
};
