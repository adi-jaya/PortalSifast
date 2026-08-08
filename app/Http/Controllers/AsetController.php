<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyAsetRequest;
use App\Http\Requests\Monitoring\LinkAsetMonitoredDeviceRequest;
use App\Http\Requests\StoreAsetRequest;
use App\Http\Requests\VerifikasiAsetRequest;
use App\Models\Aset;
use App\Models\AsetAspakAlat;
use App\Models\AsetBarang;
use App\Models\AsetDistributor;
use App\Models\AsetDokumen;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetProdusen;
use App\Models\AsetRuang;
use App\Models\MonitoredDevice;
use App\Models\Ticket;
use App\Services\Inventaris\BuatAsetBatch;
use App\Services\Inventaris\GeneratorKodeAset;
use App\Services\Inventaris\HitungPenyusutanAset;
use App\Services\Inventaris\PemetaanStatusAset;
use App\Services\Inventaris\PengaturanPenyusutanAset;
use App\Services\InventarisQrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class AsetController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');
        $kelas = (string) $request->query('kelas_aset', '');
        $siklus = (string) $request->query('siklus_hidup', '');
        $ruangId = $request->integer('aset_ruang_id') ?: null;
        $monitoring = (string) $request->query('monitoring', '');

        $asets = Aset::query()
            ->with([
                'barang.merk',
                'barang.jenis',
                'ruang',
                'fotoUtama',
                'monitoredDevice:id,aset_id,hostname,computer_name,status,last_seen_at',
            ])
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($q2) use ($search) {
                    $q2->where('kode_aset', 'like', $search)
                        ->orWhere('no_simrs', 'like', $search)
                        ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', $search));
                });
            })
            ->when($kelas !== '', function ($query) use ($kelas) {
                $query->whereHas('barang', fn ($b) => $b->where('kelas_aset', $kelas));
            })
            ->when($siklus !== '', fn ($query) => $query->where('siklus_hidup', $siklus))
            ->when($ruangId, fn ($query) => $query->where('aset_ruang_id', $ruangId))
            ->when($monitoring === 'dimonitor', fn ($query) => $query->whereHas('monitoredDevice'))
            ->when($monitoring === 'tidak', fn ($query) => $query->whereDoesntHave('monitoredDevice'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $nos = $asets->getCollection()->pluck('id');
        $openTickets = $nos->isEmpty()
            ? collect()
            : Ticket::query()
                ->whereIn('asset_id', $nos)
                ->whereHas('status', fn ($q) => $q->where('is_closed', false))
                ->selectRaw('asset_id, COUNT(*) as aggregate')
                ->groupBy('asset_id')
                ->pluck('aggregate', 'asset_id');

        $asets->setCollection(
            $asets->getCollection()->map(function (Aset $aset) use ($openTickets) {
                $fotoPortal = $aset->fotoUtama?->path;

                return [
                    'id' => $aset->id,
                    'kode_aset' => $aset->kode_aset,
                    'no_simrs' => $aset->no_simrs,
                    'no_seri' => $aset->no_seri,
                    'nama_barang' => $aset->barang?->nama_barang ?? '-',
                    'nama_merk' => $aset->barang?->merk?->nama_merk,
                    'nama_jenis' => $aset->barang?->jenis?->nama_jenis,
                    'kode_barang' => $aset->barang?->kode_barang,
                    'nama_ruang' => $aset->ruang?->nama_ruang,
                    'kelas_aset' => $aset->barang?->kelas_aset,
                    'wajib_kalibrasi' => (bool) $aset->barang?->wajib_kalibrasi,
                    'kondisi' => $aset->kondisi,
                    'status_fungsi' => $aset->status_fungsi,
                    'tingkat_kerusakan' => $aset->tingkat_kerusakan,
                    'siklus_hidup' => $aset->siklus_hidup,
                    'tahun_registrasi' => $aset->tahun_registrasi,
                    'harga' => $aset->harga,
                    'photo_src' => $fotoPortal
                        ? asset('storage/'.$fotoPortal)
                        : ($aset->path_foto_sumber ? route('aset.foto-sumber', $aset) : null),
                    'open_tickets' => (int) ($openTickets[$aset->id] ?? 0),
                    'monitoring' => $aset->monitoredDevice ? [
                        'device_id' => $aset->monitoredDevice->id,
                        'status' => $aset->monitoredDevice->status,
                        'hostname' => $aset->monitoredDevice->hostname
                            ?: $aset->monitoredDevice->computer_name,
                    ] : null,
                ];
            })
        );

        return Inertia::render('aset/index', [
            'asets' => $asets,
            'ruang' => AsetRuang::query()->orderBy('nama_ruang')->get(['id', 'kode_ruang', 'nama_ruang']),
            'filters' => [
                'q' => $q,
                'kelas_aset' => $kelas,
                'siklus_hidup' => $siklus,
                'aset_ruang_id' => $ruangId,
                'monitoring' => $monitoring,
            ],
            'stats' => [
                'total' => Aset::query()->count(),
                'draf' => Aset::query()->where('siklus_hidup', 'draf')->count(),
                'aktif' => Aset::query()->where('siklus_hidup', 'aktif')->count(),
                'medis' => Aset::query()->whereHas('barang', fn ($b) => $b->where('kelas_aset', 'medis'))->count(),
                'non_medis' => Aset::query()->whereHas('barang', fn ($b) => $b->where('kelas_aset', 'non_medis'))->count(),
                'dimonitor' => Aset::query()->whereHas('monitoredDevice')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('aset/create', [
            ...$this->masterFormOptions(),
            'penyusutanDefaults' => app(PengaturanPenyusutanAset::class)->all(),
        ]);
    }

    public function store(StoreAsetRequest $request, BuatAsetBatch $batch): RedirectResponse
    {
        $created = $batch->buat(
            $request->validated(),
            $request->user(),
            $request->file('foto'),
        );

        if (count($created) === 1) {
            return redirect()
                ->route('aset.show', $created[0])
                ->with('success', 'Aset '.$created[0]->kode_aset.' berhasil dibuat.');
        }

        return redirect()
            ->route('aset.created', [
                'ids' => collect($created)->pluck('id')->implode(','),
            ])
            ->with('success', count($created).' aset berhasil dibuat.');
    }

    public function created(Request $request): Response
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))));
        $asets = Aset::query()
            ->with(['barang', 'ruang'])
            ->whereIn('id', $ids)
            ->orderBy('kode_aset')
            ->get()
            ->map(fn (Aset $a) => [
                'id' => $a->id,
                'kode_aset' => $a->kode_aset,
                'no_seri' => $a->no_seri,
                'nama_barang' => $a->barang?->nama_barang,
                'nama_ruang' => $a->ruang?->nama_ruang,
            ]);

        return Inertia::render('aset/created', [
            'asets' => $asets,
        ]);
    }

    public function show(Aset $aset): Response
    {
        $aset->load([
            'barang.merk',
            'barang.jenis',
            'barang.kategori',
            'barang.produsen',
            'barang.aspak',
            'barang.nonAlkes',
            'ruang',
            'distributor',
            'foto',
            'riwayat' => fn ($q) => $q->latest('created_at')->limit(20),
        ]);

        $tickets = Ticket::query()
            ->with('status:id,name,color')
            ->where(function ($q) use ($aset) {
                $q->where('asset_id', $aset->id);
                if ($aset->no_simrs) {
                    $q->orWhere('asset_no_inventaris', $aset->no_simrs);
                }
            })
            ->latest()
            ->limit(20)
            ->get(['id', 'ticket_number', 'title', 'ticket_status_id', 'created_at', 'asset_id', 'asset_no_inventaris'])
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'status' => $ticket->status?->name,
                'created_at' => $ticket->created_at?->toDateString(),
            ]);

        $fotoPortal = $aset->foto->firstWhere('utama', true) ?? $aset->foto->first();

        $pengaturan = app(PengaturanPenyusutanAset::class);
        $resolved = $pengaturan->resolveUntukAset(
            $aset->harga,
            $aset->barang?->umur_ekonomis_bulan,
            $aset->barang?->nilai_residu,
            $aset->barang?->kelas_aset,
        );

        $penyusutan = (new HitungPenyusutanAset)->hitung(
            $aset->harga,
            $aset->tanggal_pengadaan,
            $resolved['umur_bulan'],
            $resolved['nilai_residu'],
        );
        $penyusutan['memakai_default_umur'] = $resolved['memakai_default_umur'];
        $penyusutan['memakai_default_residu'] = $resolved['memakai_default_residu'];
        $penyusutan['pengaturan'] = $pengaturan->all();

        return Inertia::render('aset/show', [
            'aset' => [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'no_simrs' => $aset->no_simrs,
                'no_seri' => $aset->no_seri,
                'kode_ruang_registrasi' => $aset->kode_ruang_registrasi,
                'tahun_registrasi' => $aset->tahun_registrasi,
                'asal_barang' => $aset->asal_barang,
                'tanggal_pengadaan' => $aset->tanggal_pengadaan?->toDateString(),
                'harga' => $aset->harga,
                'status_sumber' => $aset->status_sumber,
                'kondisi' => $aset->kondisi,
                'status_fungsi' => $aset->status_fungsi,
                'tingkat_kerusakan' => $aset->tingkat_kerusakan,
                'siklus_hidup' => $aset->siklus_hidup,
                'status_ketersediaan' => $aset->status_ketersediaan ?? 'tersedia',
                'diverifikasi_pada' => $aset->diverifikasi_pada?->toDateTimeString(),
                'path_foto_sumber' => $aset->path_foto_sumber,
                'photo_url' => $fotoPortal
                    ? asset('storage/'.$fotoPortal->path)
                    : ($aset->path_foto_sumber ? route('aset.foto-sumber', $aset) : null),
                'foto' => $aset->foto->map(fn ($f) => [
                    'id' => $f->id,
                    'path' => asset('storage/'.$f->path),
                    'utama' => $f->utama,
                ]),
                'barang' => $aset->barang ? [
                    'id' => $aset->barang->id,
                    'kode_barang' => $aset->barang->kode_barang,
                    'nama_barang' => $aset->barang->nama_barang,
                    'kelas_aset' => $aset->barang->kelas_aset,
                    'wajib_kalibrasi' => $aset->barang->wajib_kalibrasi,
                    'umur_ekonomis_bulan' => $aset->barang->umur_ekonomis_bulan,
                    'tahun_produksi' => $aset->barang->tahun_produksi,
                    'tahun_mulai_operasi' => $aset->barang->tahun_mulai_operasi,
                    'nilai_residu' => $aset->barang->nilai_residu,
                    'no_akl_akd' => $aset->barang->no_akl_akd,
                    'daya_watt' => $aset->barang->daya_watt,
                    'level_teknologi' => $aset->barang->level_teknologi,
                    'nama_merk' => $aset->barang->merk?->nama_merk,
                    'nama_jenis' => $aset->barang->jenis?->nama_jenis,
                    'nama_kategori' => $aset->barang->kategori?->nama_kategori,
                    'nama_produsen' => $aset->barang->produsen?->nama_produsen,
                    'nama_aspak' => $aset->barang->aspak?->nama_alat,
                    'nama_non_alkes' => $aset->barang->nonAlkes?->nama_alat,
                    'kode_non_alkes' => $aset->barang->nonAlkes?->kode,
                ] : null,
                'distributor' => $aset->distributor ? [
                    'id' => $aset->distributor->id,
                    'nama_distributor' => $aset->distributor->nama_distributor,
                ] : null,
                'ruang' => $aset->ruang ? [
                    'id' => $aset->ruang->id,
                    'kode_ruang' => $aset->ruang->kode_ruang,
                    'nama_ruang' => $aset->ruang->nama_ruang,
                ] : null,
            ],
            'tickets' => $tickets,
            'peminjamanRiwayat' => $aset->peminjaman()
                ->latest('id')
                ->limit(5)
                ->get(['id', 'nomor', 'status', 'tanggal_pinjam', 'tanggal_kembali_rencana'])
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'nomor' => $row->nomor,
                    'status' => $row->status,
                    'is_terlambat' => $row->isTerlambat(),
                    'tanggal_pinjam' => $row->tanggal_pinjam?->toDateString(),
                ]),
            'mutasiRiwayat' => $aset->mutasiLokasi()
                ->with(['ruangAsal:id,nama_ruang', 'ruangTujuan:id,nama_ruang'])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'nomor' => $row->nomor,
                    'tanggal_mutasi' => $row->tanggal_mutasi?->toDateString(),
                    'ruang_asal' => $row->ruangAsal?->nama_ruang,
                    'ruang_tujuan' => $row->ruangTujuan?->nama_ruang,
                ]),
            'ruangOptions' => AsetRuang::query()->orderBy('nama_ruang')->get(['id', 'kode_ruang', 'nama_ruang']),
            'penyusutan' => $penyusutan,
            'dokumen' => $this->dokumenUntukShow($aset),
            'monitoring' => $this->monitoringSummaryFor($aset),
            'canLinkMonitoring' => $aset->isMonitorableForAgent(),
            'linkableDevices' => $aset->isMonitorableForAgent()
                ? $this->linkableDevicesFor($aset)
                : [],
        ]);
    }

    public function updateMonitoring(LinkAsetMonitoredDeviceRequest $request, Aset $aset): RedirectResponse
    {
        $deviceId = $request->validated('monitored_device_id');

        DB::transaction(function () use ($aset, $deviceId): void {
            MonitoredDevice::query()
                ->where('aset_id', $aset->id)
                ->update(['aset_id' => null]);

            if ($deviceId !== null && $deviceId !== '') {
                MonitoredDevice::query()
                    ->whereKey((int) $deviceId)
                    ->update(['aset_id' => $aset->id]);
            }
        });

        $linked = $deviceId !== null && $deviceId !== '';

        return redirect()
            ->route('aset.show', $aset)
            ->with('success', $linked ? 'Perangkat monitoring berhasil dihubungkan.' : 'Tautan perangkat monitoring dilepas.');
    }

    /**
     * @return list<array{id: int, label: string, status: string}>
     */
    private function linkableDevicesFor(Aset $aset): array
    {
        return MonitoredDevice::query()
            ->where(function ($query) use ($aset): void {
                $query
                    ->whereNull('aset_id')
                    ->orWhere('aset_id', $aset->id);
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [MonitoredDevice::STATUS_ONLINE])
            ->orderBy('hostname')
            ->limit(200)
            ->get(['id', 'hostname', 'computer_name', 'ip_address', 'status', 'last_seen_at'])
            ->map(function (MonitoredDevice $device): array {
                $parts = array_filter([
                    $device->hostname ?: $device->computer_name ?: "Device #{$device->id}",
                    $device->ip_address,
                    $device->status,
                ]);

                return [
                    'id' => $device->id,
                    'label' => implode(' · ', $parts),
                    'status' => $device->status,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     device_id: int,
     *     hostname: string|null,
     *     status: string,
     *     last_seen_at: string|null,
     *     last_cpu_percent: string|float|null,
     *     last_ram_percent: string|float|null,
     *     last_disk_percent: string|float|null,
     *     agent_version: string|null
     * }|null
     */
    private function monitoringSummaryFor(Aset $aset): ?array
    {
        $device = $aset->monitoredDevice()->first([
            'id',
            'hostname',
            'computer_name',
            'status',
            'last_seen_at',
            'last_cpu_percent',
            'last_ram_percent',
            'last_disk_percent',
            'agent_version',
        ]);

        if ($device === null) {
            return null;
        }

        return [
            'device_id' => $device->id,
            'hostname' => $device->hostname ?: $device->computer_name,
            'status' => $device->status,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'last_cpu_percent' => $device->last_cpu_percent,
            'last_ram_percent' => $device->last_ram_percent,
            'last_disk_percent' => $device->last_disk_percent,
            'agent_version' => $device->agent_version,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dokumenUntukShow(Aset $aset): array
    {
        $unit = AsetDokumen::query()
            ->where('lingkup', AsetDokumen::LINGKUP_UNIT)
            ->where('aset_id', $aset->id)
            ->latest('id')
            ->get();

        $barang = collect();
        if ($aset->aset_barang_id) {
            $barang = AsetDokumen::query()
                ->where('lingkup', AsetDokumen::LINGKUP_BARANG)
                ->where('aset_barang_id', $aset->aset_barang_id)
                ->latest('id')
                ->get();
        }

        return $unit->concat($barang)->map(fn (AsetDokumen $d) => [
            'id' => $d->id,
            'judul' => $d->judul,
            'tipe' => $d->tipe,
            'tipe_label' => $d->labelTipe(),
            'lingkup' => $d->lingkup,
            'nama_asli' => $d->nama_asli,
            'ukuran' => $d->ukuran,
            'created_at' => $d->created_at?->toDateTimeString(),
            'unduh_url' => route('aset.dokumen.unduh', [$aset, $d]),
        ])->values()->all();
    }

    public function edit(Aset $aset): Response
    {
        $aset->load(['barang', 'ruang']);

        return Inertia::render('aset/edit', [
            'aset' => [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'no_simrs' => $aset->no_simrs,
                'no_seri' => $aset->no_seri,
                'aset_barang_id' => $aset->aset_barang_id,
                'aset_ruang_id' => $aset->aset_ruang_id,
                'aset_distributor_id' => $aset->aset_distributor_id,
                'tahun_registrasi' => $aset->tahun_registrasi,
                'asal_barang' => $aset->asal_barang,
                'tanggal_pengadaan' => $aset->tanggal_pengadaan?->toDateString(),
                'harga' => $aset->harga,
                'kondisi' => $aset->kondisi,
                'status_fungsi' => $aset->status_fungsi ?? 'berfungsi',
                'tingkat_kerusakan' => $aset->tingkat_kerusakan ?? 'baik',
                'siklus_hidup' => $aset->siklus_hidup,
                'kelas_aset' => $aset->barang?->kelas_aset,
                'wajib_kalibrasi' => (bool) $aset->barang?->wajib_kalibrasi,
                'umur_ekonomis_bulan' => $aset->barang?->umur_ekonomis_bulan,
                'aset_kategori_id' => $aset->barang?->aset_kategori_id,
                'aset_jenis_id' => $aset->barang?->aset_jenis_id,
                'aset_merk_id' => $aset->barang?->aset_merk_id,
                'aset_produsen_id' => $aset->barang?->aset_produsen_id,
                'aset_aspak_alat_id' => $aset->barang?->aset_aspak_alat_id,
                'aset_non_alkes_id' => $aset->barang?->aset_non_alkes_id,
                'no_akl_akd' => $aset->barang?->no_akl_akd,
                'daya_watt' => $aset->barang?->daya_watt,
                'level_teknologi' => $aset->barang?->level_teknologi,
                'tahun_produksi' => $aset->barang?->tahun_produksi,
                'tahun_mulai_operasi' => $aset->barang?->tahun_mulai_operasi,
                'nilai_residu' => $aset->barang?->nilai_residu,
                'nama_barang' => $aset->barang?->nama_barang,
            ],
            ...$this->masterFormOptions(),
            'barang' => AsetBarang::query()
                ->orderBy('nama_barang')
                ->limit(500)
                ->get(['id', 'kode_barang', 'nama_barang', 'kelas_aset', 'aset_merk_id', 'aset_jenis_id', 'aset_kategori_id', 'aset_produsen_id']),
            'penyusutanDefaults' => app(PengaturanPenyusutanAset::class)->all(),
        ]);
    }

    public function update(StoreAsetRequest $request, Aset $aset): RedirectResponse
    {
        $v = $request->validated();
        $status = PemetaanStatusAset::dariInputForm(
            $v['status_fungsi'] ?? $aset->status_fungsi,
            $v['tingkat_kerusakan'] ?? $aset->tingkat_kerusakan,
        );

        $aset->update([
            'aset_barang_id' => $v['aset_barang_id'] ?? $aset->aset_barang_id,
            'aset_ruang_id' => $v['aset_ruang_id'],
            'aset_distributor_id' => $v['aset_distributor_id'] ?? null,
            'tahun_registrasi' => $v['tahun_registrasi'],
            'asal_barang' => $v['asal_barang'] ?? null,
            'tanggal_pengadaan' => $v['tanggal_pengadaan'] ?? null,
            'harga' => $v['harga'] ?? null,
            'kondisi' => $status['kondisi'],
            'status_fungsi' => $status['status_fungsi'],
            'tingkat_kerusakan' => $status['tingkat_kerusakan'],
            'no_seri' => filled($v['no_seri'] ?? null) ? $v['no_seri'] : null,
        ]);

        if ($aset->aset_barang_id) {
            $resolved = app(PengaturanPenyusutanAset::class)->resolveUntukAset(
                $v['harga'] ?? $aset->harga,
                isset($v['umur_ekonomis_bulan']) ? (int) $v['umur_ekonomis_bulan'] : null,
                $v['nilai_residu'] ?? null,
                $v['kelas_aset'] ?? null,
            );

            AsetBarang::query()->whereKey($aset->aset_barang_id)->update([
                'kelas_aset' => $v['kelas_aset'] ?? null,
                'wajib_kalibrasi' => array_key_exists('wajib_kalibrasi', $v) ? (bool) $v['wajib_kalibrasi'] : false,
                'umur_ekonomis_bulan' => $resolved['umur_bulan'],
                'aset_kategori_id' => $v['aset_kategori_id'] ?? null,
                'aset_jenis_id' => $v['aset_jenis_id'] ?? null,
                'aset_merk_id' => $v['aset_merk_id'] ?? null,
                'aset_produsen_id' => $v['aset_produsen_id'] ?? null,
                'aset_aspak_alat_id' => $v['aset_aspak_alat_id'] ?? null,
                'aset_non_alkes_id' => $v['aset_non_alkes_id'] ?? null,
                'no_akl_akd' => $v['no_akl_akd'] ?? null,
                'daya_watt' => $v['daya_watt'] ?? null,
                'level_teknologi' => $v['level_teknologi'] ?? null,
                'tahun_produksi' => $v['tahun_produksi'] ?? null,
                'tahun_mulai_operasi' => $v['tahun_mulai_operasi'] ?? null,
                'nilai_residu' => $resolved['nilai_residu'],
            ]);
        }

        return redirect()
            ->route('aset.show', $aset)
            ->with('success', 'Aset diperbarui.');
    }

    public function destroy(Aset $aset): RedirectResponse
    {
        $aset->delete();

        return redirect()
            ->route('aset.index')
            ->with('success', 'Aset dihapus dari portal (SIMRS tidak berubah).');
    }

    public function bulkDestroy(BulkDestroyAsetRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');

        $count = Aset::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('aset.index')
            ->with('success', "{$count} aset dihapus dari portal (SIMRS tidak berubah).");
    }

    public function verifikasi(
        VerifikasiAsetRequest $request,
        Aset $aset,
        GeneratorKodeAset $generator
    ): RedirectResponse {
        $v = $request->validated();
        $ruang = AsetRuang::query()->findOrFail($v['aset_ruang_id']);

        DB::transaction(function () use ($request, $aset, $v, $ruang, $generator) {
            if ($aset->barang) {
                $aset->barang->update([
                    'kelas_aset' => $v['kelas_aset'] ?? $aset->barang->kelas_aset,
                    'wajib_kalibrasi' => array_key_exists('wajib_kalibrasi', $v)
                        ? (bool) $v['wajib_kalibrasi']
                        : $aset->barang->wajib_kalibrasi,
                    'umur_ekonomis_bulan' => $v['umur_ekonomis_bulan'] ?? $aset->barang->umur_ekonomis_bulan,
                ]);
            }

            $kodeAset = $aset->kode_aset;
            $tahun = (int) $v['tahun_registrasi'];
            $needNewCode = $request->boolean('regenerate_kode')
                || $aset->kode_ruang_registrasi !== $ruang->kode_ruang
                || (int) $aset->tahun_registrasi !== $tahun;

            if ($needNewCode && $aset->siklus_hidup === 'draf') {
                $kodeAset = $generator->generate($ruang->kode_ruang, $tahun);
            }

            $aset->update([
                'kode_aset' => $kodeAset,
                'aset_ruang_id' => $ruang->id,
                'kode_ruang_registrasi' => $ruang->kode_ruang,
                'tahun_registrasi' => $tahun,
                'kondisi' => $v['kondisi'] ?? $aset->kondisi,
                'siklus_hidup' => 'aktif',
                'diverifikasi_pada' => now(),
                'diverifikasi_oleh' => $request->user()?->id,
            ]);

            $aset->riwayat()->create([
                'pengguna_id' => $request->user()?->id,
                'jenis_peristiwa' => 'verifikasi',
                'nilai_baru' => [
                    'kode_aset' => $kodeAset,
                    'ruang' => $ruang->kode_ruang,
                    'tahun' => $tahun,
                ],
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Aset diverifikasi: '.$aset->fresh()->kode_aset);
    }

    public function labelPrint(Aset $aset, InventarisQrCodeGenerator $qrCodeGenerator): View
    {
        $aset->load(['barang', 'ruang']);
        $nama = $aset->barang?->nama_barang ?? $aset->kode_aset;
        if (mb_strlen($nama) > 48) {
            $nama = mb_substr($nama, 0, 45).'...';
        }

        return view('inventaris.label-print', [
            'title' => 'Label QR '.$aset->kode_aset,
            'subtitle' => 'Label aset portal',
            'labels' => [[
                'no_inventaris' => $aset->kode_aset,
                'kode_barang' => $aset->barang?->kode_barang ?? '-',
                'nama_barang' => $nama,
                'nama_ruang' => $aset->ruang?->nama_ruang ?: 'Ruang belum diisi',
                'qrSvg' => $qrCodeGenerator->svg(
                    route('aset.public.show', $aset, absolute: true),
                    180
                ),
            ]],
            'backUrl' => route('aset.show', $aset),
            'autoPrint' => request()->boolean('autoprint'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function masterFormOptions(): array
    {
        return [
            'ruang' => AsetRuang::query()->orderBy('nama_ruang')->get(['id', 'kode_ruang', 'nama_ruang']),
            'kategori' => AsetKategori::query()->orderBy('nama_kategori')->get(['id', 'kode_kategori', 'nama_kategori']),
            'jenis' => AsetJenis::query()->orderBy('nama_jenis')->get(['id', 'kode_jenis', 'nama_jenis', 'aset_merk_id']),
            'merk' => AsetMerk::query()->orderBy('nama_merk')->get(['id', 'kode_merk', 'nama_merk']),
            'produsen' => AsetProdusen::query()->orderBy('nama_produsen')->get(['id', 'kode_produsen', 'nama_produsen']),
            'distributor' => AsetDistributor::query()->orderBy('nama_distributor')->get(['id', 'kode_distributor', 'nama_distributor']),
            'aspak' => AsetAspakAlat::query()->orderBy('nama_alat')->limit(300)->get(['id', 'id_alat_aspak', 'nama_alat', 'kode']),
        ];
    }
}
