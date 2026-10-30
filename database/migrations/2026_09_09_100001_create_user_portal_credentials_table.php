<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_portal_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('portal_id')->constrained('portals')->cascadeOnDelete();
            $table->enum('credential_type', ['use_shared', 'personal'])->default('use_shared');
            $table->string('personal_username', 255)->nullable();
            $table->text('personal_password')->nullable();
            $table->json('personal_extra_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'portal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_portal_credentials');
    }
};
