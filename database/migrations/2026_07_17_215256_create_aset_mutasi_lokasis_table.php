<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_mutasi_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->foreignId('aset_ruang_asal_id')->constrained('aset_ruang')->restrictOnDelete();
            $table->foreignId('aset_ruang_tujuan_id')->constrained('aset_ruang')->restrictOnDelete();
            $table->foreignId('penerima_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('penerima_nik', 30)->nullable()->index();
            $table->foreignId('dicatat_oleh_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tanggal_mutasi');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_mutasi_lokasi');
    }
};
