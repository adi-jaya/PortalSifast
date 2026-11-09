<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'dbsimrs';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('dbsimrs')->hasTable('permintaan_rujukan_faskes')) {
            return;
        }

        Schema::connection('dbsimrs')->create('permintaan_rujukan_faskes', function (Blueprint $table) {
            $table->string('no_rujukan', 20)->primary()->comment('Format: RF-YYYYMMDD-XXXX');
            $table->date('tanggal_rujukan');
            $table->time('jam_rujukan');
            $table->string('nama_fktp', 100);
            $table->string('nama_perujuk', 60)->comment('Nama dokter/bidan/perawat FKTP');
            $table->string('no_telp_perujuk', 20)->comment('No WA/HP kontak perujuk');
            $table->string('nama_pasien', 100);
            $table->string('no_ktp', 20)->comment('NIK 16 digit');
            $table->enum('jk', ['L', 'P']);
            $table->date('tgl_lahir')->nullable();
            $table->string('umur', 15)->nullable()->comment('Contoh: 35 Th / 6 Bl');
            $table->string('no_telp_pasien', 20)->nullable();
            $table->text('diagnosa')->comment('Diagnosa kerja/awal perujuk');
            $table->text('keluhan_utama')->nullable()->comment('Keluhan & alasan rujukan');
            $table->string('tanda_vital_kesadaran', 30)->nullable()->comment('Compos Mentis / Apatis / Somnolen / Sopor / Koma');
            $table->string('tanda_vital_td', 15)->nullable()->comment('Tekanan Darah, misal: 120/80');
            $table->string('tanda_vital_nadi', 10)->nullable()->comment('x/menit');
            $table->string('tanda_vital_rr', 10)->nullable()->comment('x/menit');
            $table->string('tanda_vital_suhu', 10)->nullable()->comment('Derajat celcius');
            $table->string('tanda_vital_spo2', 10)->nullable()->comment('Saturasi O2 (%)');
            $table->text('tindakan_diberikan')->nullable()->comment('Terapi, oksigen, obat yang sudah diberi');
            $table->string('transportasi', 50)->nullable()->comment('Ambulans FKTP / Mobil Pribadi / dll');
            $table->string('kebutuhan_khusus', 100)->nullable()->comment('Resusitasi / Isolasi / Inkubator / Oksigen Lanjutan');
            $table->string('estimasi_tiba', 50)->nullable()->comment('Contoh: 15 menit / Pukul 17:30');
            $table->enum('status', ['Menunggu', 'Sudah Datang', 'Batal'])->default('Menunggu');
            $table->text('catatan_igd')->nullable()->comment('Catatan tindak lanjut petugas IGD');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['tanggal_rujukan', 'status'], 'idx_tgl_status');
            $table->index('no_ktp', 'idx_no_ktp');
            $table->index('nama_pasien', 'idx_nama_pasien');

            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';
            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('dbsimrs')->dropIfExists('permintaan_rujukan_faskes');
    }
};
