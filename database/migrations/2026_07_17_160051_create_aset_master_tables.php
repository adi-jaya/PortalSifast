<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kategori', 20)->unique();
            $table->string('nama_kategori', 100);
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_jenis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jenis', 20)->unique();
            $table->string('nama_jenis', 100);
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_merk', function (Blueprint $table) {
            $table->id();
            $table->string('kode_merk', 20)->unique();
            $table->string('nama_merk', 100);
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_produsen', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produsen', 20)->unique();
            $table->string('nama_produsen', 120);
            $table->string('alamat', 255)->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 120)->nullable();
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_distributor', function (Blueprint $table) {
            $table->id();
            $table->string('kode_distributor', 20)->unique();
            $table->string('nama_distributor', 120);
            $table->string('alamat', 255)->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('hash_sumber', 64)->nullable();
            $table->timestamp('disinkron_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_aspak_alat', function (Blueprint $table) {
            $table->id();
            $table->string('id_alat_aspak', 30)->unique();
            $table->string('nama_alat', 200);
            $table->string('kode', 20)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('aset_aspak_alat')->nullOnDelete();
            $table->string('alat_path', 500)->nullable();
            $table->string('sinonim', 200)->nullable();
            $table->boolean('wajib_kalibrasi')->default(false);
            $table->unsignedInteger('durasi_kalibrasi_hari')->default(700);
            $table->timestamps();
        });

        Schema::table('aset_barang', function (Blueprint $table) {
            $table->foreignId('aset_kategori_id')->nullable()->after('nama_barang')->constrained('aset_kategori')->nullOnDelete();
            $table->foreignId('aset_jenis_id')->nullable()->after('aset_kategori_id')->constrained('aset_jenis')->nullOnDelete();
            $table->foreignId('aset_merk_id')->nullable()->after('aset_jenis_id')->constrained('aset_merk')->nullOnDelete();
            $table->foreignId('aset_produsen_id')->nullable()->after('aset_merk_id')->constrained('aset_produsen')->nullOnDelete();
            $table->foreignId('aset_aspak_alat_id')->nullable()->after('aset_produsen_id')->constrained('aset_aspak_alat')->nullOnDelete();
            $table->unsignedInteger('jumlah')->default(0)->after('aset_aspak_alat_id');
            $table->string('no_akl_akd', 60)->nullable()->after('umur_ekonomis_bulan');
            $table->unsignedInteger('daya_watt')->nullable()->after('no_akl_akd');
            $table->string('level_teknologi', 20)->nullable()->after('daya_watt');
            $table->unsignedSmallInteger('tahun_mulai_operasi')->nullable()->after('level_teknologi');
            $table->decimal('nilai_residu', 15, 2)->nullable()->after('tahun_mulai_operasi');
            $table->softDeletes();
        });

        Schema::table('aset', function (Blueprint $table) {
            $table->string('no_seri', 80)->nullable()->after('no_simrs');
            $table->foreignId('aset_distributor_id')->nullable()->after('aset_ruang_id')->constrained('aset_distributor')->nullOnDelete();
            $table->string('status_fungsi', 30)->nullable()->after('kondisi');
            $table->string('tingkat_kerusakan', 30)->nullable()->after('status_fungsi');
            $table->softDeletes();

            $table->unique('no_seri');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropUnique(['no_seri']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('aset_distributor_id');
            $table->dropColumn(['no_seri', 'status_fungsi', 'tingkat_kerusakan']);
        });

        Schema::table('aset_barang', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('aset_aspak_alat_id');
            $table->dropConstrainedForeignId('aset_produsen_id');
            $table->dropConstrainedForeignId('aset_merk_id');
            $table->dropConstrainedForeignId('aset_jenis_id');
            $table->dropConstrainedForeignId('aset_kategori_id');
            $table->dropColumn([
                'jumlah', 'no_akl_akd', 'daya_watt', 'level_teknologi',
                'tahun_mulai_operasi', 'nilai_residu',
            ]);
        });

        Schema::dropIfExists('aset_aspak_alat');
        Schema::dropIfExists('aset_distributor');
        Schema::dropIfExists('aset_produsen');
        Schema::dropIfExists('aset_merk');
        Schema::dropIfExists('aset_jenis');
        Schema::dropIfExists('aset_kategori');
    }
};
