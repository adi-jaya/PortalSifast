<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_manage_web_official')->default(false)->after('can_view_mutu_dashboard');
            $table->index('can_manage_web_official');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['can_manage_web_official']);
            $table->dropColumn('can_manage_web_official');
        });
    }
};
