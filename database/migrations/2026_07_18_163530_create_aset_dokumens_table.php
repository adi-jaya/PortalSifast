<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200)->nullable();
            $table->string('tipe', 40);
            $table->string('lingkup', 20);
            $table->foreignId('aset_id')->nullable()->constrained('aset')->cascadeOnDelete();
            $table->foreignId('aset_barang_id')->nullable()->constrained('aset_barang')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('nama_asli', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('ukuran')->default(0);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lingkup', 'aset_id']);
            $table->index(['lingkup', 'aset_barang_id']);
            $table->index('tipe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_dokumen');
    }
};
