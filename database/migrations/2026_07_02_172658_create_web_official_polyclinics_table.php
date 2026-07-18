<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_official_polyclinics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kd_poli', 20)->unique();
            $table->string('slug', 200)->unique();
            $table->string('label', 80)->default('KLINIK SPESIALIS');
            $table->string('simrs_name', 200);
            $table->string('name_override', 200)->nullable();
            $table->string('short_description', 500);
            $table->longText('long_description')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_official_polyclinics');
    }
};
