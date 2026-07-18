<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_ruang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ruang', 20)->unique();
            $table->string('nama_ruang', 100);
            $table->timestamp('sumber_hilang_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20)->unique();
            $table->string('nama_barang', 120)->nullable();
            $table->string('kode_produsen', 20)->nullable();
            $table->string('id_merk', 20)->nullable();
            $table->string('id_kategori', 20)->nullable();
            $table->string('id_jenis', 20)->nullable();
            $table->unsignedSmallInteger('tahun_produksi')->nullable();
            $table->string('isbn', 50)->nullable();
            $table->enum('kelas_aset', ['medis', 'non_medis'])->nullable();
            $table->boolean('wajib_kalibrasi')->default(false);
            $table->unsignedInteger('umur_ekonomis_bulan')->nullable();
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamp('sumber_hilang_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aset', 60)->unique();
            $table->string('no_simrs', 30)->nullable()->unique();
            $table->foreignId('aset_barang_id')->nullable()->constrained('aset_barang')->nullOnDelete();
            $table->string('kode_ruang_registrasi', 20)->nullable();
            $table->foreignId('aset_ruang_id')->nullable()->constrained('aset_ruang')->nullOnDelete();
            $table->unsignedSmallInteger('tahun_registrasi')->nullable();
            $table->string('asal_barang', 30)->nullable();
            $table->date('tanggal_pengadaan')->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->string('status_sumber', 30)->nullable();
            $table->string('kondisi', 30)->nullable();
            $table->string('siklus_hidup', 30)->default('draf');
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path_foto_sumber', 500)->nullable();
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamp('sumber_hilang_pada')->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('siklus_hidup');
            $table->index('kondisi');
            $table->index('tahun_registrasi');
        });

        Schema::create('aset_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('path', 500);
            $table->boolean('utama')->default(false);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['aset_id', 'utama']);
        });

        Schema::create('aset_counter_nomor', function (Blueprint $table) {
            $table->id();
            $table->string('awalan', 20)->default('INV');
            $table->string('kode_ruang', 20);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('terakhir')->default(0);
            $table->timestamps();

            $table->unique(['awalan', 'kode_ruang', 'tahun']);
        });

        Schema::create('aset_sinkron', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipicu_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('antrian');
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->unsignedInteger('jumlah_baru')->default(0);
            $table->unsignedInteger('jumlah_berubah')->default(0);
            $table->unsignedInteger('jumlah_sama')->default(0);
            $table->unsignedInteger('jumlah_hilang')->default(0);
            $table->unsignedInteger('jumlah_gagal')->default(0);
            $table->json('ringkasan')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->foreignId('pengguna_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis_peristiwa', 50);
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['aset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_riwayat');
        Schema::dropIfExists('aset_sinkron');
        Schema::dropIfExists('aset_counter_nomor');
        Schema::dropIfExists('aset_foto');
        Schema::dropIfExists('aset');
        Schema::dropIfExists('aset_barang');
        Schema::dropIfExists('aset_ruang');
    }
};
