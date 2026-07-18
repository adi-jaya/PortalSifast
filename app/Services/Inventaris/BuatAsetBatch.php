<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetFoto;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatAsetBatch
{
    public function __construct(
        private GeneratorKodeAset $generator,
        private PengaturanPenyusutanAset $pengaturanPenyusutan,
    ) {}

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

        if (! empty($data['aset_non_alkes_id'])) {
            return $this->resolveBarangFromNonAlkes($data);
        }

        $barang = AsetBarang::query()->create($this->payloadBarang(
            $data,
            $data['kode_barang'] ?? ('LCL'.strtoupper(substr(uniqid(), -6))),
        ));

        return $barang->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveBarangFromNonAlkes(array $data): int
    {
        $nonAlkes = AsetNonAlkes::query()->findOrFail($data['aset_non_alkes_id']);

        if (! $nonAlkes->isLeaf()) {
            throw ValidationException::withMessages([
                'aset_non_alkes_id' => 'Hanya item katalog paling bawah (leaf) yang boleh dipilih.',
            ]);
        }

        $kodeBarang = $this->kodeBarangDariNonAlkes($nonAlkes);
        $namaBarang = filled($data['nama_barang'] ?? null)
            ? (string) $data['nama_barang']
            : $nonAlkes->nama_alat;

        $existing = AsetBarang::query()
            ->where(function ($q) use ($nonAlkes, $kodeBarang) {
                $q->where('aset_non_alkes_id', $nonAlkes->id)
                    ->orWhere('kode_barang', $kodeBarang);
            })
            ->first();

        $payload = $this->payloadBarang(
            array_merge($data, [
                'nama_barang' => $namaBarang,
                'aset_non_alkes_id' => $nonAlkes->id,
                'kelas_aset' => $data['kelas_aset'] ?? 'non_medis',
            ]),
            $existing?->kode_barang ?? $kodeBarang,
        );

        if ($existing !== null) {
            $existing->update($payload);

            return $existing->id;
        }

        return AsetBarang::query()->create($payload)->id;
    }

    private function kodeBarangDariNonAlkes(AsetNonAlkes $nonAlkes): string
    {
        $kode = $nonAlkes->kode ?: $nonAlkes->alat_code;
        if (filled($kode) && strlen((string) $kode) <= 20) {
            return (string) $kode;
        }

        return 'NA'.substr((string) $nonAlkes->id_alat, -8);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadBarang(array $data, string $kodeBarang): array
    {
        $kelas = $data['kelas_aset'] ?? null;
        $harga = $data['harga'] ?? null;
        $umur = isset($data['umur_ekonomis_bulan']) && $data['umur_ekonomis_bulan'] !== null && $data['umur_ekonomis_bulan'] !== ''
            ? (int) $data['umur_ekonomis_bulan']
            : null;
        $residu = isset($data['nilai_residu']) && $data['nilai_residu'] !== null && $data['nilai_residu'] !== ''
            ? (float) $data['nilai_residu']
            : null;

        $resolved = $this->pengaturanPenyusutan->resolveUntukAset($harga, $umur, $residu, is_string($kelas) ? $kelas : null);

        return [
            'kode_barang' => $kodeBarang,
            'nama_barang' => $data['nama_barang'],
            'aset_kategori_id' => $data['aset_kategori_id'] ?? null,
            'aset_jenis_id' => $data['aset_jenis_id'] ?? null,
            'aset_merk_id' => $data['aset_merk_id'] ?? null,
            'aset_produsen_id' => $data['aset_produsen_id'] ?? null,
            'aset_aspak_alat_id' => $data['aset_aspak_alat_id'] ?? null,
            'aset_non_alkes_id' => $data['aset_non_alkes_id'] ?? null,
            'kelas_aset' => $kelas,
            'wajib_kalibrasi' => (bool) ($data['wajib_kalibrasi'] ?? false),
            'umur_ekonomis_bulan' => $resolved['umur_bulan'],
            'tahun_produksi' => $data['tahun_produksi'] ?? null,
            'no_akl_akd' => $data['no_akl_akd'] ?? null,
            'daya_watt' => $data['daya_watt'] ?? null,
            'level_teknologi' => $data['level_teknologi'] ?? null,
            'tahun_mulai_operasi' => $data['tahun_mulai_operasi'] ?? null,
            'nilai_residu' => $resolved['nilai_residu'],
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
