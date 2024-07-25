<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('project_categories')->onDelete('cascade');
            $table->string('name');
            $table->longText('description');
            $table->integer('year');
            $table->string('client');
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('favorite')->default(false);
            $table->integer('order')->default(0);
            $table->text('image_main');
            $table->text('image_preview');
            $table->text('image_770x500_1')->nullable();
            $table->text('image_770x500_2')->nullable();
            $table->text('image_370x600')->nullable();
            $table->text('image_370x400')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
