<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_ruang_id')->constrained('aset_ruang')->cascadeOnDelete();
            $table->string('judul', 150)->nullable();
            $table->string('status', 20)->default('berjalan');
            $table->foreignId('dimulai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dimulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamp('disetujui_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->json('ringkasan')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['aset_ruang_id', 'status']);
        });

        Schema::create('audit_aset_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_aset_id')->constrained('audit_aset')->cascadeOnDelete();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('kode_aset', 60);
            $table->string('hasil', 30)->nullable();
            $table->string('kondisi_aktual', 30)->nullable();
            $table->foreignId('aset_ruang_ditemukan_id')->nullable()->constrained('aset_ruang')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->string('path_foto_bukti', 500)->nullable();
            $table->foreignId('dicek_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dicek_pada')->nullable();
            $table->timestamps();

            $table->unique(['audit_aset_id', 'aset_id']);
            $table->index(['audit_aset_id', 'hasil']);
            $table->index('kode_aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_aset_item');
        Schema::dropIfExists('audit_aset');
    }
};
