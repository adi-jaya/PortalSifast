<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_sifat_naskah', function (Blueprint $table) {
            $table->string('kode', 5)->primary();
            $table->string('nama', 50);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('kode_unit_klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->string('dep_id', 20)->nullable()->index();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('konfigurasi_jenis_dokumen', function (Blueprint $table) {
            $table->string('kode', 10)->primary();
            $table->string('nama', 100);
            $table->string('kategori', 20);
            $table->text('deskripsi')->nullable();
            $table->string('format_nomor', 150)->default("RS'ASF/[NNN]/[UNIT_KLASIFIKASI]/[SIFAT]/[BR]/[YYYY]");
            $table->string('prefix_kode_rs', 10)->default("RS'ASF");
            $table->unsignedTinyInteger('halaman_inject')->default(1);
            $table->unsignedSmallInteger('posisi_x')->nullable();
            $table->unsignedSmallInteger('posisi_y')->nullable();
            $table->unsignedTinyInteger('ukuran_font')->default(11);
            $table->string('alignment', 10)->default('kanan');
            $table->string('varian_kop', 20)->default('standar');
            $table->string('tipe_workflow', 20)->default('regulasi');
            $table->boolean('butuh_tte_direktur')->default(true);
            $table->boolean('pakai_salam_islami')->default(true);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dokumen', 80)->nullable()->unique();
            $table->string('judul');
            $table->string('kategori', 20);
            $table->string('kode_jenis', 10);
            $table->string('dep_id', 20)->nullable()->index();
            $table->foreignId('kode_unit_klasifikasi_id')->constrained('kode_unit_klasifikasi');
            $table->string('kode_sifat', 5);
            $table->foreign('kode_sifat')->references('kode')->on('kode_sifat_naskah');
            $table->string('tingkat', 20)->default('unit');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('versi_saat_ini_id')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->date('tanggal_ditetapkan')->nullable();
            $table->string('tanggal_hijriyah', 50)->nullable();
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_review')->nullable();
            $table->timestamp('tanggal_kadaluarsa')->nullable();
            $table->timestamp('diarsipkan_pada')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('kode_jenis')->references('kode')->on('konfigurasi_jenis_dokumen');
        });

        Schema::create('versi_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_id')->constrained('dokumen')->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor_versi')->default(1);
            $table->string('nomor_revisi', 5)->nullable();
            $table->string('file_asli', 500)->nullable();
            $table->string('file_bernomor', 500)->nullable();
            $table->string('file_final', 500)->nullable();
            $table->char('hash_sha256', 64)->nullable();
            $table->unsignedInteger('ukuran_kb')->default(0);
            $table->unsignedSmallInteger('jumlah_halaman')->default(0);
            $table->text('catatan_perubahan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['dokumen_id', 'nomor_versi']);
        });

        Schema::table('dokumen', function (Blueprint $table) {
            $table->foreign('versi_saat_ini_id')->references('id')->on('versi_dokumen')->nullOnDelete();
        });

        Schema::create('counter_nomor_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kode_unit_klasifikasi_id')->constrained('kode_unit_klasifikasi')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedMediumInteger('counter')->default(0);
            $table->timestamps();

            $table->unique(['kode_unit_klasifikasi_id', 'tahun'], 'uq_counter_unit_tahun');
        });

        Schema::create('meta_regulasi', function (Blueprint $table) {
            $table->foreignId('dokumen_id')->primary()->constrained('dokumen')->cascadeOnDelete();
            $table->text('dasar_hukum')->nullable();
            $table->text('menimbang')->nullable();
            $table->text('mengingat')->nullable();
            $table->text('diktum')->nullable();
            $table->string('nomor_revisi', 5)->nullable();
            $table->text('keterangan_lampiran')->nullable();
            $table->json('tags_clinical_pathway')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_id')->constrained('dokumen')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('aksi', 50);
            $table->string('status_lama', 30)->nullable();
            $table->string('status_baru', 30)->nullable();
            $table->text('catatan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('distribusi_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_id')->constrained('dokumen')->cascadeOnDelete();
            $table->string('dep_id', 20)->index();
            $table->foreignId('dikirim_oleh')->constrained('users');
            $table->timestamp('dikirim_pada')->useCurrent();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribusi_dokumen');
        Schema::dropIfExists('audit_dokumen');
        Schema::dropIfExists('meta_regulasi');
        Schema::dropIfExists('counter_nomor_dokumen');
        Schema::table('dokumen', function (Blueprint $table) {
            $table->dropForeign(['versi_saat_ini_id']);
        });
        Schema::dropIfExists('versi_dokumen');
        Schema::dropIfExists('dokumen');
        Schema::dropIfExists('konfigurasi_jenis_dokumen');
        Schema::dropIfExists('kode_unit_klasifikasi');
        Schema::dropIfExists('kode_sifat_naskah');
    }
};
