<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanAuditAsetRequest;
use App\Http\Requests\SelesaiAuditAsetRequest;
use App\Http\Requests\SetujuiAuditAsetRequest;
use App\Http\Requests\StoreAuditAsetBuktiRequest;
use App\Http\Requests\StoreAuditAsetRequest;
use App\Http\Requests\UpdateAuditAsetItemRequest;
use App\Models\Aset;
use App\Models\AsetRuang;
use App\Models\AuditAset;
use App\Models\AuditAsetItem;
use App\Services\Inventaris\RekonsiliasiAuditAset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuditAsetController extends Controller
{
    public function __construct(private RekonsiliasiAuditAset $rekonsiliasi) {}

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $ruangId = $request->integer('aset_ruang_id') ?: null;

        $audits = AuditAset::query()
            ->with(['ruang', 'pemula', 'penyetuju'])
            ->withCount([
                'items',
                'items as belum_dicek_count' => fn ($q) => $q->whereNull('hasil'),
                'items as ditemukan_count' => fn ($q) => $q->where('hasil', 'ditemukan'),
                'items as tidak_ditemukan_count' => fn ($q) => $q->where('hasil', 'tidak_ditemukan'),
                'items as salah_ruang_count' => fn ($q) => $q->where('hasil', 'salah_ruang'),
            ])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($ruangId, fn ($q) => $q->where('aset_ruang_id', $ruangId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $belumPernahDiaudit = Aset::query()
            ->whereDoesntHave('auditItems')
            ->whereIn('siklus_hidup', ['aktif', 'draf'])
            ->count();

        return Inertia::render('aset/audit/index', [
            'audits' => $audits,
            'ruang' => AsetRuang::query()->orderBy('nama_ruang')->get(['id', 'kode_ruang', 'nama_ruang']),
            'filters' => [
                'status' => $status,
                'aset_ruang_id' => $ruangId,
            ],
            'stats' => [
                'berjalan' => AuditAset::query()->where('status', 'berjalan')->count(),
                'selesai' => AuditAset::query()->where('status', 'selesai')->count(),
                'disetujui' => AuditAset::query()->where('status', 'disetujui')->count(),
                'belum_pernah_diaudit' => $belumPernahDiaudit,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('aset/audit/create', [
            'ruang' => AsetRuang::query()
                ->withCount(['aset as aset_count' => fn ($q) => $q->whereIn('siklus_hidup', ['aktif', 'draf', 'hilang'])])
                ->orderBy('nama_ruang')
                ->get(['id', 'kode_ruang', 'nama_ruang']),
        ]);
    }

    public function store(StoreAuditAsetRequest $request): RedirectResponse
    {
        $ruangId = (int) $request->validated('aset_ruang_id');

        $audit = DB::transaction(function () use ($request, $ruangId) {
            $ruang = AsetRuang::query()->findOrFail($ruangId);

            $audit = AuditAset::query()->create([
                'aset_ruang_id' => $ruang->id,
                'judul' => $request->validated('judul')
                    ?: 'Audit '.$ruang->nama_ruang.' '.now()->format('Y-m-d'),
                'status' => 'berjalan',
                'dimulai_oleh' => $request->user()?->id,
                'dimulai_pada' => now(),
                'catatan' => $request->validated('catatan'),
            ]);

            $asets = Aset::query()
                ->where('aset_ruang_id', $ruang->id)
                ->whereIn('siklus_hidup', ['aktif', 'draf', 'hilang'])
                ->get(['id', 'kode_aset']);

            foreach ($asets as $aset) {
                AuditAsetItem::query()->create([
                    'audit_aset_id' => $audit->id,
                    'aset_id' => $aset->id,
                    'kode_aset' => $aset->kode_aset,
                ]);
            }

            $audit->update(['ringkasan' => $audit->hitungRingkasan()]);

            return $audit;
        });

        return redirect()
            ->route('aset.audit.show', $audit)
            ->with('success', 'Sesi audit dibuat. Silakan mulai checklist.');
    }

    public function show(AuditAset $audit): Response
    {
        $audit->load(['ruang', 'pemula', 'penyetuju']);

        $items = $audit->items()
            ->with(['aset.barang', 'aset.ruang', 'aset.fotoUtama', 'ruangDitemukan', 'pemeriksa'])
            ->orderByRaw('hasil IS NULL DESC')
            ->orderBy('kode_aset')
            ->get()
            ->map(fn (AuditAsetItem $item) => $this->mapItem($item));

        $ringkasan = $audit->ringkasan ?? $audit->hitungRingkasan();

        return Inertia::render('aset/audit/show', [
            'audit' => [
                'id' => $audit->id,
                'judul' => $audit->judul,
                'status' => $audit->status,
                'catatan' => $audit->catatan,
                'dimulai_pada' => $audit->dimulai_pada?->toIso8601String(),
                'selesai_pada' => $audit->selesai_pada?->toIso8601String(),
                'disetujui_pada' => $audit->disetujui_pada?->toIso8601String(),
                'pemula' => $audit->pemula?->name,
                'penyetuju' => $audit->penyetuju?->name,
                'ruang' => $audit->ruang ? [
                    'id' => $audit->ruang->id,
                    'kode_ruang' => $audit->ruang->kode_ruang,
                    'nama_ruang' => $audit->ruang->nama_ruang,
                ] : null,
                'ringkasan' => $ringkasan,
                'bisa_diedit' => $audit->status === 'berjalan',
                'bisa_selesai' => $audit->status === 'berjalan',
                'bisa_disetujui' => $audit->status === 'selesai',
            ],
            'items' => $items,
            'ruangOptions' => AsetRuang::query()->orderBy('nama_ruang')->get(['id', 'kode_ruang', 'nama_ruang']),
            'kondisiOptions' => ['Ada', 'Rusak', 'Hilang', 'Perbaikan', 'Dipinjam'],
        ]);
    }

    public function updateItem(
        UpdateAuditAsetItemRequest $request,
        AuditAset $audit,
        AuditAsetItem $item,
    ): RedirectResponse {
        abort_unless($item->audit_aset_id === $audit->id, 404);
        abort_unless($audit->status === 'berjalan', 422, 'Audit tidak lagi dapat diedit.');

        $data = $request->validated();

        $item->update([
            'hasil' => $data['hasil'],
            'kondisi_aktual' => $data['kondisi_aktual'] ?? null,
            'aset_ruang_ditemukan_id' => $data['hasil'] === 'salah_ruang'
                ? ($data['aset_ruang_ditemukan_id'] ?? null)
                : null,
            'catatan' => $data['catatan'] ?? null,
            'dicek_oleh' => $request->user()?->id,
            'dicek_pada' => now(),
        ]);

        $audit->update(['ringkasan' => $audit->hitungRingkasan()]);

        return back()->with('success', 'Item audit diperbarui.');
    }

    public function scan(ScanAuditAsetRequest $request, AuditAset $audit): RedirectResponse
    {
        abort_unless($audit->status === 'berjalan', 422, 'Audit tidak lagi dapat diedit.');

        $kode = trim((string) $request->validated('kode_aset'));
        $item = $audit->items()->where('kode_aset', $kode)->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'kode_aset' => "Kode {$kode} tidak ada di checklist audit ruang ini.",
            ]);
        }

        $hasil = $request->validated('hasil') ?? 'ditemukan';

        $item->update([
            'hasil' => $hasil,
            'kondisi_aktual' => $request->validated('kondisi_aktual') ?? $item->kondisi_aktual ?? 'Ada',
            'aset_ruang_ditemukan_id' => $hasil === 'salah_ruang'
                ? ($request->validated('aset_ruang_ditemukan_id') ?? null)
                : null,
            'catatan' => $request->validated('catatan') ?? $item->catatan,
            'dicek_oleh' => $request->user()?->id,
            'dicek_pada' => now(),
        ]);

        $audit->update(['ringkasan' => $audit->hitungRingkasan()]);

        return back()->with('success', "Scan OK: {$kode} ditandai {$hasil}.");
    }

    public function storeBukti(
        StoreAuditAsetBuktiRequest $request,
        AuditAset $audit,
        AuditAsetItem $item,
    ): RedirectResponse {
        abort_unless($item->audit_aset_id === $audit->id, 404);
        abort_unless($audit->status === 'berjalan', 422, 'Audit tidak lagi dapat diedit.');

        if ($item->path_foto_bukti && Storage::disk('public')->exists($item->path_foto_bukti)) {
            Storage::disk('public')->delete($item->path_foto_bukti);
        }

        $path = $request->file('foto')->store('audit-aset', 'public');

        $item->update([
            'path_foto_bukti' => $path,
            'dicek_oleh' => $item->dicek_oleh ?? $request->user()?->id,
            'dicek_pada' => $item->dicek_pada ?? now(),
        ]);

        return back()->with('success', 'Foto bukti disimpan.');
    }

    public function selesai(SelesaiAuditAsetRequest $request, AuditAset $audit): RedirectResponse
    {
        abort_unless($audit->status === 'berjalan', 422, 'Hanya audit berjalan yang dapat diselesaikan.');

        $paksa = (bool) ($request->validated('paksa') ?? false);
        $belum = $audit->items()->whereNull('hasil')->count();

        if ($belum > 0 && ! $paksa) {
            throw ValidationException::withMessages([
                'status' => "Masih ada {$belum} item belum dicek. Centang paksa untuk menyelesaikan.",
            ]);
        }

        $audit->update([
            'status' => 'selesai',
            'selesai_pada' => now(),
            'catatan' => $request->validated('catatan') ?? $audit->catatan,
            'ringkasan' => $audit->hitungRingkasan(),
        ]);

        return redirect()
            ->route('aset.audit.show', $audit)
            ->with('success', 'Audit ditandai selesai. Siap untuk approval supervisor.');
    }

    public function setujui(SetujuiAuditAsetRequest $request, AuditAset $audit): RedirectResponse
    {
        $this->rekonsiliasi->setujui(
            $audit,
            $request->user(),
            $request->validated('catatan'),
        );

        return redirect()
            ->route('aset.audit.show', $audit)
            ->with('success', 'Audit disetujui. Koreksi kondisi/lokasi diterapkan ke portal (bukan SIMRS).');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(AuditAsetItem $item): array
    {
        $aset = $item->aset;
        $fotoPortal = $aset?->fotoUtama?->path;

        return [
            'id' => $item->id,
            'aset_id' => $item->aset_id,
            'kode_aset' => $item->kode_aset,
            'hasil' => $item->hasil,
            'kondisi_aktual' => $item->kondisi_aktual,
            'aset_ruang_ditemukan_id' => $item->aset_ruang_ditemukan_id,
            'catatan' => $item->catatan,
            'path_foto_bukti' => $item->path_foto_bukti
                ? asset('storage/'.$item->path_foto_bukti)
                : null,
            'dicek_oleh' => $item->pemeriksa?->name,
            'dicek_pada' => $item->dicek_pada?->toIso8601String(),
            'nama_barang' => $aset?->barang?->nama_barang ?? '-',
            'kondisi_sistem' => $aset?->kondisi,
            'siklus_hidup' => $aset?->siklus_hidup,
            'nama_ruang' => $aset?->ruang?->nama_ruang,
            'photo_src' => $fotoPortal
                ? asset('storage/'.$fotoPortal)
                : ($aset?->path_foto_sumber && $aset ? route('aset.foto-sumber', $aset) : null),
            'ruang_ditemukan' => $item->ruangDitemukan ? [
                'id' => $item->ruangDitemukan->id,
                'nama_ruang' => $item->ruangDitemukan->nama_ruang,
            ] : null,
        ];
    }
}
