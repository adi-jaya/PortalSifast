<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiwayatSeminarRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const TINGKAT_VALUES = [
        'Local',
        'Regional',
        'Nasional',
        'Internasional',
    ];

    /**
     * @var list<string>
     */
    private const JENIS_VALUES = [
        'WORKSHOP',
        'SIMPOSIUM',
        'SEMINAR',
        'FGD',
        'PELATIHAN',
        'LAINNYA',
    ];

    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tingkat' => trim((string) $this->input('tingkat', '')),
            'jenis' => trim((string) $this->input('jenis', '')),
            'nama_seminar' => trim((string) $this->input('nama_seminar', '')),
            'peranan' => trim((string) $this->input('peranan', '')),
            'penyelengara' => trim((string) $this->input('penyelengara', '')),
            'tempat' => trim((string) $this->input('tempat', '')),
            'original_nama_seminar' => trim((string) $this->input('original_nama_seminar', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'original_nama_seminar' => ['required', 'string', 'max:50'],
            'original_mulai' => ['required', 'date'],
            'tingkat' => ['required', 'string', Rule::in(self::TINGKAT_VALUES)],
            'jenis' => ['required', 'string', Rule::in(self::JENIS_VALUES)],
            'nama_seminar' => ['required', 'string', 'max:50'],
            'peranan' => ['required', 'string', 'max:40'],
            'mulai' => ['required', 'date'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'penyelengara' => ['required', 'string', 'max:50'],
            'tempat' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_nama_seminar.required' => 'Identitas data asli wajib dikirim.',
            'original_mulai.required' => 'Identitas data asli wajib dikirim.',
            'tingkat.required' => 'Tingkat wajib diisi.',
            'tingkat.in' => 'Tingkat tidak valid.',
            'jenis.required' => 'Jenis wajib diisi.',
            'jenis.in' => 'Jenis tidak valid.',
            'nama_seminar.required' => 'Nama seminar wajib diisi.',
            'peranan.required' => 'Peranan wajib diisi.',
            'mulai.required' => 'Tanggal mulai wajib diisi.',
            'selesai.required' => 'Tanggal selesai wajib diisi.',
            'selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'penyelengara.required' => 'Penyelenggara wajib diisi.',
            'tempat.required' => 'Tempat wajib diisi.',
        ];
    }
}
