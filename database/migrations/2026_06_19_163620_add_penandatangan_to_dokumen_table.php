<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen', function (Blueprint $table) {
            $table->string('penandatangan_nik', 20)->nullable()->after('dibuat_oleh');
            $table->string('penandatangan_nama', 150)->nullable()->after('penandatangan_nik');
            $table->string('penandatangan_jabatan', 100)->nullable()->after('penandatangan_nama');
            $table->foreignId('disetujui_oleh')->nullable()->after('penandatangan_jabatan')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disetujui_oleh');
            $table->dropColumn(['penandatangan_nik', 'penandatangan_nama', 'penandatangan_jabatan']);
        });
    }
};
