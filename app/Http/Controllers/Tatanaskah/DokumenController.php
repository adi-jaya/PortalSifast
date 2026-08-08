<?php

namespace App\Http\Controllers\Tatanaskah;

use App\Enums\DokumenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tatanaskah\StoreDokumenRequest;
use App\Http\Requests\Tatanaskah\TransitionDokumenRequest;
use App\Models\AuditDokumen;
use App\Models\Dokumen;
use App\Models\KodeSifatNaskah;
use App\Models\KodeUnitKlasifikasi;
use App\Models\KonfigurasiJenisDokumen;
use App\Models\MetaRegulasi;
use App\Services\Tatanaskah\DocumentSigningService;
use App\Services\Tatanaskah\DocumentStorageService;
use App\Services\Tatanaskah\DokumenWorkflowService;
use App\Services\Tatanaskah\StirlingPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumenController extends Controller
{
    public function __construct(
        private DocumentStorageService $storageService,
        private DokumenWorkflowService $workflowService,
        private StirlingPdfService $stirlingService,
        private DocumentSigningService $signingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Dokumen::class);

        $q = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();
        $kodeJenis = $request->string('kode_jenis')->toString();
        $menungguSaya = $request->boolean('menunggu_saya');
        $userNik = $request->user()?->simrs_nik;

        $query = Dokumen::query()
            ->with(['jenis:kode,nama', 'unitKlasifikasi:id,kode,nama', 'pembuat:id,name', 'versiSaatIni'])
            ->orderByDesc('updated_at');

        if ($menungguSaya && filled($userNik)) {
            $query->where('status', DokumenStatus::MenungguTte)
                ->where('penandatangan_nik', $userNik);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('judul', 'like', "%{$q}%")
                    ->orWhere('nomor_dokumen', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($kodeJenis !== '') {
            $query->where('kode_jenis', $kodeJenis);
        }

        return Inertia::render('tatanaskah/dokumen/index', [
            'dokumen' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'kode_jenis' => $kodeJenis,
                'menunggu_saya' => $menungguSaya,
            ],
            'menungguPersetujuanCount' => filled($userNik)
                ? Dokumen::query()
                    ->where('status', DokumenStatus::MenungguTte)
                    ->where('penandatangan_nik', $userNik)
                    ->count()
                : 0,
            'jenisOptions' => KonfigurasiJenisDokumen::query()
                ->where('is_aktif', true)
                ->orderBy('kode')
                ->get(['kode', 'nama']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Dokumen::class);

        return Inertia::render('tatanaskah/dokumen/create', [
            'jenisOptions' => KonfigurasiJenisDokumen::query()
                ->where('is_aktif', true)
                ->orderBy('kode')
                ->get(['kode', 'nama', 'varian_kop']),
            'unitOptions' => KodeUnitKlasifikasi::query()
                ->where('is_aktif', true)
                ->orderBy('kode')
                ->get(['id', 'kode', 'nama', 'dep_id']),
            'sifatOptions' => KodeSifatNaskah::query()
                ->where('is_aktif', true)
                ->orderBy('kode')
                ->get(['kode', 'nama']),
        ]);
    }

    public function store(StoreDokumenRequest $request): RedirectResponse
    {
        $this->authorize('create', Dokumen::class);

        $validated = $request->validated();
        $jenis = KonfigurasiJenisDokumen::query()->findOrFail($validated['kode_jenis']);

        $dokumen = Dokumen::query()->create([
            'judul' => $validated['judul'],
            'kategori' => $jenis->kategori,
            'kode_jenis' => $jenis->kode,
            'dep_id' => $validated['dep_id'] ?? $request->user()->dep_id,
            'kode_unit_klasifikasi_id' => $validated['kode_unit_klasifikasi_id'],
            'kode_sifat' => $validated['kode_sifat'],
            'dibuat_oleh' => $request->user()->id,
            'penandatangan_nik' => $validated['penandatangan_nik'],
            'penandatangan_nama' => $validated['penandatangan_nama'],
            'penandatangan_jabatan' => $validated['penandatangan_jabatan'] ?? null,
            'status' => DokumenStatus::Draft,
            'tanggal_review' => $validated['tanggal_review'] ?? null,
        ]);

        MetaRegulasi::query()->create([
            'dokumen_id' => $dokumen->id,
            'menimbang' => $validated['menimbang'] ?? null,
            'mengingat' => $validated['mengingat'] ?? null,
            'diktum' => $validated['diktum'] ?? null,
            'nomor_revisi' => $validated['nomor_revisi'] ?? ($jenis->kode === 'SPO' ? '00' : null),
        ]);

        $versi = $this->storageService->storePdfOnDokumen($request->file('file'), $dokumen, $request->user());

        $absolute = Storage::disk('local')->path($versi->file_asli);
        if (is_file($absolute)) {
            $info = $this->stirlingService->getPdfInfo($absolute);
            $versi->update([
                'jumlah_halaman' => $info['pageCount'],
                'ukuran_kb' => $info['sizeKb'],
            ]);
        }

        AuditDokumen::query()->create([
            'dokumen_id' => $dokumen->id,
            'user_id' => $request->user()->id,
            'aksi' => 'created',
            'status_baru' => DokumenStatus::Draft->value,
            'catatan' => 'Dokumen draft dibuat dengan upload PDF.',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 300, ''),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('tatanaskah.dokumen.show', $dokumen)
            ->with('success', 'Draft dokumen berhasil dibuat.');
    }

    public function show(Dokumen $dokumen): Response
    {
        $this->authorize('view', $dokumen);

        $dokumen->load([
            'jenis:kode,nama,varian_kop',
            'unitKlasifikasi:id,kode,nama',
            'sifat:kode,nama',
            'pembuat:id,name',
            'penyetuju:id,name',
            'versiSaatIni',
            'metaRegulasi',
            'audit' => fn ($q) => $q->with('user:id,name')->limit(30),
        ]);

        $versi = $dokumen->versiSaatIni;
        $pdfPath = $versi?->filePathForViewer();

        return Inertia::render('tatanaskah/dokumen/show', [
            'dokumen' => $dokumen,
            'pdfUrl' => $pdfPath ? route('tatanaskah.dokumen.file', $dokumen) : null,
            'statusOptions' => $this->statusOptions(),
            'nextStatuses' => collect($dokumen->status->allowedTransitions())
                ->map(fn (DokumenStatus $s) => ['value' => $s->value, 'label' => $s->label()])
                ->values(),
            'can' => [
                'update' => request()->user()?->can('update', $dokumen) ?? false,
                'transition' => request()->user()?->can('transition', $dokumen) ?? false,
                'delete' => request()->user()?->can('delete', $dokumen) ?? false,
            ],
            'isPenandatangan' => request()->user()?->isPenandatanganFor($dokumen) ?? false,
            'signingCapabilities' => $this->signingService->capabilities(),
            'certSigningEnabled' => $this->signingService->isCertSigningEnabled(),
            'stirlingHealthy' => $this->stirlingService->isHealthy(),
        ]);
    }

    public function transition(TransitionDokumenRequest $request, Dokumen $dokumen): RedirectResponse
    {
        $this->authorize('transition', $dokumen);

        $target = DokumenStatus::from($request->validated('status'));

        if (! $dokumen->status->canTransitionTo($target)) {
            return back()->with('error', 'Transisi status tidak diizinkan.');
        }

        try {
            $this->workflowService->transition(
                $dokumen,
                $target,
                $request->user(),
                $request->validated('catatan'),
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status dokumen diperbarui menjadi '.$target->label().'.');
    }

    public function file(Dokumen $dokumen): BinaryFileResponse
    {
        $this->authorize('view', $dokumen);

        $path = $dokumen->versiSaatIni?->filePathForViewer();

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $absolute = Storage::disk('local')->path($path);

        return response()->file($absolute, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function destroy(Dokumen $dokumen): RedirectResponse
    {
        $this->authorize('delete', $dokumen);

        $dokumen->delete();

        return redirect()
            ->route('tatanaskah.dokumen.index')
            ->with('success', 'Draft dokumen dihapus.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(DokumenStatus::cases())
            ->map(fn (DokumenStatus $s) => ['value' => $s->value, 'label' => $s->label()])
            ->values()
            ->all();
    }
}
