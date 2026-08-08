<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_metric_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_device_id')->constrained('monitored_devices')->cascadeOnDelete();
            $table->decimal('cpu_percent', 5, 2)->nullable();
            $table->decimal('ram_percent', 5, 2)->nullable();
            $table->decimal('disk_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->timestamp('collected_at')->useCurrent();

            $table->index(['monitored_device_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_metric_samples');
    }
};
