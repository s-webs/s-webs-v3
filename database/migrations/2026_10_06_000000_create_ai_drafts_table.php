<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource', 32);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('source_hash', 64);
            $table->json('suggestions');
            $table->string('model', 100);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['resource', 'item_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_drafts');
    }
};
