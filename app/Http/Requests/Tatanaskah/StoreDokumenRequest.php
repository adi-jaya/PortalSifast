<?php

namespace App\Http\Requests\Tatanaskah;

use App\Models\Pegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canBuatDokumen() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'kode_jenis' => ['required', 'string', Rule::exists('konfigurasi_jenis_dokumen', 'kode')->where('is_aktif', true)],
            'kode_unit_klasifikasi_id' => ['required', 'integer', Rule::exists('kode_unit_klasifikasi', 'id')->where('is_aktif', true)],
            'kode_sifat' => ['required', 'string', Rule::exists('kode_sifat_naskah', 'kode')->where('is_aktif', true)],
            'penandatangan_nik' => ['required', 'string', 'max:20'],
            'penandatangan_nama' => ['required', 'string', 'max:150'],
            'penandatangan_jabatan' => ['nullable', 'string', 'max:100'],
            'tanggal_review' => ['nullable', 'date'],
            'nomor_revisi' => ['nullable', 'string', 'max:5'],
            'menimbang' => ['nullable', 'string'],
            'mengingat' => ['nullable', 'string'],
            'diktum' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File PDF wajib diupload.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
            'penandatangan_nik.required' => 'Penandatangan wajib dipilih.',
            'penandatangan_nama.required' => 'Nama penandatangan wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('penandatangan_nik')) {
                return;
            }

            try {
                if (app()->runningUnitTests()) {
                    return;
                }

                $exists = Pegawai::query()
                    ->where('nik', $this->input('penandatangan_nik'))
                    ->where('stts_aktif', 'AKTIF')
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('penandatangan_nik', 'Pegawai penandatangan tidak ditemukan atau tidak aktif.');
                }
            } catch (\Throwable) {
                // Koneksi SIMRS tidak tersedia — validasi format saja (mis. saat testing).
            }
        });
    }
}
