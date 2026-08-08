<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_non_alkes', function (Blueprint $table) {
            $table->id();
            $table->string('id_alat', 30)->unique();
            $table->string('nama_alat', 255);
            $table->string('alat_code', 50)->nullable();
            $table->string('kode', 50)->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('aset_non_alkes')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1)->index();
            $table->string('alat_path', 500)->nullable();
            $table->text('alat_ket')->nullable();
            $table->text('sinonim')->nullable();
            $table->boolean('deleted')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('aset_barang', function (Blueprint $table) {
            $table->foreignId('aset_non_alkes_id')
                ->nullable()
                ->after('aset_aspak_alat_id')
                ->constrained('aset_non_alkes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aset_barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aset_non_alkes_id');
        });

        Schema::dropIfExists('aset_non_alkes');
    }
};
