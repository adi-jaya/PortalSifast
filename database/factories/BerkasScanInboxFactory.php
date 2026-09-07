<?php

namespace Database\Factories;

use App\Models\BerkasScanInbox;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<BerkasScanInbox>
 */
class BerkasScanInboxFactory extends Factory
{
    protected $model = BerkasScanInbox::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->lexify('scan-????.pdf');
        $path = 'berkas-scan-inbox/'.Str::uuid().'.pdf';

        Storage::disk('local')->put($path, '%PDF-1.4 fake');

        return [
            'original_filename' => $filename,
            'stored_path' => $path,
            'suggested_kode' => null,
            'suggested_label' => null,
            'confidence' => null,
            'ocr_excerpt' => null,
            'ocr_failed' => false,
            'status' => BerkasScanInbox::STATUS_PENDING,
            'nik' => null,
            'confirmed_kode' => null,
            'confirmed_by' => null,
            'agent_label' => 'test-agent',
            'error_message' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => BerkasScanInbox::STATUS_PENDING,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => BerkasScanInbox::STATUS_FAILED,
            'error_message' => 'previous failure',
        ]);
    }
}
