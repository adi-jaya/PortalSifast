<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetProdusen;
use App\Models\AsetRuang;
use App\Models\AsetSinkron;
use App\Models\Inventaris;
use App\Models\InventarisBarang;
use App\Models\InventarisGambar;
use App\Models\InventarisJenis;
use App\Models\InventarisKategori;
use App\Models\InventarisMerk;
use App\Models\InventarisProdusen;
use App\Models\InventarisRuang;
use Illuminate\Support\Facades\DB;

class SinkronAsetDariSimrs
{
    public function __construct(private GeneratorKodeAset $generator) {}

    /**
     * @return array{
     *     jumlah_baru: int,
     *     jumlah_berubah: int,
     *     jumlah_sama: int,
     *     jumlah_hilang: int,
     *     jumlah_gagal: int,
     *     ringkasan: array<string, mixed>
     * }
     */
    public function preview(): array
    {
        return $this->run(apply: false);
    }

    /**
     * @return array{
     *     jumlah_baru: int,
     *     jumlah_berubah: int,
     *     jumlah_sama: int,
     *     jumlah_hilang: int,
     *     jumlah_gagal: int,
     *     ringkasan: array<string, mixed>,
     *     sinkron_id: int
     * }
     */
    public function apply(?int $dipicuOleh = null): array
    {
        $sinkron = AsetSinkron::query()->create([
            'dipicu_oleh' => $dipicuOleh,
            'status' => 'berjalan',
            'mulai_pada' => now(),
        ]);

        try {
            $result = $this->run(apply: true);
            $sinkron->update([
                'status' => 'selesai',
                'selesai_pada' => now(),
                'jumlah_baru' => $result['jumlah_baru'],
                'jumlah_berubah' => $result['jumlah_berubah'],
                'jumlah_sama' => $result['jumlah_sama'],
                'jumlah_hilang' => $result['jumlah_hilang'],
                'jumlah_gagal' => $result['jumlah_gagal'],
                'ringkasan' => $result['ringkasan'],
            ]);

            return [...$result, 'sinkron_id' => $sinkron->id];
        } catch (\Throwable $e) {
            $sinkron->update([
                'status' => 'gagal',
                'selesai_pada' => now(),
                'ringkasan' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    /**
     * @return array{
     *     jumlah_baru: int,
     *     jumlah_berubah: int,
     *     jumlah_sama: int,
     *     jumlah_hilang: int,
     *     jumlah_gagal: int,
     *     ringkasan: array<string, mixed>
     * }
     */
    private function run(bool $apply): array
    {
        $baru = 0;
        $berubah = 0;
        $sama = 0;
        $hilang = 0;
        $gagal = 0;
        $errors = [];
        $master = [
            'ruang_baru' => 0,
            'ruang_berubah' => 0,
            'barang_baru' => 0,
            'barang_berubah' => 0,
        ];

        $fallbackKode = (string) config('aset.kode_ruang_fallback', 'TANPA');
        $fallbackNama = (string) config('aset.nama_ruang_fallback', 'Tanpa Ruang');

        try {
            $ruangSumber = InventarisRuang::query()->orderBy('id_ruang')->get();
            $barangSumber = InventarisBarang::query()->orderBy('kode_barang')->get();
            $asetSumber = Inventaris::query()->orderBy('no_inventaris')->get();
            $fotoMap = InventarisGambar::query()->get()->keyBy('no_inventaris');
        } catch (\Throwable $e) {
            return [
                'jumlah_baru' => 0,
                'jumlah_berubah' => 0,
                'jumlah_sama' => 0,
                'jumlah_hilang' => 0,
                'jumlah_gagal' => 1,
                'ringkasan' => ['error' => 'Gagal membaca SIMRS: '.$e->getMessage()],
            ];
        }

        $process = function () use (
            $apply,
            $ruangSumber,
            $barangSumber,
            $asetSumber,
            $fotoMap,
            $fallbackKode,
            $fallbackNama,
            &$baru,
            &$berubah,
            &$sama,
            &$hilang,
            &$gagal,
            &$errors,
            &$master
        ): void {
            $seenRuang = [];
            foreach ($ruangSumber as $ruang) {
                $seenRuang[] = $ruang->id_ruang;
                $payload = [
                    'nama_ruang' => $ruang->nama_ruang,
                    'sumber_hilang_pada' => null,
                ];
                $existing = AsetRuang::query()->where('kode_ruang', $ruang->id_ruang)->first();
                if ($existing === null) {
                    $master['ruang_baru']++;
                    if ($apply) {
                        AsetRuang::query()->create([
                            'kode_ruang' => $ruang->id_ruang,
                            ...$payload,
                        ]);
                    }
                } elseif ($existing->nama_ruang !== $ruang->nama_ruang || $existing->sumber_hilang_pada !== null) {
                    $master['ruang_berubah']++;
                    if ($apply) {
                        $existing->update($payload);
                    }
                }
            }

            if (! in_array($fallbackKode, $seenRuang, true)) {
                $seenRuang[] = $fallbackKode;
                if ($apply && AsetRuang::query()->where('kode_ruang', $fallbackKode)->doesntExist()) {
                    AsetRuang::query()->create([
                        'kode_ruang' => $fallbackKode,
                        'nama_ruang' => $fallbackNama,
                    ]);
                }
            }

            if ($apply) {
                AsetRuang::query()
                    ->whereNotIn('kode_ruang', $seenRuang)
                    ->whereNull('sumber_hilang_pada')
                    ->update(['sumber_hilang_pada' => now()]);
            }

            $this->sinkronMasterLookup($apply);

            $kategoriMap = AsetKategori::query()->pluck('id', 'kode_kategori');
            $jenisMap = AsetJenis::query()->pluck('id', 'kode_jenis');
            $merkMap = AsetMerk::query()->pluck('id', 'kode_merk');
            $produsenMap = AsetProdusen::query()->pluck('id', 'kode_produsen');

            $seenBarang = [];
            foreach ($barangSumber as $barang) {
                $seenBarang[] = $barang->kode_barang;
                $hash = $this->hashBarang($barang);
                $payload = [
                    'nama_barang' => $barang->nama_barang,
                    'kode_produsen' => $barang->kode_produsen,
                    'id_merk' => $barang->id_merk,
                    'id_kategori' => $barang->id_kategori,
                    'id_jenis' => $barang->id_jenis,
                    'aset_kategori_id' => $barang->id_kategori ? $kategoriMap->get($barang->id_kategori) : null,
                    'aset_jenis_id' => $barang->id_jenis ? $jenisMap->get($barang->id_jenis) : null,
                    'aset_merk_id' => $barang->id_merk ? $merkMap->get($barang->id_merk) : null,
                    'aset_produsen_id' => $barang->kode_produsen ? $produsenMap->get($barang->kode_produsen) : null,
                    'tahun_produksi' => $this->normalizeYear($barang->thn_produksi),
                    'hash_sumber' => $hash,
                    'disinkron_pada' => now(),
                    'sumber_hilang_pada' => null,
                ];

                $existing = AsetBarang::query()->where('kode_barang', $barang->kode_barang)->first();
                if ($existing === null) {
                    $master['barang_baru']++;
                    if ($apply) {
                        AsetBarang::query()->create([
                            'kode_barang' => $barang->kode_barang,
                            ...$payload,
                        ]);
                    }
                } elseif ($existing->hash_sumber !== $hash || $existing->sumber_hilang_pada !== null) {
                    $master['barang_berubah']++;
                    if ($apply) {
                        $existing->update($payload);
                    }
                }
            }

            if ($apply) {
                AsetBarang::query()
                    ->whereNotIn('kode_barang', $seenBarang)
                    ->whereNull('sumber_hilang_pada')
                    ->update(['sumber_hilang_pada' => now()]);
            }

            $ruangLokal = AsetRuang::query()->get()->keyBy('kode_ruang');
            $barangLokal = AsetBarang::query()->get()->keyBy('kode_barang');
            $seenAset = [];

            foreach ($asetSumber as $inv) {
                try {
                    $seenAset[] = $inv->no_inventaris;
                    $kodeRuang = filled($inv->id_ruang) ? (string) $inv->id_ruang : $fallbackKode;
                    $tahun = $this->resolveTahun($inv->tgl_pengadaan);
                    $pathFoto = $fotoMap->get($inv->no_inventaris)?->photo;
                    $hash = $this->hashAset($inv, $pathFoto);

                    $existing = Aset::query()->where('no_simrs', $inv->no_inventaris)->first();
                    $statusMap = PemetaanStatusAset::dariStatusSimrs($inv->status_barang);
                    $sourcePayload = [
                        'aset_barang_id' => $barangLokal->get($inv->kode_barang)?->id,
                        'asal_barang' => $inv->asal_barang,
                        'tanggal_pengadaan' => $inv->tgl_pengadaan ?: null,
                        'harga' => $inv->harga,
                        'status_sumber' => $inv->status_barang,
                        'kondisi' => $statusMap['kondisi'],
                        'status_fungsi' => $statusMap['status_fungsi'],
                        'tingkat_kerusakan' => $statusMap['tingkat_kerusakan'],
                        'path_foto_sumber' => $pathFoto,
                        'hash_sumber' => $hash,
                        'disinkron_pada' => now(),
                        'sumber_hilang_pada' => null,
                    ];

                    if ($existing === null) {
                        $baru++;
                        if ($apply) {
                            if (! $ruangLokal->has($kodeRuang) && AsetRuang::query()->where('kode_ruang', $kodeRuang)->doesntExist()) {
                                AsetRuang::query()->create([
                                    'kode_ruang' => $kodeRuang,
                                    'nama_ruang' => $kodeRuang === $fallbackKode ? $fallbackNama : $kodeRuang,
                                ]);
                                $ruangLokal = AsetRuang::query()->get()->keyBy('kode_ruang');
                            }

                            $kodeAset = $this->generator->generate($kodeRuang, $tahun);
                            Aset::query()->create([
                                'kode_aset' => $kodeAset,
                                'no_simrs' => $inv->no_inventaris,
                                'kode_ruang_registrasi' => $kodeRuang,
                                'aset_ruang_id' => $ruangLokal->get($kodeRuang)?->id,
                                'tahun_registrasi' => $tahun,
                                'kondisi' => $statusMap['kondisi'],
                                'status_fungsi' => $statusMap['status_fungsi'],
                                'tingkat_kerusakan' => $statusMap['tingkat_kerusakan'],
                                'siklus_hidup' => 'draf',
                                ...$sourcePayload,
                            ]);
                        }
                    } elseif ($existing->hash_sumber !== $hash || $existing->sumber_hilang_pada !== null) {
                        $berubah++;
                        if ($apply) {
                            $update = $sourcePayload;
                            if ($existing->diverifikasi_pada === null) {
                                $update['aset_ruang_id'] = $ruangLokal->get($kodeRuang)?->id;
                                $update['kode_ruang_registrasi'] = $existing->kode_ruang_registrasi ?: $kodeRuang;
                                if ($existing->tahun_registrasi === null) {
                                    $update['tahun_registrasi'] = $tahun;
                                }
                            }
                            $existing->update($update);
                        }
                    } else {
                        $sama++;
                    }
                } catch (\Throwable $e) {
                    $gagal++;
                    if (count($errors) < 20) {
                        $errors[] = [
                            'no_simrs' => $inv->no_inventaris,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $missingQuery = Aset::query()
                ->whereNotNull('no_simrs')
                ->when(count($seenAset) > 0, fn ($q) => $q->whereNotIn('no_simrs', $seenAset))
                ->when(count($seenAset) === 0, fn ($q) => $q->whereRaw('1 = 1'))
                ->whereNull('sumber_hilang_pada');

            $hilang = (clone $missingQuery)->count();
            if ($apply && $hilang > 0) {
                $missingQuery->update(['sumber_hilang_pada' => now()]);
            }
        };

        if ($apply) {
            DB::transaction($process);
        } else {
            $process();
        }

        return [
            'jumlah_baru' => $baru,
            'jumlah_berubah' => $berubah,
            'jumlah_sama' => $sama,
            'jumlah_hilang' => $hilang,
            'jumlah_gagal' => $gagal,
            'ringkasan' => [
                'ruang_sumber' => $ruangSumber->count(),
                'barang_sumber' => $barangSumber->count(),
                'aset_sumber' => $asetSumber->count(),
                'master' => $master,
                'errors' => $errors,
            ],
        ];
    }

    private function hashBarang(InventarisBarang $barang): string
    {
        return hash('sha256', implode('|', [
            $barang->kode_barang,
            $barang->nama_barang,
            $barang->kode_produsen,
            $barang->id_merk,
            $barang->id_kategori,
            $barang->id_jenis,
            $barang->thn_produksi,
            $barang->isbn,
        ]));
    }

    private function hashAset(Inventaris $inv, ?string $pathFoto): string
    {
        return hash('sha256', implode('|', [
            $inv->no_inventaris,
            $inv->kode_barang,
            $inv->asal_barang,
            $inv->tgl_pengadaan,
            $inv->harga,
            $inv->status_barang,
            $inv->id_ruang,
            $pathFoto,
        ]));
    }

    private function resolveTahun(mixed $tanggal): int
    {
        if ($tanggal === null || $tanggal === '' || $tanggal === '0000-00-00') {
            return (int) now()->year;
        }

        $year = (int) date('Y', strtotime((string) $tanggal));

        if ($year < 1900 || $year > 2100) {
            return (int) now()->year;
        }

        return $year;
    }

    private function normalizeYear(mixed $year): ?int
    {
        if ($year === null || $year === '' || $year === 0 || $year === '0000') {
            return null;
        }

        $value = (int) $year;

        return $value >= 1900 && $value <= 2100 ? $value : null;
    }

    private function sinkronMasterLookup(bool $apply): void
    {
        try {
            foreach (InventarisKategori::query()->get() as $row) {
                if ($apply) {
                    AsetKategori::query()->updateOrCreate(
                        ['kode_kategori' => $row->id_kategori],
                        ['nama_kategori' => $row->nama_kategori, 'disinkron_pada' => now()],
                    );
                }
            }
            foreach (InventarisJenis::query()->get() as $row) {
                if ($apply) {
                    AsetJenis::query()->updateOrCreate(
                        ['kode_jenis' => $row->id_jenis],
                        ['nama_jenis' => $row->nama_jenis, 'disinkron_pada' => now()],
                    );
                }
            }
            foreach (InventarisMerk::query()->get() as $row) {
                if ($apply) {
                    AsetMerk::query()->updateOrCreate(
                        ['kode_merk' => $row->id_merk],
                        ['nama_merk' => $row->nama_merk, 'disinkron_pada' => now()],
                    );
                }
            }
            foreach (InventarisProdusen::query()->get() as $row) {
                if ($apply) {
                    AsetProdusen::query()->updateOrCreate(
                        ['kode_produsen' => $row->kode_produsen],
                        [
                            'nama_produsen' => $row->nama_produsen,
                            'alamat' => $row->alamat_produsen,
                            'no_telp' => $row->no_telp,
                            'email' => $row->email,
                            'website' => $row->website_produsen,
                            'disinkron_pada' => now(),
                        ],
                    );
                }
            }
        } catch (\Throwable) {
            // Master SIMRS tidak tersedia — lewati tanpa gagalkan sinkron utama.
        }
    }
}
