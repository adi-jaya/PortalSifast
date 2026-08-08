<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('patroli_ruang'));
        $hasUniqueKode = $indexes->contains(
            fn (array $idx): bool => ($idx['unique'] ?? false)
                && $idx['columns'] === ['kode']
        );
        $hasNonUniqueKode = $indexes->contains(
            fn (array $idx): bool => ! ($idx['unique'] ?? false)
                && $idx['columns'] === ['kode']
        );

        Schema::table('patroli_ruang', function (Blueprint $table) use ($hasUniqueKode, $hasNonUniqueKode): void {
            if ($hasNonUniqueKode) {
                $table->dropIndex(['kode']);
            }

            if (! $hasUniqueKode) {
                $table->unique('kode');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('patroli_ruang'));
        $hasUniqueKode = $indexes->contains(
            fn (array $idx): bool => ($idx['unique'] ?? false)
                && $idx['columns'] === ['kode']
        );

        Schema::table('patroli_ruang', function (Blueprint $table) use ($hasUniqueKode): void {
            if ($hasUniqueKode) {
                $table->dropUnique(['kode']);
                $table->index('kode');
            }
        });
    }
};
