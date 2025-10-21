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
         Schema::create('wp_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_site_id');
            $table->unsignedBigInteger('user_id');
            $table->string('original_filename');
            $table->string('local_path');
            $table->unsignedBigInteger('wp_media_id')->nullable();
            $table->text('wp_url')->nullable();
            $table->enum('upload_status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['wp_site_id', 'upload_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_media');
    }
};
