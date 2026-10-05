<?php

namespace App\Services\BerkasKepegawaian;

use App\Models\Pegawai;
use App\Models\RiwayatJabatan;
use App\Models\RiwayatPendidikan;
use App\Models\RiwayatPenelitian;
use App\Models\RiwayatPenghargaan;
use App\Models\RiwayatSeminar;
use App\Models\RiwayatSuratPeringatan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class KepegawaianRiwayatService
{
    /**
     * @param  array{jenis: string, nama_peringatan: string, tanggal: string}  $data
     */
    public function storeSuratPeringatan(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatSuratPeringatan::query(),
            (int) $pegawai->id,
            ['nama_peringatan' => $data['nama_peringatan'], 'tanggal' => $data['tanggal']],
            [
                'jenis' => $data['jenis'],
                'nama_peringatan' => $data['nama_peringatan'],
                'tanggal' => $data['tanggal'],
                'berkas' => null,
            ],
            'Surat peringatan dengan nama dan tanggal tersebut sudah ada.',
        );
    }

    /**
     * @param  array{
     *     original_nama_peringatan: string,
     *     original_tanggal: string,
     *     jenis: string,
     *     nama_peringatan: string,
     *     tanggal: string
     * }  $data
     */
    public function updateSuratPeringatan(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatSuratPeringatan::query(),
            (int) $pegawai->id,
            ['nama_peringatan' => $data['original_nama_peringatan'], 'tanggal' => $data['original_tanggal']],
            ['nama_peringatan' => $data['nama_peringatan'], 'tanggal' => $data['tanggal']],
            [
                'jenis' => $data['jenis'],
                'nama_peringatan' => $data['nama_peringatan'],
                'tanggal' => $data['tanggal'],
            ],
            'Data surat peringatan tidak ditemukan.',
            'Surat peringatan dengan nama dan tanggal tersebut sudah ada.',
        );
    }

    public function destroySuratPeringatan(Pegawai $pegawai, string $namaPeringatan, string $tanggal): void
    {
        $this->destroyComposite(
            RiwayatSuratPeringatan::query(),
            (int) $pegawai->id,
            ['nama_peringatan' => $namaPeringatan, 'tanggal' => $tanggal],
            'Data surat peringatan tidak ditemukan.',
        );
    }

    /**
     * @param  array{jenis: string, nama_penghargaan: string, tanggal: string, instansi: string, pejabat_pemberi: string}  $data
     */
    public function storePenghargaan(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatPenghargaan::query(),
            (int) $pegawai->id,
            ['nama_penghargaan' => $data['nama_penghargaan'], 'tanggal' => $data['tanggal']],
            [
                'jenis' => $data['jenis'],
                'nama_penghargaan' => $data['nama_penghargaan'],
                'tanggal' => $data['tanggal'],
                'instansi' => $data['instansi'],
                'pejabat_pemberi' => $data['pejabat_pemberi'],
                'berkas' => null,
            ],
            'Penghargaan dengan nama dan tanggal tersebut sudah ada.',
        );
    }

    /**
     * @param  array{
     *     original_nama_penghargaan: string,
     *     original_tanggal: string,
     *     jenis: string,
     *     nama_penghargaan: string,
     *     tanggal: string,
     *     instansi: string,
     *     pejabat_pemberi: string
     * }  $data
     */
    public function updatePenghargaan(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatPenghargaan::query(),
            (int) $pegawai->id,
            ['nama_penghargaan' => $data['original_nama_penghargaan'], 'tanggal' => $data['original_tanggal']],
            ['nama_penghargaan' => $data['nama_penghargaan'], 'tanggal' => $data['tanggal']],
            [
                'jenis' => $data['jenis'],
                'nama_penghargaan' => $data['nama_penghargaan'],
                'tanggal' => $data['tanggal'],
                'instansi' => $data['instansi'],
                'pejabat_pemberi' => $data['pejabat_pemberi'],
            ],
            'Data penghargaan tidak ditemukan.',
            'Penghargaan dengan nama dan tanggal tersebut sudah ada.',
        );
    }

    public function destroyPenghargaan(Pegawai $pegawai, string $nama, string $tanggal): void
    {
        $this->destroyComposite(
            RiwayatPenghargaan::query(),
            (int) $pegawai->id,
            ['nama_penghargaan' => $nama, 'tanggal' => $tanggal],
            'Data penghargaan tidak ditemukan.',
        );
    }

    /**
     * @param  array{
     *     pendidikan: string,
     *     sekolah: string,
     *     jurusan: string,
     *     thn_lulus: string|int,
     *     kepala: string,
     *     pendanaan: string,
     *     keterangan: string,
     *     status: string
     * }  $data
     */
    public function storePendidikan(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatPendidikan::query(),
            (int) $pegawai->id,
            ['pendidikan' => $data['pendidikan'], 'sekolah' => $data['sekolah']],
            [
                'pendidikan' => $data['pendidikan'],
                'sekolah' => $data['sekolah'],
                'jurusan' => $data['jurusan'],
                'thn_lulus' => $data['thn_lulus'],
                'kepala' => $data['kepala'],
                'pendanaan' => $data['pendanaan'],
                'keterangan' => $data['keterangan'],
                'status' => $data['status'],
                'berkas' => '',
            ],
            'Riwayat pendidikan dengan tingkat dan sekolah tersebut sudah ada.',
            dateKeys: [],
        );
    }

    /**
     * @param  array{
     *     original_pendidikan: string,
     *     original_sekolah: string,
     *     pendidikan: string,
     *     sekolah: string,
     *     jurusan: string,
     *     thn_lulus: string|int,
     *     kepala: string,
     *     pendanaan: string,
     *     keterangan: string,
     *     status: string
     * }  $data
     */
    public function updatePendidikan(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatPendidikan::query(),
            (int) $pegawai->id,
            ['pendidikan' => $data['original_pendidikan'], 'sekolah' => $data['original_sekolah']],
            ['pendidikan' => $data['pendidikan'], 'sekolah' => $data['sekolah']],
            [
                'pendidikan' => $data['pendidikan'],
                'sekolah' => $data['sekolah'],
                'jurusan' => $data['jurusan'],
                'thn_lulus' => $data['thn_lulus'],
                'kepala' => $data['kepala'],
                'pendanaan' => $data['pendanaan'],
                'keterangan' => $data['keterangan'],
                'status' => $data['status'],
            ],
            'Data riwayat pendidikan tidak ditemukan.',
            'Riwayat pendidikan dengan tingkat dan sekolah tersebut sudah ada.',
            dateKeys: [],
        );
    }

    public function destroyPendidikan(Pegawai $pegawai, string $pendidikan, string $sekolah): void
    {
        $this->destroyComposite(
            RiwayatPendidikan::query(),
            (int) $pegawai->id,
            ['pendidikan' => $pendidikan, 'sekolah' => $sekolah],
            'Data riwayat pendidikan tidak ditemukan.',
            dateKeys: [],
        );
    }

    /**
     * @param  array{
     *     jabatan: string,
     *     tmt_pangkat: string,
     *     tmt_pangkat_yad: string,
     *     pejabat_penetap: string,
     *     nomor_sk: string,
     *     tgl_sk: string,
     *     dasar_peraturan: string,
     *     masa_kerja: int,
     *     bln_kerja: int
     * }  $data
     */
    public function storeJabatan(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatJabatan::query(),
            (int) $pegawai->id,
            ['jabatan' => $data['jabatan']],
            [
                'jabatan' => $data['jabatan'],
                'tmt_pangkat' => $data['tmt_pangkat'],
                'tmt_pangkat_yad' => $data['tmt_pangkat_yad'],
                'pejabat_penetap' => $data['pejabat_penetap'],
                'nomor_sk' => $data['nomor_sk'],
                'tgl_sk' => $data['tgl_sk'],
                'dasar_peraturan' => $data['dasar_peraturan'],
                'masa_kerja' => $data['masa_kerja'],
                'bln_kerja' => $data['bln_kerja'],
                'berkas' => '',
            ],
            'Riwayat jabatan tersebut sudah ada.',
            dateKeys: [],
        );
    }

    /**
     * @param  array{
     *     original_jabatan: string,
     *     jabatan: string,
     *     tmt_pangkat: string,
     *     tmt_pangkat_yad: string,
     *     pejabat_penetap: string,
     *     nomor_sk: string,
     *     tgl_sk: string,
     *     dasar_peraturan: string,
     *     masa_kerja: int,
     *     bln_kerja: int
     * }  $data
     */
    public function updateJabatan(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatJabatan::query(),
            (int) $pegawai->id,
            ['jabatan' => $data['original_jabatan']],
            ['jabatan' => $data['jabatan']],
            [
                'jabatan' => $data['jabatan'],
                'tmt_pangkat' => $data['tmt_pangkat'],
                'tmt_pangkat_yad' => $data['tmt_pangkat_yad'],
                'pejabat_penetap' => $data['pejabat_penetap'],
                'nomor_sk' => $data['nomor_sk'],
                'tgl_sk' => $data['tgl_sk'],
                'dasar_peraturan' => $data['dasar_peraturan'],
                'masa_kerja' => $data['masa_kerja'],
                'bln_kerja' => $data['bln_kerja'],
            ],
            'Data riwayat jabatan tidak ditemukan.',
            'Riwayat jabatan tersebut sudah ada.',
            dateKeys: [],
        );
    }

    public function destroyJabatan(Pegawai $pegawai, string $jabatan): void
    {
        $this->destroyComposite(
            RiwayatJabatan::query(),
            (int) $pegawai->id,
            ['jabatan' => $jabatan],
            'Data riwayat jabatan tidak ditemukan.',
            dateKeys: [],
        );
    }

    /**
     * @param  array{
     *     tingkat: string,
     *     jenis: string,
     *     nama_seminar: string,
     *     peranan: string,
     *     mulai: string,
     *     selesai: string,
     *     penyelengara: string,
     *     tempat: string
     * }  $data
     */
    public function storeSeminar(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatSeminar::query(),
            (int) $pegawai->id,
            ['nama_seminar' => $data['nama_seminar'], 'mulai' => $data['mulai']],
            [
                'tingkat' => $data['tingkat'],
                'jenis' => $data['jenis'],
                'nama_seminar' => $data['nama_seminar'],
                'peranan' => $data['peranan'],
                'mulai' => $data['mulai'],
                'selesai' => $data['selesai'],
                'penyelengara' => $data['penyelengara'],
                'tempat' => $data['tempat'],
                'berkas' => '',
            ],
            'Seminar dengan nama dan tanggal mulai tersebut sudah ada.',
            dateKeys: ['mulai'],
        );
    }

    /**
     * @param  array{
     *     original_nama_seminar: string,
     *     original_mulai: string,
     *     tingkat: string,
     *     jenis: string,
     *     nama_seminar: string,
     *     peranan: string,
     *     mulai: string,
     *     selesai: string,
     *     penyelengara: string,
     *     tempat: string
     * }  $data
     */
    public function updateSeminar(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatSeminar::query(),
            (int) $pegawai->id,
            ['nama_seminar' => $data['original_nama_seminar'], 'mulai' => $data['original_mulai']],
            ['nama_seminar' => $data['nama_seminar'], 'mulai' => $data['mulai']],
            [
                'tingkat' => $data['tingkat'],
                'jenis' => $data['jenis'],
                'nama_seminar' => $data['nama_seminar'],
                'peranan' => $data['peranan'],
                'mulai' => $data['mulai'],
                'selesai' => $data['selesai'],
                'penyelengara' => $data['penyelengara'],
                'tempat' => $data['tempat'],
            ],
            'Data seminar tidak ditemukan.',
            'Seminar dengan nama dan tanggal mulai tersebut sudah ada.',
            dateKeys: ['mulai'],
        );
    }

    public function destroySeminar(Pegawai $pegawai, string $namaSeminar, string $mulai): void
    {
        $this->destroyComposite(
            RiwayatSeminar::query(),
            (int) $pegawai->id,
            ['nama_seminar' => $namaSeminar, 'mulai' => $mulai],
            'Data seminar tidak ditemukan.',
            dateKeys: ['mulai'],
        );
    }

    /**
     * @param  array{
     *     jenis_penelitian: string,
     *     peranan: string,
     *     judul_penelitian: string,
     *     judul_jurnal: string,
     *     tahun: string|int,
     *     biaya_penelitian: float|int|null,
     *     asal_dana: string
     * }  $data
     */
    public function storePenelitian(Pegawai $pegawai, array $data): void
    {
        $this->storeComposite(
            RiwayatPenelitian::query(),
            (int) $pegawai->id,
            ['judul_penelitian' => $data['judul_penelitian'], 'tahun' => $data['tahun']],
            [
                'jenis_penelitian' => $data['jenis_penelitian'],
                'peranan' => $data['peranan'],
                'judul_penelitian' => $data['judul_penelitian'],
                'judul_jurnal' => $data['judul_jurnal'],
                'tahun' => $data['tahun'],
                'biaya_penelitian' => $data['biaya_penelitian'],
                'asal_dana' => $data['asal_dana'],
                'berkas' => '',
            ],
            'Penelitian dengan judul dan tahun tersebut sudah ada.',
            dateKeys: [],
        );
    }

    /**
     * @param  array{
     *     original_judul_penelitian: string,
     *     original_tahun: string|int,
     *     jenis_penelitian: string,
     *     peranan: string,
     *     judul_penelitian: string,
     *     judul_jurnal: string,
     *     tahun: string|int,
     *     biaya_penelitian: float|int|null,
     *     asal_dana: string
     * }  $data
     */
    public function updatePenelitian(Pegawai $pegawai, array $data): void
    {
        $this->updateComposite(
            RiwayatPenelitian::query(),
            (int) $pegawai->id,
            ['judul_penelitian' => $data['original_judul_penelitian'], 'tahun' => $data['original_tahun']],
            ['judul_penelitian' => $data['judul_penelitian'], 'tahun' => $data['tahun']],
            [
                'jenis_penelitian' => $data['jenis_penelitian'],
                'peranan' => $data['peranan'],
                'judul_penelitian' => $data['judul_penelitian'],
                'judul_jurnal' => $data['judul_jurnal'],
                'tahun' => $data['tahun'],
                'biaya_penelitian' => $data['biaya_penelitian'],
                'asal_dana' => $data['asal_dana'],
            ],
            'Data penelitian tidak ditemukan.',
            'Penelitian dengan judul dan tahun tersebut sudah ada.',
            dateKeys: [],
        );
    }

    public function destroyPenelitian(Pegawai $pegawai, string $judul, string|int $tahun): void
    {
        $this->destroyComposite(
            RiwayatPenelitian::query(),
            (int) $pegawai->id,
            ['judul_penelitian' => $judul, 'tahun' => $tahun],
            'Data penelitian tidak ditemukan.',
            dateKeys: [],
        );
    }

    /**
     * @param  Builder<Model>  $base
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $dateKeys
     */
    private function storeComposite(
        Builder $base,
        int $pegawaiId,
        array $identity,
        array $payload,
        string $duplicateMessage,
        array $dateKeys = ['tanggal'],
    ): void {
        if ($this->identityQuery(clone $base, $pegawaiId, $identity, $dateKeys)->exists()) {
            throw new InvalidArgumentException($duplicateMessage);
        }

        $modelClass = $base->getModel()::class;
        $modelClass::query()->create([
            'id' => $pegawaiId,
            ...$payload,
        ]);
    }

    /**
     * @param  Builder<Model>  $base
     * @param  array<string, mixed>  $originalIdentity
     * @param  array<string, mixed>  $newIdentity
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $dateKeys
     */
    private function updateComposite(
        Builder $base,
        int $pegawaiId,
        array $originalIdentity,
        array $newIdentity,
        array $payload,
        string $missingMessage,
        string $duplicateMessage,
        array $dateKeys = ['tanggal'],
    ): void {
        $existing = $this->identityQuery(clone $base, $pegawaiId, $originalIdentity, $dateKeys)->first();
        if ($existing === null) {
            throw new InvalidArgumentException($missingMessage);
        }

        $changed = false;
        foreach ($newIdentity as $key => $value) {
            if ((string) $originalIdentity[$key] !== (string) $value) {
                $changed = true;
                break;
            }
        }

        if ($changed && $this->identityQuery(clone $base, $pegawaiId, $newIdentity, $dateKeys)->exists()) {
            throw new InvalidArgumentException($duplicateMessage);
        }

        $this->identityQuery(clone $base, $pegawaiId, $originalIdentity, $dateKeys)->update($payload);
    }

    /**
     * @param  Builder<Model>  $base
     * @param  array<string, mixed>  $identity
     * @param  list<string>  $dateKeys
     */
    private function destroyComposite(
        Builder $base,
        int $pegawaiId,
        array $identity,
        string $missingMessage,
        array $dateKeys = ['tanggal'],
    ): void {
        $deleted = $this->identityQuery(clone $base, $pegawaiId, $identity, $dateKeys)->delete();
        if ($deleted === 0) {
            throw new InvalidArgumentException($missingMessage);
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $identity
     * @param  list<string>  $dateKeys
     * @return Builder<Model>
     */
    private function identityQuery(Builder $query, int $pegawaiId, array $identity, array $dateKeys): Builder
    {
        $query->where('id', $pegawaiId);

        foreach ($identity as $column => $value) {
            if (in_array($column, $dateKeys, true)) {
                $query->whereDate($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }
}
