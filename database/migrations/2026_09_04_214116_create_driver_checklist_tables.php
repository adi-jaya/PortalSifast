<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_polisi')->nullable();
            $table->string('merk')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->index('status');
            $table->index('nama');
        });

        Schema::create('driver_checklist_item', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kategori')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['aktif', 'urutan']);
        });

        Schema::create('driver_kendaraan_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_kendaraan_id')->constrained('driver_kendaraan')->cascadeOnDelete();
            $table->foreignId('driver_checklist_item_id')->constrained('driver_checklist_item')->cascadeOnDelete();
            $table->boolean('berlaku')->default(true);
            $table->timestamps();

            $table->unique(['driver_kendaraan_id', 'driver_checklist_item_id'], 'driver_kendaraan_item_unique');
        });

        Schema::create('driver_pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_kendaraan_id')->constrained('driver_kendaraan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedTinyInteger('pemeriksaan_ke');
            $table->dateTime('waktu_pemeriksaan');
            $table->string('status', 20)->default('selesai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(
                ['driver_kendaraan_id', 'tanggal', 'pemeriksaan_ke'],
                'driver_pemeriksaan_unik'
            );
            $table->index(['tanggal', 'status']);
            $table->index('petugas_id');
        });

        Schema::create('driver_pemeriksaan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_pemeriksaan_id')->constrained('driver_pemeriksaan')->cascadeOnDelete();
            $table->foreignId('driver_checklist_item_id')->constrained('driver_checklist_item')->restrictOnDelete();
            $table->string('hasil', 20);
            $table->text('temuan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['driver_pemeriksaan_id', 'hasil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_pemeriksaan_detail');
        Schema::dropIfExists('driver_pemeriksaan');
        Schema::dropIfExists('driver_kendaraan_item');
        Schema::dropIfExists('driver_checklist_item');
        Schema::dropIfExists('driver_kendaraan');
    }
};
