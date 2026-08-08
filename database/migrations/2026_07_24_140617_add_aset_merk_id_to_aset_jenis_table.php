<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset_jenis', function (Blueprint $table) {
            $table->foreignId('aset_merk_id')
                ->nullable()
                ->after('nama_jenis')
                ->constrained('aset_merk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aset_jenis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aset_merk_id');
        });
    }
};
