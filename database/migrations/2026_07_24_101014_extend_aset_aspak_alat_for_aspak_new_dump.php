<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset_aspak_alat', function (Blueprint $table) {
            $table->string('nama_alat', 500)->change();
            $table->string('kode', 50)->nullable()->change();
            $table->string('alat_code', 50)->nullable()->after('kode');
            $table->text('alat_ket')->nullable()->after('alat_path');
            $table->string('sinonim', 500)->nullable()->change();
        });

        Schema::table('aset_barang', function (Blueprint $table) {
            $table->string('nama_barang', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('aset_aspak_alat', function (Blueprint $table) {
            $table->dropColumn(['alat_code', 'alat_ket']);
            $table->string('nama_alat', 200)->change();
            $table->string('kode', 20)->nullable()->change();
            $table->string('sinonim', 200)->nullable()->change();
        });

        Schema::table('aset_barang', function (Blueprint $table) {
            $table->string('nama_barang', 120)->nullable()->change();
        });
    }
};
