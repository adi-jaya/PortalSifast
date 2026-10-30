<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('category', 100);
            $table->text('url');
            $table->string('url_pattern', 255)->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('auth_type', ['shared', 'personal', 'both'])->default('both');
            $table->string('shared_username', 255)->nullable();
            $table->text('shared_password')->nullable();
            $table->json('shared_extra_fields')->nullable();
            $table->json('form_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
