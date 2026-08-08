<?php

use App\Models\EmployeeSalary;
use App\Support\PayrollCsvMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->decimal('keterlambatan', 15, 2)->nullable()->after('ikkm')->comment('Potongan keterlambatan');
            $table->decimal('ijin', 15, 2)->nullable()->after('keterlambatan')->comment('Potongan ijin');
        });

        EmployeeSalary::query()
            ->whereNotNull('raw_row')
            ->orderBy('id')
            ->chunkById(100, function ($salaries): void {
                foreach ($salaries as $salary) {
                    $raw = is_array($salary->raw_row) ? $salary->raw_row : [];
                    if ($raw === []) {
                        continue;
                    }

                    $mapped = PayrollCsvMapper::mapRawRow($raw);

                    $salary->forceFill([
                        'keterlambatan' => $mapped['keterlambatan'],
                        'ijin' => $mapped['ijin'],
                        'lain_pot' => $mapped['lain_pot'],
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->dropColumn(['keterlambatan', 'ijin']);
        });
    }
};
