<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePegawaiProfilRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const TRIM_FIELDS = [
        'nama',
        'jbtn',
        'bidang',
        'departemen',
        'stts_kerja',
        'stts_wp',
        'pendidikan',
        'jnj_jabatan',
        'kode_kelompok',
        'alamat',
        'kota',
        'tmp_lahir',
        'no_ktp',
    ];

    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];
        foreach (self::TRIM_FIELDS as $field) {
            $merged[$field] = trim((string) $this->input($field, ''));
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:50'],
            'jk' => ['required', Rule::in(['Pria', 'Wanita'])],
            'jbtn' => ['required', 'string', 'max:25'],
            'bidang' => ['required', 'string', 'max:15', Rule::exists('dbsimrs.bidang', 'nama')],
            'departemen' => ['required', 'string', 'max:4', Rule::exists('dbsimrs.departemen', 'dep_id')],
            'stts_kerja' => ['required', 'string', 'max:3', Rule::exists('dbsimrs.stts_kerja', 'stts')],
            'stts_wp' => ['required', 'string', 'max:5', Rule::exists('dbsimrs.stts_wp', 'stts')],
            'pendidikan' => ['required', 'string', 'max:80', Rule::exists('dbsimrs.pendidikan', 'tingkat')],
            'jnj_jabatan' => ['required', 'string', 'max:5', Rule::exists('dbsimrs.jnj_jabatan', 'kode')],
            'kode_kelompok' => ['required', 'string', 'max:3', Rule::exists('dbsimrs.kelompok_jabatan', 'kode_kelompok')],
            'mulai_kerja' => ['required', 'date'],
            'stts_aktif' => ['required', Rule::in(['AKTIF', 'CUTI', 'KELUAR', 'TENAGA LUAR', 'NON AKTIF'])],
            'alamat' => ['required', 'string', 'max:60'],
            'kota' => ['required', 'string', 'max:20'],
            'tmp_lahir' => ['required', 'string', 'max:20'],
            'tgl_lahir' => ['required', 'date'],
            'no_ktp' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'jk.required' => 'Jenis kelamin wajib dipilih.',
            'jk.in' => 'Jenis kelamin tidak valid.',
            'stts_aktif.in' => 'Status aktif tidak valid.',
            'mulai_kerja.required' => 'Tanggal mulai kerja wajib diisi.',
            'tgl_lahir.required' => 'Tanggal lahir wajib diisi.',
            'bidang.exists' => 'Bidang tidak ditemukan di master.',
            'departemen.exists' => 'Departemen tidak ditemukan di master.',
            'stts_kerja.exists' => 'Status kerja tidak ditemukan di master.',
            'stts_wp.exists' => 'Status WP tidak ditemukan di master.',
            'pendidikan.exists' => 'Pendidikan tidak ditemukan di master.',
            'jnj_jabatan.exists' => 'Jenjang jabatan tidak ditemukan di master.',
            'kode_kelompok.exists' => 'Kelompok jabatan tidak ditemukan di master.',
        ];
    }
}
