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
        Schema::create('wp_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_site_id');
            $table->unsignedBigInteger('wp_tag_id');
            $table->string('name');
            $table->string('slug');
            $table->integer('count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['wp_site_id', 'wp_tag_id']);
            $table->index('wp_site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_tags');
    }
};
