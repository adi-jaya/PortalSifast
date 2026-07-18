<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetFoto;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BuatAsetBatch
{
    public function __construct(private GeneratorKodeAset $generator) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, Aset>
     */
    public function buat(array $data, ?User $user = null, ?UploadedFile $foto = null): array
    {
        return DB::transaction(function () use ($data, $user, $foto) {
            $ruang = AsetRuang::query()->findOrFail($data['aset_ruang_id']);
            $barangId = $this->resolveBarangId($data);
            $jumlah = max(1, min(50, (int) ($data['jumlah_unit'] ?? 1)));
            $serials = $this->normalizeSerials($data, $jumlah);
            $status = PemetaanStatusAset::dariInputForm(
                $data['status_fungsi'] ?? null,
                $data['tingkat_kerusakan'] ?? null,
            );

            $created = [];
            for ($i = 0; $i < $jumlah; $i++) {
                $kode = $this->generator->generate($ruang->kode_ruang, (int) $data['tahun_registrasi']);

                $aset = Aset::query()->create([
                    'kode_aset' => $kode,
                    'aset_barang_id' => $barangId,
                    'kode_ruang_registrasi' => $ruang->kode_ruang,
                    'aset_ruang_id' => $ruang->id,
                    'aset_distributor_id' => $data['aset_distributor_id'] ?? null,
                    'tahun_registrasi' => $data['tahun_registrasi'],
                    'asal_barang' => $data['asal_barang'] ?? null,
                    'tanggal_pengadaan' => $data['tanggal_pengadaan'] ?? null,
                    'harga' => $data['harga'] ?? null,
                    'kondisi' => $status['kondisi'],
                    'status_fungsi' => $status['status_fungsi'],
                    'tingkat_kerusakan' => $status['tingkat_kerusakan'],
                    'no_seri' => $serials[$i] ?? null,
                    'siklus_hidup' => 'aktif',
                    'diverifikasi_pada' => now(),
                    'diverifikasi_oleh' => $user?->id,
                ]);

                if ($foto !== null && $i === 0) {
                    $path = $foto->store('aset', 'public');
                    AsetFoto::query()->create([
                        'aset_id' => $aset->id,
                        'path' => $path,
                        'utama' => true,
                        'diunggah_oleh' => $user?->id,
                    ]);
                }

                $created[] = $aset;
            }

            if ($barangId) {
                AsetBarang::hitungUlangJumlah($barangId);
            }

            return $created;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveBarangId(array $data): int
    {
        if (! empty($data['aset_barang_id'])) {
            $barang = AsetBarang::query()->findOrFail($data['aset_barang_id']);
            $barang->update($this->payloadBarang($data, $barang->kode_barang));

            return $barang->id;
        }

        $barang = AsetBarang::query()->create($this->payloadBarang(
            $data,
            $data['kode_barang'] ?? ('LCL'.strtoupper(substr(uniqid(), -6))),
        ));

        return $barang->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadBarang(array $data, string $kodeBarang): array
    {
        return [
            'kode_barang' => $kodeBarang,
            'nama_barang' => $data['nama_barang'],
            'aset_kategori_id' => $data['aset_kategori_id'] ?? null,
            'aset_jenis_id' => $data['aset_jenis_id'] ?? null,
            'aset_merk_id' => $data['aset_merk_id'] ?? null,
            'aset_produsen_id' => $data['aset_produsen_id'] ?? null,
            'aset_aspak_alat_id' => $data['aset_aspak_alat_id'] ?? null,
            'kelas_aset' => $data['kelas_aset'] ?? null,
            'wajib_kalibrasi' => (bool) ($data['wajib_kalibrasi'] ?? false),
            'umur_ekonomis_bulan' => $data['umur_ekonomis_bulan'] ?? null,
            'tahun_produksi' => $data['tahun_produksi'] ?? null,
            'no_akl_akd' => $data['no_akl_akd'] ?? null,
            'daya_watt' => $data['daya_watt'] ?? null,
            'level_teknologi' => $data['level_teknologi'] ?? null,
            'tahun_mulai_operasi' => $data['tahun_mulai_operasi'] ?? null,
            'nilai_residu' => $data['nilai_residu'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, ?string>
     */
    private function normalizeSerials(array $data, int $jumlah): array
    {
        $list = $data['no_seri_list'] ?? [];
        if (! is_array($list)) {
            $list = [];
        }

        if ($jumlah === 1 && empty($list) && ! empty($data['no_seri'])) {
            $list = [(string) $data['no_seri']];
        }

        $result = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $val = isset($list[$i]) ? trim((string) $list[$i]) : '';
            $result[] = $val !== '' ? $val : null;
        }

        return $result;
    }
}
