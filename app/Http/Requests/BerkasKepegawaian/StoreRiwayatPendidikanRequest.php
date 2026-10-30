<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiwayatPendidikanRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const PENDIDIKAN_VALUES = [
        'SD',
        'SMP',
        'SMA',
        'SMK',
        'D I',
        'D II',
        'D III',
        'D IV',
        'S1',
        'S2',
        'S3',
        'Post Doctor',
    ];

    /**
     * @var list<string>
     */
    private const PENDANAAN_VALUES = [
        'Biaya Sendiri',
        'Biaya Instansi Sendiri',
        'Lembaga Swasta Kerjasama',
        'Lembaga Swasta Kompetisi',
        'Lembaga Pemerintah Kerjasama',
        'Lembaga Pemerintah Kompetisi',
        'Lembaga Internasional',
    ];

    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pendidikan' => trim((string) $this->input('pendidikan', '')),
            'sekolah' => trim((string) $this->input('sekolah', '')),
            'jurusan' => trim((string) $this->input('jurusan', '')),
            'kepala' => trim((string) $this->input('kepala', '')),
            'pendanaan' => trim((string) $this->input('pendanaan', '')),
            'keterangan' => trim((string) $this->input('keterangan', '')),
            'status' => trim((string) $this->input('status', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pendidikan' => ['required', 'string', Rule::in(self::PENDIDIKAN_VALUES)],
            'sekolah' => ['required', 'string', 'max:50'],
            'jurusan' => ['required', 'string', 'max:40'],
            'thn_lulus' => ['required', 'digits:4', 'integer', 'min:1950', 'max:2100'],
            'kepala' => ['required', 'string', 'max:50'],
            'pendanaan' => ['required', 'string', Rule::in(self::PENDANAAN_VALUES)],
            'keterangan' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pendidikan.required' => 'Pendidikan wajib diisi.',
            'pendidikan.in' => 'Pendidikan tidak valid.',
            'sekolah.required' => 'Sekolah wajib diisi.',
            'jurusan.required' => 'Jurusan wajib diisi.',
            'thn_lulus.required' => 'Tahun lulus wajib diisi.',
            'kepala.required' => 'Kepala sekolah wajib diisi.',
            'pendanaan.required' => 'Pendanaan wajib diisi.',
            'pendanaan.in' => 'Pendanaan tidak valid.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'status.required' => 'Status wajib diisi.',
        ];
    }
}
