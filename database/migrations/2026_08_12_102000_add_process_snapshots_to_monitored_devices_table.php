<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table) {
            $table->json('process_snapshot')->nullable()->after('window_snapshot_at');
            $table->timestamp('process_snapshot_at')->nullable()->after('process_snapshot');
            $table->json('suspicious_processes')->nullable()->after('process_snapshot_at');
            $table->timestamp('suspicious_processes_at')->nullable()->after('suspicious_processes');
        });
    }

    public function down(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table) {
            $table->dropColumn([
                'process_snapshot',
                'process_snapshot_at',
                'suspicious_processes',
                'suspicious_processes_at',
            ]);
        });
    }
};
