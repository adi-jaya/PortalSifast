<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_scan_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('suggested_kode', 20)->nullable();
            $table->string('suggested_label')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('ocr_excerpt')->nullable();
            $table->boolean('ocr_failed')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->string('nik', 20)->nullable()->index();
            $table->string('confirmed_kode', 20)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('agent_label')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_scan_inbox');
    }
};
