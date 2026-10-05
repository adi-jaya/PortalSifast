<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_berkas_kepegawaian')->default(false)->after('can_access_monitoring');
            $table->index('can_access_berkas_kepegawaian');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['can_access_berkas_kepegawaian']);
            $table->dropColumn('can_access_berkas_kepegawaian');
        });
    }
};
