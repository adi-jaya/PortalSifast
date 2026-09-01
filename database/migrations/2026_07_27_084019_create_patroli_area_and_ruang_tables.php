<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cutover bersih: check-in lama terikat aset_ruang, tidak dimigrasi.
        DB::table('patroli_checkin_item')->delete();
        DB::table('patroli_checkin')->delete();

        if (! Schema::hasTable('patroli_area')) {
            Schema::create('patroli_area', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('patroli_ruang')) {
            Schema::create('patroli_ruang', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patroli_area_id')->constrained('patroli_area')->cascadeOnDelete();
                $table->string('kode')->nullable()->unique();
                $table->string('nama');
                $table->foreignId('patroli_template_id')
                    ->nullable()
                    ->constrained('patroli_template')
                    ->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['patroli_area_id', 'nama']);
                $table->index(['patroli_template_id', 'is_active']);
            });
        }

        Schema::table('patroli_checkin', function (Blueprint $table) {
            $table->dropForeign(['aset_ruang_id']);
            $table->dropIndex(['aset_ruang_id', 'checked_at']);
            $table->dropColumn('aset_ruang_id');
        });

        Schema::table('patroli_checkin', function (Blueprint $table) {
            $table->foreignId('patroli_ruang_id')
                ->after('id')
                ->constrained('patroli_ruang')
                ->cascadeOnDelete();
            $table->index(['patroli_ruang_id', 'checked_at']);
        });

        if (Schema::hasColumn('aset_ruang', 'patroli_template_id')) {
            Schema::table('aset_ruang', function (Blueprint $table) {
                $table->dropConstrainedForeignId('patroli_template_id');
            });
        }
    }

    public function down(): void
    {
        DB::table('patroli_checkin_item')->delete();
        DB::table('patroli_checkin')->delete();

        Schema::table('patroli_checkin', function (Blueprint $table) {
            $table->dropForeign(['patroli_ruang_id']);
            $table->dropIndex(['patroli_ruang_id', 'checked_at']);
            $table->dropColumn('patroli_ruang_id');
        });

        Schema::table('patroli_checkin', function (Blueprint $table) {
            $table->foreignId('aset_ruang_id')
                ->after('id')
                ->constrained('aset_ruang')
                ->cascadeOnDelete();
            $table->index(['aset_ruang_id', 'checked_at']);
        });

        Schema::dropIfExists('patroli_ruang');
        Schema::dropIfExists('patroli_area');

        if (! Schema::hasColumn('aset_ruang', 'patroli_template_id')) {
            Schema::table('aset_ruang', function (Blueprint $table) {
                $table->foreignId('patroli_template_id')
                    ->nullable()
                    ->after('nama_ruang')
                    ->constrained('patroli_template')
                    ->nullOnDelete();
            });
        }
    }
};

