<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyRiwayatPendidikanRequest extends FormRequest
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

    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pendidikan' => trim((string) $this->input('pendidikan', '')),
            'sekolah' => trim((string) $this->input('sekolah', '')),
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
        ];
    }
}
