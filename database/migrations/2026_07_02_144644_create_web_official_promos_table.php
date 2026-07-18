<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_official_promos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 200)->unique();
            $table->string('title', 200);
            $table->string('label', 100)->default('PROMO SPESIAL');
            $table->string('excerpt', 500);
            $table->longText('body')->nullable();
            $table->string('cover_url', 500);
            $table->dateTimeTz('start_date');
            $table->dateTimeTz('end_date');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_official_promos');
    }
};
