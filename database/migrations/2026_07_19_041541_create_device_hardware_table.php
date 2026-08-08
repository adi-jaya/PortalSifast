<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_hardware', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_device_id')->unique()->constrained('monitored_devices')->cascadeOnDelete();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('architecture', 32)->nullable();
            $table->string('cpu_model')->nullable();
            $table->unsignedSmallInteger('cpu_cores')->nullable();
            $table->unsignedInteger('ram_total_mb')->nullable();
            $table->unsignedInteger('disk_total_gb')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->string('motherboard')->nullable();
            $table->string('bios')->nullable();
            $table->timestamp('boot_time')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('domain')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_hardware');
    }
};
