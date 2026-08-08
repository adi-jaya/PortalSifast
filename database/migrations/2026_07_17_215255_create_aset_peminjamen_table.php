<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->foreignId('peminjam_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('peminjam_nik', 30)->nullable()->index();
            $table->foreignId('diserahkan_oleh_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tanggal_pinjam');
            $table->date('tanggal_kembali_rencana')->nullable();
            $table->dateTime('tanggal_kembali_aktual')->nullable();
            $table->foreignId('diterima_kembali_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->text('catatan')->nullable();
            $table->string('kondisi_kembali')->nullable();
            $table->timestamps();

            $table->index(['status', 'tanggal_kembali_rencana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_peminjaman');
    }
};
