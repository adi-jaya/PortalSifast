<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('hostname')->nullable();
            $table->string('computer_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 64)->nullable();
            $table->string('api_key_prefix', 8)->index();
            $table->string('api_key_hash');
            $table->string('agent_version', 32)->nullable();
            $table->string('status', 16)->default('offline')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->decimal('last_cpu_percent', 5, 2)->nullable();
            $table->decimal('last_ram_percent', 5, 2)->nullable();
            $table->decimal('last_disk_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->foreignId('aset_id')->nullable()->constrained('aset')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_devices');
    }
};
