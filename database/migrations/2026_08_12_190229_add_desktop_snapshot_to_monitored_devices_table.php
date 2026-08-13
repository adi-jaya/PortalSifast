<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table): void {
            $table->string('desktop_snapshot_path')->nullable()->after('suspicious_processes_at');
            $table->timestamp('desktop_snapshot_at')->nullable()->after('desktop_snapshot_path');
            $table->json('desktop_snapshot_meta')->nullable()->after('desktop_snapshot_at');
        });
    }

    public function down(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table): void {
            $table->dropColumn([
                'desktop_snapshot_path',
                'desktop_snapshot_at',
                'desktop_snapshot_meta',
            ]);
        });
    }
};
