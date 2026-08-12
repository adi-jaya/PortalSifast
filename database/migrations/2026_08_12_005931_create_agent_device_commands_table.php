<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_device_id')->constrained('monitored_devices')->cascadeOnDelete();
            $table->string('type', 32);
            $table->json('payload')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->json('result')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['monitored_device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_device_commands');
    }
};
