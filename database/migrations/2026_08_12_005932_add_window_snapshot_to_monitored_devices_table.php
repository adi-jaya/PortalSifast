<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table) {
            $table->json('window_snapshot')->nullable()->after('sensors');
            $table->timestamp('window_snapshot_at')->nullable()->after('window_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('monitored_devices', function (Blueprint $table) {
            $table->dropColumn(['window_snapshot', 'window_snapshot_at']);
        });
    }
};
