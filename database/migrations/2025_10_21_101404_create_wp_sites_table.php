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
        Schema::create('wp_sites', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('domain');
            $table->string('rest_path');
            $table->string('username');
            $table->string('password');
            $table->string('jwt_token')->nullable();
            $table->string('jwt_expires_at')->nullable();
            $table->enum('status',['active','inactive'])->default('inactive');
            $table->boolean('auto_refresh')->default(true);
            $table->text('connection_error')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_sites');
    }
};
