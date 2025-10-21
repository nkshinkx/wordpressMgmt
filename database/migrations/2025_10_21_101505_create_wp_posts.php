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
        Schema::create('wp_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_site_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->unsignedBigInteger('featured_image_id')->nullable();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->enum('status', ['local_draft', 'pushed_draft', 'published', 'failed', 'out_of_sync'])->default('local_draft');
            $table->enum('wp_status', ['draft', 'publish', 'pending', 'private'])->default('draft');
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('wp_author_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['wp_site_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_posts');
    }
};
