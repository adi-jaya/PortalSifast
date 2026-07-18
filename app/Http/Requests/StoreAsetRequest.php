<?php

namespace App\Http\Requests;

use App\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignoreAsetId = $this->route('aset')?->id;

        return [
            'aset_barang_id' => ['nullable', 'integer', 'exists:aset_barang,id'],
            'aset_ruang_id' => ['required', 'integer', 'exists:aset_ruang,id'],
            'aset_distributor_id' => ['nullable', 'integer', 'exists:aset_distributor,id'],
            'aset_kategori_id' => ['nullable', 'integer', 'exists:aset_kategori,id'],
            'aset_jenis_id' => ['nullable', 'integer', 'exists:aset_jenis,id'],
            'aset_merk_id' => ['nullable', 'integer', 'exists:aset_merk,id'],
            'aset_produsen_id' => ['nullable', 'integer', 'exists:aset_produsen,id'],
            'aset_aspak_alat_id' => ['nullable', 'integer', 'exists:aset_aspak_alat,id'],
            'tahun_registrasi' => ['required', 'integer', 'min:1900', 'max:2100'],
            'tahun_produksi' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'tahun_mulai_operasi' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'jumlah_unit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'no_seri' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('aset', 'no_seri')->ignore($ignoreAsetId),
            ],
            'no_seri_list' => ['nullable', 'array', 'max:50'],
            'no_seri_list.*' => ['nullable', 'string', 'max:80'],
            'asal_barang' => ['nullable', 'string', 'max:30'],
            'tanggal_pengadaan' => ['nullable', 'date'],
            'harga' => ['nullable', 'numeric', 'min:0'],
            'kondisi' => ['nullable', 'string', 'max:30'],
            'status_fungsi' => ['nullable', Rule::in(['berfungsi', 'tidak_berfungsi'])],
            'tingkat_kerusakan' => ['nullable', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'kelas_aset' => ['nullable', Rule::in(['medis', 'non_medis'])],
            'wajib_kalibrasi' => ['sometimes', 'boolean'],
            'umur_ekonomis_bulan' => ['nullable', 'integer', 'min:1', 'max:600'],
            'no_akl_akd' => ['nullable', 'string', 'max:60'],
            'daya_watt' => ['nullable', 'integer', 'min:0'],
            'level_teknologi' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'nilai_residu' => ['nullable', 'numeric', 'min:0'],
            'nama_barang' => ['required_without:aset_barang_id', 'nullable', 'string', 'max:120'],
            'kode_barang' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $list = $this->input('no_seri_list', []);
            if (! is_array($list)) {
                return;
            }

            $filled = array_values(array_filter($list, fn ($s) => filled($s)));
            if (count($filled) !== count(array_unique($filled))) {
                $validator->errors()->add('no_seri_list', 'Nomor seri tidak boleh duplikat dalam satu pengisian.');
            }

            $ignoreId = $this->route('aset')?->id;
            foreach ($filled as $serial) {
                $exists = Aset::query()
                    ->where('no_seri', $serial)
                    ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('no_seri_list', "Serial {$serial} sudah dipakai aset lain.");

                    break;
                }
            }
        });
    }
}
