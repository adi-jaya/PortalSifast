<?php

namespace App\Services\BerkasKepegawaian;

use App\Models\AbstractSimrsRiwayat;
use App\Models\Bidang;
use App\Models\Departemen;
use App\Models\JnjJabatan;
use App\Models\KelompokJabatan;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\RiwayatJabatan;
use App\Models\RiwayatPendidikan;
use App\Models\RiwayatPenelitian;
use App\Models\RiwayatPenghargaan;
use App\Models\RiwayatSeminar;
use App\Models\RiwayatSuratPeringatan;
use App\Models\SttsKerja;
use App\Models\SttsWp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KepegawaianReadService
{
    public function __construct(private BerkasKategoriResolver $kategoriResolver) {}

    /**
     * @var list<string>
     */
    private const PROFIL_EDITABLE_FIELDS = [
        'nama',
        'jk',
        'jbtn',
        'bidang',
        'departemen',
        'stts_kerja',
        'stts_wp',
        'pendidikan',
        'jnj_jabatan',
        'kode_kelompok',
        'mulai_kerja',
        'stts_aktif',
        'alamat',
        'kota',
        'tmp_lahir',
        'tgl_lahir',
        'no_ktp',
    ];

    /**
     * @return array<string, mixed>
     */
    public function profil(Pegawai $pegawai): array
    {
        $departemenNama = $this->lookupWhenFilled(
            $pegawai->departemen,
            fn (string $code) => Departemen::query()->where('dep_id', $code)->value('nama'),
        );
        $sttsKerjaLabel = $this->lookupWhenFilled(
            $pegawai->stts_kerja,
            fn (string $code) => SttsKerja::query()->where('stts', $code)->value('ktg'),
        );
        $sttsWpLabel = $this->lookupWhenFilled(
            $pegawai->stts_wp,
            fn (string $code) => SttsWp::query()->where('stts', $code)->value('ktg'),
        );
        $jnjNama = $this->lookupWhenFilled(
            $pegawai->jnj_jabatan,
            fn (string $code) => JnjJabatan::query()->where('kode', $code)->value('nama'),
        );
        $kelompokNama = $this->lookupWhenFilled(
            $pegawai->kode_kelompok,
            fn (string $code) => KelompokJabatan::query()->where('kode_kelompok', $code)->value('nama_kelompok'),
        );

        return [
            'nik' => (string) $pegawai->nik,
            'nama' => (string) $pegawai->nama,
            'jk' => $pegawai->jk,
            'jbtn' => $pegawai->jbtn,
            'bidang' => $pegawai->bidang,
            'departemen' => $pegawai->departemen,
            'departemen_nama' => $departemenNama ?? $pegawai->departemen,
            'stts_kerja' => $pegawai->stts_kerja,
            'stts_kerja_label' => $sttsKerjaLabel ?? $pegawai->stts_kerja,
            'stts_wp' => $pegawai->stts_wp,
            'stts_wp_label' => $sttsWpLabel ?? $pegawai->stts_wp,
            'pendidikan' => $pegawai->pendidikan,
            'jnj_jabatan' => $pegawai->jnj_jabatan,
            'jnj_jabatan_nama' => $jnjNama ?? $pegawai->jnj_jabatan,
            'kode_kelompok' => $pegawai->kode_kelompok,
            'kelompok_jabatan_nama' => $kelompokNama ?? $pegawai->kode_kelompok,
            'mulai_kerja' => $pegawai->mulai_kerja,
            'stts_aktif' => $pegawai->stts_aktif,
            'no_ktp' => $pegawai->no_ktp,
            'tmp_lahir' => $pegawai->tmp_lahir,
            'tgl_lahir' => $pegawai->tgl_lahir,
            'alamat' => $pegawai->alamat,
            'kota' => $pegawai->kota,
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function riwayatForPegawai(Pegawai $pegawai): array
    {
        $pegawaiId = (int) $pegawai->id;

        return [
            'pendidikan' => $this->mapRows(
                RiwayatPendidikan::query()->where('id', $pegawaiId)->orderBy('thn_lulus')->get(),
                ['pendidikan', 'sekolah', 'jurusan', 'thn_lulus', 'kepala', 'pendanaan', 'keterangan', 'status', 'berkas'],
            ),
            'jabatan' => $this->mapRows(
                RiwayatJabatan::query()->where('id', $pegawaiId)->orderByDesc('tgl_sk')->get(),
                ['jabatan', 'tmt_pangkat', 'tmt_pangkat_yad', 'pejabat_penetap', 'nomor_sk', 'tgl_sk', 'dasar_peraturan', 'masa_kerja', 'bln_kerja', 'berkas'],
            ),
            'penghargaan' => $this->mapRows(
                RiwayatPenghargaan::query()->where('id', $pegawaiId)->orderByDesc('tanggal')->get(),
                ['jenis', 'nama_penghargaan', 'tanggal', 'instansi', 'pejabat_pemberi', 'berkas'],
            ),
            'seminar' => $this->mapRows(
                RiwayatSeminar::query()->where('id', $pegawaiId)->orderByDesc('mulai')->get(),
                ['tingkat', 'jenis', 'nama_seminar', 'peranan', 'mulai', 'selesai', 'penyelengara', 'tempat', 'berkas'],
            ),
            'surat_peringatan' => $this->mapRows(
                RiwayatSuratPeringatan::query()->where('id', $pegawaiId)->orderByDesc('tanggal')->get(),
                ['jenis', 'nama_peringatan', 'tanggal', 'berkas'],
            ),
            'penelitian' => $this->mapRows(
                RiwayatPenelitian::query()->where('id', $pegawaiId)->orderByDesc('tahun')->get(),
                ['jenis_penelitian', 'peranan', 'judul_penelitian', 'judul_jurnal', 'tahun', 'biaya_penelitian', 'asal_dana', 'berkas'],
            ),
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function riwayatForNik(string $nik): array
    {
        $pegawai = Pegawai::query()->where('nik', $nik)->first();
        if ($pegawai === null) {
            return [
                'pendidikan' => [],
                'jabatan' => [],
                'penghargaan' => [],
                'seminar' => [],
                'surat_peringatan' => [],
                'penelitian' => [],
            ];
        }

        return $this->riwayatForPegawai($pegawai);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfil(Pegawai $pegawai, array $data): Pegawai
    {
        $payload = array_intersect_key($data, array_flip(self::PROFIL_EDITABLE_FIELDS));

        Pegawai::query()
            ->where('nik', $pegawai->nik)
            ->update($payload);

        return Pegawai::query()->where('nik', $pegawai->nik)->firstOrFail();
    }

    /**
     * @return array<string, list<array{value: string, label: string, description?: string}>>
     */
    public function referensiOptionsForForm(?Pegawai $pegawai = null): array
    {
        $options = [
            'bidang' => $this->sameValueLabelOptions(
                Bidang::query()->orderBy('nama')->get(['nama']),
                'nama',
            ),
            'departemen' => $this->valueLabelOptions(
                Departemen::query()->orderBy('nama')->get(['dep_id', 'nama']),
                'dep_id',
                'nama',
                withDescription: true,
            ),
            'stts_kerja' => $this->codeCategoryOptions(
                SttsKerja::query()->orderBy('stts')->get(['stts', 'ktg']),
            ),
            'stts_wp' => $this->codeCategoryOptions(
                SttsWp::query()->orderBy('stts')->get(['stts', 'ktg']),
            ),
            'pendidikan' => $this->sameValueLabelOptions(
                Pendidikan::query()->orderBy('tingkat')->get(['tingkat']),
                'tingkat',
            ),
            'jnj_jabatan' => $this->valueLabelOptions(
                JnjJabatan::query()->orderBy('kode')->get(['kode', 'nama']),
                'kode',
                'nama',
                withDescription: true,
            ),
            'kelompok_jabatan' => $this->valueLabelOptions(
                KelompokJabatan::query()->orderBy('kode_kelompok')->get(['kode_kelompok', 'nama_kelompok']),
                'kode_kelompok',
                'nama_kelompok',
                withDescription: true,
            ),
        ];

        if ($pegawai === null) {
            return $options;
        }

        $options['bidang'] = $this->ensureOption($options['bidang'], $pegawai->bidang, $pegawai->bidang);
        $options['departemen'] = $this->ensureOption($options['departemen'], $pegawai->departemen, $pegawai->departemen);
        $options['stts_kerja'] = $this->ensureOption($options['stts_kerja'], $pegawai->stts_kerja, $pegawai->stts_kerja);
        $options['stts_wp'] = $this->ensureOption($options['stts_wp'], $pegawai->stts_wp, $pegawai->stts_wp);
        $options['pendidikan'] = $this->ensureOption($options['pendidikan'], $pegawai->pendidikan, $pegawai->pendidikan);
        $options['jnj_jabatan'] = $this->ensureOption($options['jnj_jabatan'], $pegawai->jnj_jabatan, $pegawai->jnj_jabatan);
        $options['kelompok_jabatan'] = $this->ensureOption($options['kelompok_jabatan'], $pegawai->kode_kelompok, $pegawai->kode_kelompok);

        return $options;
    }

    /**
     * @param  list<array{value: string, label: string, description?: string}>  $options
     * @return list<array{value: string, label: string, description?: string}>
     */
    private function ensureOption(array $options, mixed $value, mixed $label): array
    {
        $code = trim((string) ($value ?? ''));
        if ($code === '') {
            return $options;
        }

        foreach ($options as $option) {
            if ($option['value'] === $code) {
                return $options;
            }
        }

        $entry = [
            'value' => $code,
            'label' => (trim((string) ($label ?? '')) !== '' ? (string) $label : $code).' (tidak di master)',
            'description' => 'Nilai lama — pilih ulang dari master',
        ];

        array_unshift($options, $entry);

        return $options;
    }

    /**
     * @return list<array{kode: string, nama_berkas: string, kategori: string|null}>
     */
    public function masterBerkasOptions(): array
    {
        return MasterBerkasPegawai::query()
            ->orderBy('no_urut')
            ->orderBy('nama_berkas')
            ->get(['kode', 'nama_berkas', 'kategori'])
            ->map(fn (MasterBerkasPegawai $row) => [
                'kode' => $row->kode,
                'nama_berkas' => $row->nama_berkas,
                'kategori' => $row->kategori,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{nik: string, nama: string, jbtn: string|null, bidang: string|null, berkas_kategori: string}>
     */
    public function aktifPegawaiOptions(int $limit = 500): array
    {
        return $this->aktifPegawaiQuery()
            ->orderBy('nama')
            ->limit($limit)
            ->get(['nik', 'nama', 'jbtn', 'bidang'])
            ->map(fn (Pegawai $row) => [
                'nik' => $row->nik,
                'nama' => $row->nama,
                'jbtn' => $row->jbtn,
                'bidang' => $row->bidang,
                'berkas_kategori' => $this->kategoriResolver->resolve($row),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{kode: string, nama_berkas: string, kategori: string|null}>
     */
    public function masterBerkasOptionsForPegawai(Pegawai $pegawai): array
    {
        $kategori = $this->kategoriResolver->resolve($pegawai);

        return $this->kategoriResolver->filterMastersByKategori(
            $this->masterBerkasOptions(),
            $kategori,
        );
    }

    /**
     * @return Builder<Pegawai>
     */
    public function aktifPegawaiQuery(): Builder
    {
        return Pegawai::query()->where('stts_aktif', 'AKTIF');
    }

    /**
     * @param  callable(string): mixed  $resolve
     */
    private function lookupWhenFilled(mixed $code, callable $resolve): mixed
    {
        if (! filled($code)) {
            return null;
        }

        return $resolve((string) $code);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array{value: string, label: string}>
     */
    private function sameValueLabelOptions(Collection $rows, string $attribute): array
    {
        return $rows->map(fn (object $row) => [
            'value' => (string) $row->{$attribute},
            'label' => (string) $row->{$attribute},
        ])->values()->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array{value: string, label: string, description?: string}>
     */
    private function valueLabelOptions(
        Collection $rows,
        string $valueAttr,
        string $labelAttr,
        bool $withDescription = false,
    ): array {
        return $rows->map(function (object $row) use ($valueAttr, $labelAttr, $withDescription): array {
            $value = (string) $row->{$valueAttr};
            $option = [
                'value' => $value,
                'label' => (string) $row->{$labelAttr},
            ];

            if ($withDescription) {
                $option['description'] = $value;
            }

            return $option;
        })->values()->all();
    }

    /**
     * @param  Collection<int, object{stts: mixed, ktg: mixed}>  $rows
     * @return list<array{value: string, label: string, description: string}>
     */
    private function codeCategoryOptions(Collection $rows): array
    {
        return $rows->map(function (object $row): array {
            $code = (string) $row->stts;

            return [
                'value' => $code,
                'label' => (string) ($row->ktg ?: $code),
                'description' => $code,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, AbstractSimrsRiwayat>  $rows
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function mapRows(Collection $rows, array $columns): array
    {
        return $rows->map(function (AbstractSimrsRiwayat $row) use ($columns): array {
            $data = [];
            foreach ($columns as $column) {
                $value = $row->{$column};
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                }
                $data[$column] = $value;
            }
            $data['public_url'] = AbstractSimrsRiwayat::publicBerkasUrl(
                isset($data['berkas']) ? (string) $data['berkas'] : null
            );

            return $data;
        })->values()->all();
    }
}
