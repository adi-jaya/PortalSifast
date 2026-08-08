<?php

namespace App\Http\Requests;

use App\Models\Aset;
use App\Models\AsetAspakAlat;
use App\Models\AsetJenis;
use App\Models\AsetNonAlkes;
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
            'aset_ruang_id' => ['nullable', 'integer', 'exists:aset_ruang,id'],
            'aset_ruang_id_list' => ['nullable', 'array', 'max:50'],
            'aset_ruang_id_list.*' => ['nullable', 'integer', 'exists:aset_ruang,id'],
            'aset_distributor_id' => ['nullable', 'integer', 'exists:aset_distributor,id'],
            'aset_kategori_id' => ['nullable', 'integer', 'exists:aset_kategori,id'],
            'aset_jenis_id' => ['nullable', 'integer', 'exists:aset_jenis,id'],
            'aset_merk_id' => ['nullable', 'integer', 'exists:aset_merk,id'],
            'aset_produsen_id' => ['nullable', 'integer', 'exists:aset_produsen,id'],
            'aset_aspak_alat_id' => ['nullable', 'integer', 'exists:aset_aspak_alat,id'],
            'aset_non_alkes_id' => ['nullable', 'integer', 'exists:aset_non_alkes,id'],
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
            'kelas_aset' => ['required_without:aset_barang_id', 'nullable', Rule::in(['medis', 'non_medis'])],
            'wajib_kalibrasi' => ['sometimes', 'boolean'],
            'umur_ekonomis_bulan' => ['nullable', 'integer', 'min:1', 'max:600'],
            'no_akl_akd' => ['nullable', 'string', 'max:60'],
            'daya_watt' => ['nullable', 'integer', 'min:0'],
            'level_teknologi' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'nilai_residu' => ['nullable', 'numeric', 'min:0'],
            'nama_barang' => [
                'required_without_all:aset_barang_id,aset_non_alkes_id,aset_aspak_alat_id',
                'nullable',
                'string',
                'max:255',
            ],
            'kode_barang' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $kelas = $this->input('kelas_aset');
            $aspakId = $this->input('aset_aspak_alat_id');
            $nonAlkesId = $this->input('aset_non_alkes_id');
            $barangId = $this->input('aset_barang_id');
            $isUpdate = $this->route('aset') !== null;

            if ($isUpdate) {
                if (! filled($this->input('aset_ruang_id'))) {
                    $validator->errors()->add('aset_ruang_id', 'Ruang wajib diisi.');
                }
            } else {
                $jumlah = max(1, min(50, (int) ($this->input('jumlah_unit') ?? 1)));
                $list = $this->input('aset_ruang_id_list', []);
                if (! is_array($list)) {
                    $list = [];
                }
                $list = array_values($list);

                if (count($list) === 0 && filled($this->input('aset_ruang_id'))) {
                    $list = array_fill(0, $jumlah, $this->input('aset_ruang_id'));
                }

                if (count($list) < $jumlah) {
                    $validator->errors()->add(
                        'aset_ruang_id_list',
                        "Isi ruang untuk setiap unit ({$jumlah} unit).",
                    );
                } else {
                    for ($i = 0; $i < $jumlah; $i++) {
                        if (! filled($list[$i] ?? null)) {
                            $validator->errors()->add(
                                "aset_ruang_id_list.{$i}",
                                'Ruang unit '.($i + 1).' wajib dipilih.',
                            );
                        }
                    }
                }
            }

            if ($kelas === 'medis' && ! filled($barangId) && ! filled($aspakId)) {
                $validator->errors()->add('aset_aspak_alat_id', 'Pilih item katalog ASPAK untuk aset medis.');
            }

            if ($kelas === 'non_medis' && ! filled($barangId) && ! filled($nonAlkesId) && ! filled($this->input('nama_barang'))) {
                $validator->errors()->add(
                    'aset_non_alkes_id',
                    'Pilih item katalog non-alkes atau isi nama barang.',
                );
            }

            if ($kelas === 'medis' && filled($nonAlkesId) && ! filled($barangId)) {
                $validator->errors()->add('aset_non_alkes_id', 'Katalog non-alkes hanya untuk aset non-medis.');
            }

            if ($kelas === 'non_medis' && filled($aspakId) && ! filled($barangId)) {
                $validator->errors()->add('aset_aspak_alat_id', 'Katalog ASPAK hanya untuk aset medis.');
            }

            if (filled($aspakId)) {
                $leaf = AsetAspakAlat::query()
                    ->leaf()
                    ->whereKey($aspakId)
                    ->exists();
                if (! $leaf) {
                    $validator->errors()->add(
                        'aset_aspak_alat_id',
                        'Hanya item katalog ASPAK paling bawah (leaf) yang boleh dipilih.',
                    );
                }
            }

            if (filled($nonAlkesId)) {
                $leaf = AsetNonAlkes::query()
                    ->leaf()
                    ->whereKey($nonAlkesId)
                    ->exists();
                if (! $leaf) {
                    $validator->errors()->add(
                        'aset_non_alkes_id',
                        'Hanya item katalog paling bawah (leaf) yang boleh dipilih.',
                    );
                }
            }

            $merkId = $this->input('aset_merk_id');
            $jenisId = $this->input('aset_jenis_id');
            if (filled($merkId) && filled($jenisId)) {
                $jenisMerkId = AsetJenis::query()->whereKey($jenisId)->value('aset_merk_id');
                if ($jenisMerkId !== null && (int) $jenisMerkId !== (int) $merkId) {
                    $validator->errors()->add(
                        'aset_jenis_id',
                        'Tipe/jenis tidak sesuai dengan merk yang dipilih.',
                    );
                }
            }

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
