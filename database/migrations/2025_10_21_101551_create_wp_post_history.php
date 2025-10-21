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
        Schema::create('wp_post_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('action', ['created', 'updated', 'pushed', 'published', 'unpublished', 'deleted', 'failed'])->default('created');
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['wp_post_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_post_history');
    }
};
