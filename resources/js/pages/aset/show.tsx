import { Head, Link, router, useForm } from '@inertiajs/react';
import { Activity, ArrowLeft, ArrowLeftRight, Download, HandCoins, QrCode, Ticket, Trash2 } from 'lucide-react';
import { FormEvent, useEffect, useRef, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { DeviceLinkPicker, type LinkableDeviceOption } from '@/components/monitoring/device-link-picker';
import { MetricBar } from '@/components/monitoring/metric-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatRelativeId } from '@/lib/monitoring';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Barang = {
    id: number;
    kode_barang: string;
    nama_barang: string;
    kelas_aset: string | null;
    wajib_kalibrasi: boolean;
    umur_ekonomis_bulan: number | null;
    tahun_produksi: number | null;
    tahun_mulai_operasi: number | null;
    nilai_residu: number | string | null;
    no_akl_akd: string | null;
    daya_watt: number | null;
    level_teknologi: string | null;
    nama_merk: string | null;
    nama_jenis: string | null;
    nama_kategori: string | null;
    nama_produsen: string | null;
    nama_aspak: string | null;
    nama_non_alkes: string | null;
    kode_non_alkes: string | null;
};

type Props = {
    aset: {
        id: number;
        kode_aset: string;
        no_simrs: string | null;
        no_seri?: string | null;
        kode_ruang_registrasi: string | null;
        tahun_registrasi: number | null;
        asal_barang: string | null;
        tanggal_pengadaan: string | null;
        harga: number | string | null;
        status_sumber: string | null;
        kondisi: string | null;
        status_fungsi?: string | null;
        tingkat_kerusakan?: string | null;
        siklus_hidup: string;
        status_ketersediaan?: string;
        photo_url: string | null;
        foto: { id: number; path: string; utama: boolean }[];
        barang: Barang | null;
        distributor?: { id: number; nama_distributor: string } | null;
        ruang: { id: number; kode_ruang: string; nama_ruang: string } | null;
    };
    tickets: { id: number; ticket_number: string; title: string; status: string | null; created_at: string | null }[];
    peminjamanRiwayat?: {
        id: number;
        nomor: string;
        status: string;
        is_terlambat: boolean;
        tanggal_pinjam: string | null;
    }[];
    mutasiRiwayat?: {
        id: number;
        nomor: string;
        tanggal_mutasi: string | null;
        ruang_asal: string | null;
        ruang_tujuan: string | null;
    }[];
    ruangOptions: { id: number; kode_ruang: string; nama_ruang: string }[];
    penyusutan?: Penyusutan;
    dokumen?: DokumenRow[];
    monitoring?: {
        device_id: number;
        hostname: string | null;
        status: 'online' | 'offline' | string;
        last_seen_at: string | null;
        last_cpu_percent: string | number | null;
        last_ram_percent: string | number | null;
        last_disk_percent: string | number | null;
        agent_version: string | null;
    } | null;
    canLinkMonitoring?: boolean;
    linkableDevices?: LinkableDeviceOption[];
};

type DokumenRow = {
    id: number;
    judul: string | null;
    tipe: string;
    tipe_label: string;
    lingkup: 'unit' | 'barang';
    nama_asli: string;
    ukuran: number;
    created_at: string | null;
    unduh_url: string;
};

type Penyusutan = {
    metode: string;
    dapat_dihitung: boolean;
    nilai_perolehan: number | null;
    nilai_residu: number;
    umur_manfaat_bulan: number | null;
    penyusutan_per_bulan: number | null;
    bulan_berjalan: number;
    akumulasi_penyusutan: number | null;
    nilai_buku: number | null;
    sisa_umur_bulan: number | null;
    persen_sisa: number | null;
    memakai_default_umur?: boolean;
    memakai_default_residu?: boolean;
    pengaturan?: {
        metode: string;
        residu_persen_default: number;
        umur_bulan_medis: number;
        umur_bulan_non_medis: number;
        umur_bulan_default: number;
    };
};

function dash(value: ReactNode): ReactNode {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return value;
}

function formatRp(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }
    const n = Number(value);
    if (Number.isNaN(n)) {
        return '–';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(n);
}

function formatUmur(bulan: number | null | undefined): string {
    if (!bulan) {
        return '–';
    }
    if (bulan % 12 === 0) {
        return `${bulan / 12} Tahun`;
    }

    return `${bulan} bulan`;
}

function levelTeknologiLabel(value: string | null | undefined): string {
    const map: Record<string, string> = {
        low: 'Rendah',
        medium: 'Sedang',
        high: 'Tinggi',
    };

    return value ? (map[value] ?? value) : '–';
}

function kondisiAlatLabel(aset: Props['aset']): string {
    const parts = [aset.status_fungsi, aset.tingkat_kerusakan].filter(Boolean);
    if (parts.length > 0) {
        return parts.join(' · ').replaceAll('_', ' ');
    }

    return aset.kondisi ?? '–';
}

function rasioSisa(harga: number | string | null | undefined, residu: number | string | null | undefined): string {
    const h = Number(harga);
    const r = Number(residu);
    if (!h || Number.isNaN(h) || Number.isNaN(r)) {
        return '–';
    }

    return `${((r / h) * 100).toFixed(0)} %`;
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="min-w-0">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-0.5 text-sm font-medium break-words text-foreground">{children}</p>
        </div>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="overflow-hidden rounded-xl border border-border/80 bg-card">
            <div className="border-b border-border/60 bg-muted/30 px-4 py-3">
                <h2 className="text-sm font-semibold tracking-tight">{title}</h2>
            </div>
            <div className="p-4">{children}</div>
        </section>
    );
}

function formatBytes(bytes: number): string {
    if (!bytes) {
        return '–';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function DokumenList({
    title,
    empty,
    items,
    kodeAset,
}: {
    title: string;
    empty: string;
    items: DokumenRow[];
    kodeAset: string;
}) {
    return (
        <div className="space-y-2">
            <p className="text-xs font-medium text-muted-foreground">{title}</p>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">{empty}</p>
            ) : (
                <ul className="space-y-2">
                    {items.map((d) => (
                        <li
                            key={d.id}
                            className="flex items-start justify-between gap-2 rounded-lg border border-border/60 px-3 py-2"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">{d.judul || d.nama_asli}</p>
                                <p className="text-xs text-muted-foreground">
                                    {d.tipe_label} · {formatBytes(d.ukuran)}
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button variant="ghost" size="icon" asChild title="Unduh">
                                    <a href={d.unduh_url}>
                                        <Download className="h-4 w-4" />
                                    </a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Hapus"
                                    onClick={() => {
                                        if (confirm(`Hapus dokumen "${d.judul || d.nama_asli}"?`)) {
                                            router.delete(`/aset/${kodeAset}/dokumen/${d.id}`);
                                        }
                                    }}
                                >
                                    <Trash2 className="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function formatUmurBulan(bulan: number | null | undefined): string {
    if (bulan === null || bulan === undefined) {
        return '–';
    }
    if (bulan === 0) {
        return 'Habis';
    }
    const tahun = Math.floor(bulan / 12);
    const sisa = bulan % 12;
    const parts: string[] = [];
    if (tahun > 0) {
        parts.push(`${tahun} th`);
    }
    if (sisa > 0) {
        parts.push(`${sisa} bln`);
    }

    return parts.join(' ') || '0 bln';
}

export default function AsetShow({
    aset,
    tickets,
    peminjamanRiwayat = [],
    mutasiRiwayat = [],
    ruangOptions,
    penyusutan,
    dokumen = [],
    monitoring = null,
    canLinkMonitoring = false,
    linkableDevices = [],
}: Props) {
    const tersedia = (aset.status_ketersediaan ?? 'tersedia') === 'tersedia';
    const fileRef = useRef<HTMLInputElement>(null);
    const dokumenFileRef = useRef<HTMLInputElement>(null);
    const fotoForm = useForm<{ foto: File | null }>({ foto: null });
    const dokumenForm = useForm<{
        file: File | null;
        tipe: string;
        lingkup: string;
        judul: string;
    }>({
        file: null,
        tipe: 'pendukung',
        lingkup: 'unit',
        judul: '',
    });
    const deviceLinkForm = useForm<{ monitored_device_id: number | null }>({
        monitored_device_id: monitoring?.device_id ?? null,
    });

    useEffect(() => {
        deviceLinkForm.setData('monitored_device_id', monitoring?.device_id ?? null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [monitoring?.device_id]);
    const verifikasi = useForm({
        aset_ruang_id: aset.ruang?.id ? String(aset.ruang.id) : '',
        tahun_registrasi: String(aset.tahun_registrasi ?? new Date().getFullYear()),
        kondisi: aset.kondisi ?? 'Ada',
        kelas_aset: aset.barang?.kelas_aset ?? '',
        wajib_kalibrasi: Boolean(aset.barang?.wajib_kalibrasi),
        umur_ekonomis_bulan: aset.barang?.umur_ekonomis_bulan
            ? String(aset.barang.umur_ekonomis_bulan)
            : '',
        regenerate_kode: false,
    });

    const namaBarang = aset.barang?.nama_barang ?? 'Aset';
    const sn = aset.no_seri || aset.kode_aset;
    const titleLine = `${namaBarang} - ${sn}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Aset', href: '/aset' },
        { title: aset.kode_aset, href: `/aset/${aset.kode_aset}` },
    ];

    const submitFoto = (e: FormEvent) => {
        e.preventDefault();
        fotoForm.post(`/aset/${aset.kode_aset}/foto`, {
            forceFormData: true,
            onSuccess: () => {
                fotoForm.reset();
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    };

    const submitDokumen = (e: FormEvent) => {
        e.preventDefault();
        dokumenForm.post(`/aset/${aset.kode_aset}/dokumen`, {
            forceFormData: true,
            onSuccess: () => {
                dokumenForm.reset('file', 'judul');
                dokumenForm.setData({
                    file: null,
                    tipe: 'pendukung',
                    lingkup: 'unit',
                    judul: '',
                });
                if (dokumenFileRef.current) {
                    dokumenFileRef.current.value = '';
                }
            },
        });
    };

    const dokumenUnit = dokumen.filter((d) => d.lingkup === 'unit');
    const dokumenBarang = dokumen.filter((d) => d.lingkup === 'barang');

    const submitVerifikasi = (e: FormEvent) => {
        e.preventDefault();
        verifikasi.transform((data) => ({
            ...data,
            aset_ruang_id: Number(data.aset_ruang_id),
            tahun_registrasi: Number(data.tahun_registrasi),
            umur_ekonomis_bulan: data.umur_ekonomis_bulan
                ? Number(data.umur_ekonomis_bulan)
                : null,
            kelas_aset: data.kelas_aset || null,
        }));
        verifikasi.post(`/aset/${aset.kode_aset}/verifikasi`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={titleLine} />

            <div className="mx-auto flex max-w-5xl flex-col gap-5">
                <div className="flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild className="mt-0.5 shrink-0">
                            <Link href="/aset"><ArrowLeft className="h-4 w-4" /></Link>
                        </Button>
                        <div className="min-w-0">
                            <p className="text-[11px] font-medium tracking-[0.16em] text-teal-700 uppercase dark:text-teal-400">
                                Informasi alat
                            </p>
                            <h1 className="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">{titleLine}</h1>
                            <p className="mt-1 font-mono text-xs text-muted-foreground">{aset.kode_aset}</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                <Badge variant="outline" className="capitalize">{aset.siklus_hidup}</Badge>
                                <Badge variant={tersedia ? 'secondary' : 'destructive'} className="capitalize">
                                    {aset.status_ketersediaan ?? 'tersedia'}
                                </Badge>
                                {aset.barang?.kelas_aset ? (
                                    <Badge variant="outline" className="capitalize">{aset.barang.kelas_aset}</Badge>
                                ) : null}
                                {monitoring ? (
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            'capitalize',
                                            monitoring.status === 'online'
                                                ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
                                        )}
                                    >
                                        <Activity className="mr-1 size-3" />
                                        {monitoring.status}
                                    </Badge>
                                ) : null}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2 sm:justify-end">
                        <Button variant="outline" asChild>
                            <a href={`/aset/${aset.kode_aset}/label-print`} target="_blank" rel="noreferrer">
                                <QrCode className="mr-2 h-4 w-4" /> Label QR
                            </a>
                        </Button>
                        {tersedia ? (
                            <Button variant="outline" asChild>
                                <Link href={`/aset-peminjaman/create?aset_id=${aset.id}`}>
                                    <HandCoins className="mr-2 h-4 w-4" /> Pinjamkan
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" disabled>
                                <HandCoins className="mr-2 h-4 w-4" /> Pinjamkan
                            </Button>
                        )}
                        {tersedia ? (
                            <Button variant="outline" asChild>
                                <Link href={`/aset-mutasi-lokasi/create?aset_id=${aset.id}`}>
                                    <ArrowLeftRight className="mr-2 h-4 w-4" /> Mutasi
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" disabled>
                                <ArrowLeftRight className="mr-2 h-4 w-4" /> Mutasi
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={`/tickets/create?asset_id=${aset.id}`}>
                                <Ticket className="mr-2 h-4 w-4" /> Buat tiket
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/aset/${aset.kode_aset}/edit`}>Edit</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Section title="Monitoring perangkat">
                            {monitoring ? (
                                <div className="space-y-4">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold tracking-tight">
                                                {monitoring.hostname || `Perangkat #${monitoring.device_id}`}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                Last seen {formatRelativeId(monitoring.last_seen_at)}
                                                {monitoring.agent_version ? ` · Agent ${monitoring.agent_version}` : ''}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    'capitalize',
                                                    monitoring.status === 'online'
                                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                        : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
                                                )}
                                            >
                                                {monitoring.status}
                                            </Badge>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/monitoring/${monitoring.device_id}`}>
                                                    <Activity className="mr-1.5 size-3.5" />
                                                    Buka monitoring
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="grid gap-2 sm:grid-cols-3">
                                        <div className="rounded-lg border border-border/70 px-3 py-2">
                                            <MetricBar label="CPU" value={monitoring.last_cpu_percent} />
                                        </div>
                                        <div className="rounded-lg border border-border/70 px-3 py-2">
                                            <MetricBar label="RAM" value={monitoring.last_ram_percent} />
                                        </div>
                                        <div className="rounded-lg border border-border/70 px-3 py-2">
                                            <MetricBar label="Disk" value={monitoring.last_disk_percent} />
                                        </div>
                                    </div>
                                    {canLinkMonitoring ? (
                                        <DeviceLinkPicker
                                            linkedDeviceId={monitoring.device_id}
                                            options={linkableDevices}
                                            value={deviceLinkForm.data.monitored_device_id}
                                            onChange={(id) => deviceLinkForm.setData('monitored_device_id', id)}
                                            onSubmit={() =>
                                                deviceLinkForm.patch(`/aset/${aset.kode_aset}/monitoring`, {
                                                    preserveScroll: true,
                                                })
                                            }
                                            onUnlink={() => {
                                                router.patch(
                                                    `/aset/${aset.kode_aset}/monitoring`,
                                                    { monitored_device_id: null },
                                                    { preserveScroll: true },
                                                );
                                            }}
                                            processing={deviceLinkForm.processing}
                                            error={deviceLinkForm.errors.monitored_device_id}
                                            recentlySuccessful={deviceLinkForm.recentlySuccessful}
                                        />
                                    ) : null}
                                </div>
                            ) : canLinkMonitoring ? (
                                <div className="space-y-3">
                                    <p className="text-sm text-muted-foreground">
                                        Belum terhubung ke RS Agent. Pilih perangkat yang sudah register, atau buka daftar
                                        perangkat yang belum terhubung.
                                    </p>
                                    <DeviceLinkPicker
                                        linkedDeviceId={null}
                                        options={linkableDevices}
                                        value={deviceLinkForm.data.monitored_device_id}
                                        onChange={(id) => deviceLinkForm.setData('monitored_device_id', id)}
                                        onSubmit={() =>
                                            deviceLinkForm.patch(`/aset/${aset.kode_aset}/monitoring`, {
                                                preserveScroll: true,
                                            })
                                        }
                                        processing={deviceLinkForm.processing}
                                        error={deviceLinkForm.errors.monitored_device_id}
                                        recentlySuccessful={deviceLinkForm.recentlySuccessful}
                                        defaultEditing
                                    />
                                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                                        <Link href="/monitoring?aset_link=unlinked">
                                            Lihat semua perangkat belum terhubung
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <p className="text-sm text-muted-foreground">
                                        Kategori aset ini belum diizinkan untuk dimonitor. Centang kategorinya di
                                        pengaturan, atau perbaiki master barang jika kategorinya salah.
                                    </p>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/monitoring/pengaturan-kategori">Atur kategori monitor</Link>
                                    </Button>
                                </div>
                            )}
                        </Section>

                        <Section title="Identitas">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Nama">{dash(aset.barang?.nama_barang)}</Field>
                                <Field label="SN">{dash(aset.no_seri)}</Field>
                                <Field label="Merk">{dash(aset.barang?.nama_merk)}</Field>
                                <Field label="Tipe">{dash(aset.barang?.nama_jenis)}</Field>
                                <Field label="Tahun">{dash(aset.barang?.tahun_produksi ?? aset.tahun_registrasi)}</Field>
                                <Field label="Produsen">{dash(aset.barang?.nama_produsen)}</Field>
                                <Field label="Kategori">{dash(aset.barang?.nama_kategori)}</Field>
                                <Field label="Kode barang">{dash(aset.barang?.kode_barang)}</Field>
                                {aset.barang?.nama_non_alkes ? (
                                    <Field label="Katalog non-alkes">
                                        {aset.barang.nama_non_alkes}
                                        {aset.barang.kode_non_alkes ? ` (${aset.barang.kode_non_alkes})` : ''}
                                    </Field>
                                ) : null}
                                {aset.barang?.nama_aspak ? (
                                    <Field label="ASPAK">{aset.barang.nama_aspak}</Field>
                                ) : null}
                            </div>
                        </Section>

                        <Section title="Lokasi">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Lokasi / Ruangan">{dash(aset.ruang?.nama_ruang)}</Field>
                                <Field label="Kode ruang">{dash(aset.ruang?.kode_ruang ?? aset.kode_ruang_registrasi)}</Field>
                                <Field label="No SIMRS">{dash(aset.no_simrs)}</Field>
                                <Field label="Distributor">{dash(aset.distributor?.nama_distributor)}</Field>
                            </div>
                        </Section>

                        <Section title="Pendanaan">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Sumber dana">{dash(aset.asal_barang)}</Field>
                                <Field label="Biaya">{formatRp(aset.harga)}</Field>
                                <Field label="Tanggal pengadaan">{dash(aset.tanggal_pengadaan)}</Field>
                                <Field label="Status sumber SIMRS">{dash(aset.status_sumber)}</Field>
                            </div>
                        </Section>

                        <Section title="Usia / Umur & Penyusutan">
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <Field label="Pembelian">
                                    {dash(aset.tanggal_pengadaan?.slice(0, 4) ?? aset.tahun_registrasi)}
                                </Field>
                                <Field label="Umur manfaat">{formatUmur(aset.barang?.umur_ekonomis_bulan)}</Field>
                                <Field label="Nilai perolehan">{formatRp(aset.harga)}</Field>
                                <Field label="Nilai residu">{formatRp(aset.barang?.nilai_residu)}</Field>
                            </div>

                            {penyusutan?.dapat_dihitung ? (
                                <>
                                    <div className="mt-4 grid gap-4 border-t border-border/60 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <Field label="Penyusutan / bulan">{formatRp(penyusutan.penyusutan_per_bulan)}</Field>
                                        <Field label="Akumulasi penyusutan">{formatRp(penyusutan.akumulasi_penyusutan)}</Field>
                                        <Field label="Nilai buku saat ini">{formatRp(penyusutan.nilai_buku)}</Field>
                                        <Field label="Sisa umur">{formatUmurBulan(penyusutan.sisa_umur_bulan)}</Field>
                                    </div>
                                    <div className="mt-4">
                                        <div className="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                            <span>Rasio nilai buku</span>
                                            <span className="font-medium text-foreground">
                                                {penyusutan.persen_sisa != null ? `${penyusutan.persen_sisa} %` : '–'}
                                            </span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-teal-600 transition-all"
                                                style={{ width: `${Math.min(100, Math.max(0, penyusutan.persen_sisa ?? 0))}%` }}
                                            />
                                        </div>
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            Metode garis lurus · berjalan {formatUmurBulan(penyusutan.bulan_berjalan)} dari{' '}
                                            {formatUmurBulan(penyusutan.umur_manfaat_bulan)}. Estimasi otomatis, bukan nilai buku akuntansi resmi.
                                            {(penyusutan.memakai_default_umur || penyusutan.memakai_default_residu) && (
                                                <>
                                                    {' '}
                                                    (
                                                    {[
                                                        penyusutan.memakai_default_umur ? 'umur dari pengaturan' : null,
                                                        penyusutan.memakai_default_residu ? 'residu dari % default' : null,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                    ).
                                                </>
                                            )}{' '}
                                            <Link href="/aset/pengaturan-penyusutan" className="text-teal-700 underline dark:text-teal-400">
                                                Atur default
                                            </Link>
                                        </p>
                                    </div>
                                </>
                            ) : (
                                <p className="mt-3 text-xs text-muted-foreground">
                                    Penyusutan belum bisa dihitung — lengkapi harga, tanggal pengadaan, dan umur manfaat pada Edit aset.
                                    {' '}Rasio sisa manual: {rasioSisa(aset.harga, aset.barang?.nilai_residu)}
                                </p>
                            )}
                        </Section>

                        <Section title="Data teknis">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Kondisi alat">{kondisiAlatLabel(aset)}</Field>
                                <Field label="Kelompok teknologi">{levelTeknologiLabel(aset.barang?.level_teknologi)}</Field>
                                <Field label="Wajib kalibrasi">{aset.barang?.wajib_kalibrasi ? 'Ya' : 'Tidak'}</Field>
                                <Field label="No. AKL/AKD">{dash(aset.barang?.no_akl_akd)}</Field>
                                <Field label="Daya">{aset.barang?.daya_watt != null ? `${aset.barang.daya_watt} W` : '–'}</Field>
                                <Field label="Tahun mulai operasi">{dash(aset.barang?.tahun_mulai_operasi)}</Field>
                            </div>
                        </Section>
                    </div>

                    <div className="space-y-4">
                        <Section title="Foto & QR">
                            <div className="space-y-3">
                                {aset.photo_url ? (
                                    <img
                                        src={aset.photo_url}
                                        alt={namaBarang}
                                        className="max-h-56 w-full rounded-lg border object-contain"
                                    />
                                ) : (
                                    <p className="text-sm text-muted-foreground">Belum ada foto.</p>
                                )}
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={`/q/${aset.kode_aset}`} target="_blank" rel="noreferrer">
                                        <QrCode className="mr-2 h-4 w-4" /> Buka halaman QR publik
                                    </a>
                                </Button>
                                <form onSubmit={submitFoto} className="space-y-2">
                                    <Input
                                        ref={fileRef}
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => fotoForm.setData('foto', e.target.files?.[0] ?? null)}
                                    />
                                    <p className="text-xs text-muted-foreground">Format gambar, maksimal 5 MB.</p>
                                    <InputError message={fotoForm.errors.foto} />
                                    <Button type="submit" disabled={fotoForm.processing || !fotoForm.data.foto} className="w-full">
                                        Unggah foto
                                    </Button>
                                </form>
                                {aset.foto.map((f) => (
                                    <Button
                                        key={f.id}
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => {
                                            if (confirm('Hapus foto ini?')) {
                                                router.delete(`/aset/${aset.kode_aset}/foto/${f.id}`);
                                            }
                                        }}
                                    >
                                        Hapus foto #{f.id}
                                    </Button>
                                ))}
                            </div>
                        </Section>

                        <Section title="File penerimaan & pendukung">
                            <div className="space-y-4">
                                <DokumenList
                                    title="Unit ini"
                                    empty="Belum ada dokumen khusus unit ini."
                                    items={dokumenUnit}
                                    kodeAset={aset.kode_aset}
                                />
                                <DokumenList
                                    title="Katalog barang (berlaku semua unit)"
                                    empty="Belum ada dokumen katalog (manual / panduan)."
                                    items={dokumenBarang}
                                    kodeAset={aset.kode_aset}
                                />

                                <form onSubmit={submitDokumen} className="space-y-3 border-t border-border/60 pt-4">
                                    <p className="text-xs font-medium text-foreground">Unggah dokumen</p>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="dokumen_file">File</Label>
                                        <Input
                                            id="dokumen_file"
                                            ref={dokumenFileRef}
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                                            onChange={(e) => dokumenForm.setData('file', e.target.files?.[0] ?? null)}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            PDF, gambar, Word, atau Excel — maksimal 10 MB.
                                        </p>
                                        <InputError message={dokumenForm.errors.file} />
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label>Tipe</Label>
                                            <Select
                                                value={dokumenForm.data.tipe}
                                                onValueChange={(v) => dokumenForm.setData('tipe', v)}
                                            >
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="kontrak">Kontrak</SelectItem>
                                                    <SelectItem value="ba_penerimaan">BA. Penerimaan</SelectItem>
                                                    <SelectItem value="akl_akd">Doc AKD/AKL</SelectItem>
                                                    <SelectItem value="ba_uji_fungsi">BA Uji Fungsi</SelectItem>
                                                    <SelectItem value="ba_uji_coba">BA Uji Coba</SelectItem>
                                                    <SelectItem value="manual">Buku panduan / Manual</SelectItem>
                                                    <SelectItem value="pendukung">File pendukung</SelectItem>
                                                    <SelectItem value="lainnya">Lainnya</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={dokumenForm.errors.tipe} />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label>Lingkup</Label>
                                            <Select
                                                value={dokumenForm.data.lingkup}
                                                onValueChange={(v) => dokumenForm.setData('lingkup', v)}
                                                disabled={!aset.barang}
                                            >
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="unit">Unit ini saja</SelectItem>
                                                    <SelectItem value="barang" disabled={!aset.barang}>
                                                        Katalog barang (semua unit)
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {!aset.barang && (
                                                <p className="text-xs text-muted-foreground">
                                                    Hubungkan ke katalog barang dulu (Edit aset) untuk bisa memilih ini.
                                                </p>
                                            )}
                                            <InputError message={dokumenForm.errors.lingkup} />
                                        </div>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Pilih <span className="font-medium text-foreground">Unit ini</span> untuk dokumen khusus 1 unit
                                        (kontrak, BA penerimaan). Pilih <span className="font-medium text-foreground">Katalog barang</span>{' '}
                                        untuk dokumen yang berlaku ke semua unit sejenis (manual/panduan alat).
                                    </p>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="dokumen_judul">Judul (opsional)</Label>
                                        <Input
                                            id="dokumen_judul"
                                            value={dokumenForm.data.judul}
                                            onChange={(e) => dokumenForm.setData('judul', e.target.value)}
                                            placeholder="Default: nama file"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={dokumenForm.processing || !dokumenForm.data.file}
                                    >
                                        Unggah dokumen
                                    </Button>
                                </form>
                            </div>
                        </Section>
                    </div>
                </div>

                {aset.siklus_hidup === 'draf' && (
                    <Card>
                        <CardHeader><CardTitle>Verifikasi inventarisasi ulang</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={submitVerifikasi} className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label>Ruang</Label>
                                    <Select
                                        value={verifikasi.data.aset_ruang_id}
                                        onValueChange={(v) => verifikasi.setData('aset_ruang_id', v)}
                                    >
                                        <SelectTrigger><SelectValue placeholder="Pilih ruang" /></SelectTrigger>
                                        <SelectContent>
                                            {ruangOptions.map((r) => (
                                                <SelectItem key={r.id} value={String(r.id)}>{r.nama_ruang} ({r.kode_ruang})</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Tahun registrasi</Label>
                                    <Input
                                        value={verifikasi.data.tahun_registrasi}
                                        onChange={(e) => verifikasi.setData('tahun_registrasi', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label>Kelas aset</Label>
                                    <Select
                                        value={verifikasi.data.kelas_aset || '__none__'}
                                        onValueChange={(v) => verifikasi.setData('kelas_aset', v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger><SelectValue placeholder="Pilih" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">Belum diisi</SelectItem>
                                            <SelectItem value="medis">Medis</SelectItem>
                                            <SelectItem value="non_medis">Non-medis</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Umur ekonomis (bulan)</Label>
                                    <Input
                                        value={verifikasi.data.umur_ekonomis_bulan}
                                        onChange={(e) => verifikasi.setData('umur_ekonomis_bulan', e.target.value)}
                                    />
                                </div>
                                <div className="flex items-center gap-2 sm:col-span-2">
                                    <Checkbox
                                        checked={verifikasi.data.wajib_kalibrasi}
                                        onCheckedChange={(c) => verifikasi.setData('wajib_kalibrasi', Boolean(c))}
                                        id="wajib_kalibrasi"
                                    />
                                    <Label htmlFor="wajib_kalibrasi">Wajib kalibrasi (alat medis)</Label>
                                </div>
                                <div className="sm:col-span-2">
                                    <Button type="submit" disabled={verifikasi.processing}>
                                        Finalisasi & aktifkan aset
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Section title="Riwayat peminjaman">
                        {peminjamanRiwayat.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Belum ada peminjaman.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {peminjamanRiwayat.map((p) => (
                                    <li key={p.id} className="flex items-center justify-between gap-2">
                                        <Link href={`/aset-peminjaman/${p.id}`} className="font-mono text-primary hover:underline">
                                            {p.nomor}
                                        </Link>
                                        <Badge variant={p.is_terlambat ? 'destructive' : 'secondary'}>
                                            {p.is_terlambat ? 'terlambat' : p.status}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>
                    <Section title="Riwayat mutasi">
                        {mutasiRiwayat.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Belum ada mutasi.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {mutasiRiwayat.map((m) => (
                                    <li key={m.id}>
                                        <Link href={`/aset-mutasi-lokasi/${m.id}`} className="font-mono text-primary hover:underline">
                                            {m.nomor}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {' '}{m.ruang_asal} → {m.ruang_tujuan}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>
                </div>

                <Section title="Tiket terkait">
                    {tickets.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada tiket.</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {tickets.map((t) => (
                                <li key={t.id}>
                                    <Link href={`/tickets/${t.id}`} className="font-mono text-primary hover:underline">
                                        {t.ticket_number}
                                    </Link>
                                    {' — '}{t.title}
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>
        </AppLayout>
    );
}
