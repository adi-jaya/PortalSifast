<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_monitoring')->default(false)->after('can_coordinate_checklist_kendaraan');
            $table->boolean('can_manage_monitoring_kategori')->default(false)->after('can_access_monitoring');
            $table->index('can_access_monitoring');
            $table->index('can_manage_monitoring_kategori');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['can_access_monitoring']);
            $table->dropIndex(['can_manage_monitoring_kategori']);
            $table->dropColumn([
                'can_access_monitoring',
                'can_manage_monitoring_kategori',
            ]);
        });
    }
};
