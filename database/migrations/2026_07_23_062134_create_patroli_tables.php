<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patroli_template', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patroli_template_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patroli_template_id')->constrained('patroli_template')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['patroli_template_id', 'urutan']);
        });

        Schema::table('aset_ruang', function (Blueprint $table) {
            $table->foreignId('patroli_template_id')
                ->nullable()
                ->after('nama_ruang')
                ->constrained('patroli_template')
                ->nullOnDelete();
        });

        Schema::create('patroli_checkin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_ruang_id')->constrained('aset_ruang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patroli_template_id')->constrained('patroli_template')->restrictOnDelete();
            $table->timestamp('checked_at');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('checked_at');
            $table->index(['aset_ruang_id', 'checked_at']);
        });

        Schema::create('patroli_checkin_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patroli_checkin_id')->constrained('patroli_checkin')->cascadeOnDelete();
            $table->foreignId('patroli_template_item_id')->nullable()->constrained('patroli_template_item')->nullOnDelete();
            $table->string('nama_item');
            $table->string('status', 32);
            $table->timestamps();

            $table->index(['status']);
            $table->index(['patroli_checkin_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patroli_checkin_item');
        Schema::dropIfExists('patroli_checkin');

        Schema::table('aset_ruang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patroli_template_id');
        });

        Schema::dropIfExists('patroli_template_item');
        Schema::dropIfExists('patroli_template');
    }
};
