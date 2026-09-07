<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiwayatJabatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jabatan' => trim((string) $this->input('jabatan', '')),
            'pejabat_penetap' => trim((string) $this->input('pejabat_penetap', '')),
            'nomor_sk' => trim((string) $this->input('nomor_sk', '')),
            'dasar_peraturan' => trim((string) $this->input('dasar_peraturan', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jabatan' => ['required', 'string', 'max:50'],
            'tmt_pangkat' => ['required', 'date'],
            'tmt_pangkat_yad' => ['required', 'date'],
            'pejabat_penetap' => ['required', 'string', 'max:50'],
            'nomor_sk' => ['required', 'string', 'max:25'],
            'tgl_sk' => ['required', 'date'],
            'dasar_peraturan' => ['required', 'string', 'max:50'],
            'masa_kerja' => ['required', 'integer', 'min:0'],
            'bln_kerja' => ['required', 'integer', 'min:0', 'max:11'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jabatan.required' => 'Jabatan wajib diisi.',
            'tmt_pangkat.required' => 'TMT pangkat wajib diisi.',
            'tmt_pangkat_yad.required' => 'TMT pangkat YAD wajib diisi.',
            'pejabat_penetap.required' => 'Pejabat penetap wajib diisi.',
            'nomor_sk.required' => 'Nomor SK wajib diisi.',
            'tgl_sk.required' => 'Tanggal SK wajib diisi.',
            'dasar_peraturan.required' => 'Dasar peraturan wajib diisi.',
            'masa_kerja.required' => 'Masa kerja wajib diisi.',
            'bln_kerja.required' => 'Bulan kerja wajib diisi.',
        ];
    }
}
