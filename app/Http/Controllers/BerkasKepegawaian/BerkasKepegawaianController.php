<?php

namespace App\Http\Controllers\BerkasKepegawaian;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatJabatanRequest;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatPendidikanRequest;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatPenelitianRequest;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatPenghargaanRequest;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatSeminarRequest;
use App\Http\Requests\BerkasKepegawaian\DestroyRiwayatSuratPeringatanRequest;
use App\Http\Requests\BerkasKepegawaian\ReplaceBerkasPegawaiRequest;
use App\Http\Requests\BerkasKepegawaian\StoreBerkasPegawaiRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatJabatanRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatPendidikanRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatPenelitianRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatPenghargaanRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatSeminarRequest;
use App\Http\Requests\BerkasKepegawaian\StoreRiwayatSuratPeringatanRequest;
use App\Http\Requests\BerkasKepegawaian\UpdatePegawaiProfilRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatJabatanRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatPendidikanRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatPenelitianRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatPenghargaanRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatSeminarRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateRiwayatSuratPeringatanRequest;
use App\Models\BerkasPegawai;
use App\Models\Pegawai;
use App\Services\BerkasKepegawaian\BerkasKategoriResolver;
use App\Services\BerkasKepegawaian\BerkasKepegawaianService;
use App\Services\BerkasKepegawaian\KepegawaianReadService;
use App\Services\BerkasKepegawaian\KepegawaianRiwayatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BerkasKepegawaianController extends Controller
{
    public function __construct(
        private BerkasKepegawaianService $service,
        private KepegawaianReadService $readService,
        private KepegawaianRiwayatService $riwayatService,
        private BerkasKategoriResolver $kategoriResolver,
    ) {}

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $departemen = trim((string) $request->query('departemen', ''));
        $bidang = trim((string) $request->query('bidang', ''));

        $referensi = $this->readService->referensiOptionsForForm();

        $pegawai = $this->readService->aktifPegawaiQuery()
            ->with('departemen_unit:dep_id,nama')
            ->withCount('berkasPegawai')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('nik', 'like', "%{$q}%")
                        ->orWhere('nama', 'like', "%{$q}%");
                });
            })
            ->when($departemen !== '', fn ($query) => $query->where('departemen', $departemen))
            ->when($bidang !== '', fn ($query) => $query->where('bidang', $bidang))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Pegawai $p) => [
                'nik' => $p->nik,
                'nama' => $p->nama,
                'jbtn' => $p->jbtn,
                'bidang' => $p->bidang,
                'departemen' => $p->departemen,
                'departemen_nama' => $p->departemen_unit?->nama ?? $p->departemen,
                'berkas_count' => (int) $p->berkas_pegawai_count,
            ]);

        return Inertia::render('berkas-kepegawaian/index', [
            'pegawai' => $pegawai,
            'pegawaiOptions' => $this->readService->aktifPegawaiOptions(),
            'filterOptions' => [
                'departemen' => $referensi['departemen'],
                'bidang' => $referensi['bidang'],
            ],
            'filters' => [
                'q' => $q,
                'departemen' => $departemen,
                'bidang' => $bidang,
            ],
        ]);
    }

    public function show(string $nik): Response
    {
        $pegawai = $this->findAktifPegawaiOrFail($nik);

        $berkasRows = BerkasPegawai::query()
            ->where('nik', $nik)
            ->with('master')
            ->get()
            ->map(fn (BerkasPegawai $row) => [
                'kode_berkas' => $row->kode_berkas,
                'nama_berkas' => $row->master?->nama_berkas ?? $row->kode_berkas,
                'kategori' => $row->master?->kategori ?? 'Lainnya',
                'no_urut' => (int) ($row->master?->no_urut ?? 9999),
                'tgl_uploud' => $row->tgl_uploud,
                'berkas' => $row->berkas,
                'public_url' => $row->publicUrl(),
            ])
            ->sortBy([
                ['no_urut', 'asc'],
                ['nama_berkas', 'asc'],
            ])
            ->values();

        $sections = $berkasRows
            ->groupBy('kategori')
            ->map(fn ($items, $kategori) => [
                'kategori' => (string) $kategori,
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('berkas-kepegawaian/show', [
            'pegawai' => $this->readService->profil($pegawai),
            'riwayat' => $this->readService->riwayatForPegawai($pegawai),
            'sections' => $sections,
            'masterOptions' => $this->readService->masterBerkasOptionsForPegawai($pegawai),
            'masterOptionsAll' => $this->readService->masterBerkasOptions(),
            'berkasKategori' => $this->kategoriResolver->resolve($pegawai),
            'uploadedKodes' => $berkasRows->pluck('kode_berkas')->values()->all(),
            'referensiOptions' => $this->readService->referensiOptionsForForm($pegawai),
        ]);
    }

    public function updateProfil(UpdatePegawaiProfilRequest $request, string $nik): RedirectResponse
    {
        $pegawai = $this->findAktifPegawaiOrFail($nik);

        try {
            $updated = $this->readService->updateProfil($pegawai, $request->validated());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menyimpan profil pegawai. Periksa koneksi database atau hak akses UPDATE.');
        }

        if ($updated->stts_aktif !== 'AKTIF') {
            return redirect()
                ->route('berkas-kepegawaian.index')
                ->with('success', 'Profil disimpan. Status pegawai bukan AKTIF; dikembalikan ke daftar.');
        }

        return back()->with('success', 'Profil pegawai berhasil diperbarui.');
    }

    public function store(StoreBerkasPegawaiRequest $request, string $nik): RedirectResponse
    {
        $this->findAktifPegawaiOrFail($nik);
        $data = $request->validated();

        try {
            $this->service->upload(
                $nik,
                $data['kode_berkas'],
                $data['dokumen'],
                $data['tgl_uploud'],
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil diunggah.');
    }

    public function replace(ReplaceBerkasPegawaiRequest $request, string $nik, string $kode): RedirectResponse
    {
        $this->findAktifPegawaiOrFail($nik);
        $data = $request->validated();

        try {
            $this->service->replace(
                $nik,
                $kode,
                $data['dokumen'],
                $data['tgl_uploud'],
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil diganti.');
    }

    public function destroy(string $nik, string $kode): RedirectResponse
    {
        $this->findAktifPegawaiOrFail($nik);

        try {
            $this->service->delete($nik, $kode);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil dihapus.');
    }

    public function storeSuratPeringatan(StoreRiwayatSuratPeringatanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storeSuratPeringatan($pegawai, $request->validated()),
            'Surat peringatan berhasil ditambahkan.',
            'Gagal menambah surat peringatan. Periksa hak akses database.',
        );
    }

    public function updateSuratPeringatan(UpdateRiwayatSuratPeringatanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updateSuratPeringatan($pegawai, $request->validated()),
            'Surat peringatan berhasil diperbarui.',
            'Gagal memperbarui surat peringatan. Periksa hak akses database.',
        );
    }

    public function destroySuratPeringatan(DestroyRiwayatSuratPeringatanRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroySuratPeringatan(
                $pegawai,
                $data['nama_peringatan'],
                $data['tanggal'],
            ),
            'Surat peringatan berhasil dihapus.',
            'Gagal menghapus surat peringatan. Periksa hak akses database.',
        );
    }

    public function storePenghargaan(StoreRiwayatPenghargaanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storePenghargaan($pegawai, $request->validated()),
            'Penghargaan berhasil ditambahkan.',
            'Gagal menambah penghargaan. Periksa hak akses database.',
        );
    }

    public function updatePenghargaan(UpdateRiwayatPenghargaanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updatePenghargaan($pegawai, $request->validated()),
            'Penghargaan berhasil diperbarui.',
            'Gagal memperbarui penghargaan. Periksa hak akses database.',
        );
    }

    public function destroyPenghargaan(DestroyRiwayatPenghargaanRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroyPenghargaan(
                $pegawai,
                $data['nama_penghargaan'],
                $data['tanggal'],
            ),
            'Penghargaan berhasil dihapus.',
            'Gagal menghapus penghargaan. Periksa hak akses database.',
        );
    }

    public function storePendidikan(StoreRiwayatPendidikanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storePendidikan($pegawai, $request->validated()),
            'Riwayat pendidikan berhasil ditambahkan.',
            'Gagal menambah riwayat pendidikan. Periksa hak akses database.',
        );
    }

    public function updatePendidikan(UpdateRiwayatPendidikanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updatePendidikan($pegawai, $request->validated()),
            'Riwayat pendidikan berhasil diperbarui.',
            'Gagal memperbarui riwayat pendidikan. Periksa hak akses database.',
        );
    }

    public function destroyPendidikan(DestroyRiwayatPendidikanRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroyPendidikan(
                $pegawai,
                $data['pendidikan'],
                $data['sekolah'],
            ),
            'Riwayat pendidikan berhasil dihapus.',
            'Gagal menghapus riwayat pendidikan. Periksa hak akses database.',
        );
    }

    public function storeJabatan(StoreRiwayatJabatanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storeJabatan($pegawai, $request->validated()),
            'Riwayat jabatan berhasil ditambahkan.',
            'Gagal menambah riwayat jabatan. Periksa hak akses database.',
        );
    }

    public function updateJabatan(UpdateRiwayatJabatanRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updateJabatan($pegawai, $request->validated()),
            'Riwayat jabatan berhasil diperbarui.',
            'Gagal memperbarui riwayat jabatan. Periksa hak akses database.',
        );
    }

    public function destroyJabatan(DestroyRiwayatJabatanRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroyJabatan($pegawai, $data['jabatan']),
            'Riwayat jabatan berhasil dihapus.',
            'Gagal menghapus riwayat jabatan. Periksa hak akses database.',
        );
    }

    public function storeSeminar(StoreRiwayatSeminarRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storeSeminar($pegawai, $request->validated()),
            'Seminar berhasil ditambahkan.',
            'Gagal menambah seminar. Periksa hak akses database.',
        );
    }

    public function updateSeminar(UpdateRiwayatSeminarRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updateSeminar($pegawai, $request->validated()),
            'Seminar berhasil diperbarui.',
            'Gagal memperbarui seminar. Periksa hak akses database.',
        );
    }

    public function destroySeminar(DestroyRiwayatSeminarRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroySeminar(
                $pegawai,
                $data['nama_seminar'],
                $data['mulai'],
            ),
            'Seminar berhasil dihapus.',
            'Gagal menghapus seminar. Periksa hak akses database.',
        );
    }

    public function storePenelitian(StoreRiwayatPenelitianRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->storePenelitian($pegawai, $request->validated()),
            'Penelitian berhasil ditambahkan.',
            'Gagal menambah penelitian. Periksa hak akses database.',
        );
    }

    public function updatePenelitian(UpdateRiwayatPenelitianRequest $request, string $nik): RedirectResponse
    {
        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->updatePenelitian($pegawai, $request->validated()),
            'Penelitian berhasil diperbarui.',
            'Gagal memperbarui penelitian. Periksa hak akses database.',
        );
    }

    public function destroyPenelitian(DestroyRiwayatPenelitianRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        return $this->runRiwayatMutation(
            $nik,
            fn (Pegawai $pegawai) => $this->riwayatService->destroyPenelitian(
                $pegawai,
                $data['judul_penelitian'],
                $data['tahun'],
            ),
            'Penelitian berhasil dihapus.',
            'Gagal menghapus penelitian. Periksa hak akses database.',
        );
    }

    /**
     * @param  callable(Pegawai): void  $action
     */
    private function runRiwayatMutation(
        string $nik,
        callable $action,
        string $successMessage,
        string $genericError,
    ): RedirectResponse {
        $pegawai = $this->findAktifPegawaiOrFail($nik);

        try {
            $action($pegawai);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $genericError);
        }

        return back()->with('success', $successMessage);
    }

    private function findAktifPegawaiOrFail(string $nik): Pegawai
    {
        return $this->readService->aktifPegawaiQuery()
            ->where('nik', $nik)
            ->firstOrFail();
    }
}
