<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_buat_dokumen')->default(false)->after('can_view_mutu_dashboard');
            $table->boolean('can_review_dokumen')->default(false)->after('can_buat_dokumen');
            $table->boolean('can_approve_dokumen_mutu')->default(false)->after('can_review_dokumen');
            $table->boolean('can_tte_dokumen')->default(false)->after('can_approve_dokumen_mutu');
            $table->boolean('can_manage_tatanaskah')->default(false)->after('can_tte_dokumen');
            $table->boolean('can_konfirmasi_terima_dokumen')->default(false)->after('can_manage_tatanaskah');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'can_buat_dokumen',
                'can_review_dokumen',
                'can_approve_dokumen_mutu',
                'can_tte_dokumen',
                'can_manage_tatanaskah',
                'can_konfirmasi_terima_dokumen',
            ]);
        });
    }
};
