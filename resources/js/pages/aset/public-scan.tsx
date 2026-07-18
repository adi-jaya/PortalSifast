import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeftRight,
    CheckCircle2,
    HandCoins,
    ImageOff,
    Lock,
    MapPin,
    Package,
    ShieldCheck,
    Ticket,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';

type HistoryPeminjaman = {
    id: number;
    nomor: string;
    status: string;
    is_terlambat: boolean;
    tanggal: string | null;
    tanggal_kembali_rencana: string | null;
    peminjam_label?: string | null;
    url?: string;
};

type HistoryMutasi = {
    id: number;
    nomor: string;
    tanggal: string | null;
    ruang_asal: string | null;
    ruang_tujuan: string | null;
    penerima_label?: string | null;
    url?: string;
};

type HistoryTiket = {
    id: number;
    ticket_number: string;
    status: string | null;
    tanggal: string | null;
    title?: string | null;
    url?: string;
};

type Props = {
    aset: {
        id: number;
        kode_aset: string;
        no_simrs: string | null;
        no_seri: string | null;
        nama_barang: string;
        kode_barang: string | null;
        nama_merk: string | null;
        nama_jenis: string | null;
        nama_kategori: string | null;
        nama_ruang: string | null;
        kode_ruang: string | null;
        kelas_aset: string | null;
        wajib_kalibrasi: boolean;
        status_fungsi: string | null;
        tingkat_kerusakan: string | null;
        siklus_hidup: string;
        status_ketersediaan?: string;
        tahun_registrasi: number | null;
        photo_src: string | null;
    };
    authenticated: boolean;
    canManage: boolean;
    manageUrl: string;
    ticketCreateUrl: string;
    riwayat: {
        peminjaman: HistoryPeminjaman[];
        mutasi: HistoryMutasi[];
        tiket: HistoryTiket[];
    };
};

function labelFungsi(status: string | null): { text: string; ok: boolean } {
    if (status === 'tidak_berfungsi') {
        return { text: 'Tidak berfungsi', ok: false };
    }

    return { text: 'Berfungsi', ok: true };
}

function labelKerusakan(tingkat: string | null): string {
    switch (tingkat) {
        case 'rusak_ringan':
            return 'Rusak ringan';
        case 'rusak_berat':
            return 'Rusak berat';
        case 'baik':
            return 'Baik';
        default:
            return tingkat ? tingkat.replaceAll('_', ' ') : 'Belum diisi';
    }
}

export default function AsetPublicScan({
    aset,
    authenticated,
    canManage,
    manageUrl,
    ticketCreateUrl,
    riwayat,
}: Props) {
    const fungsi = labelFungsi(aset.status_fungsi);
    const tersedia = (aset.status_ketersediaan ?? 'tersedia') === 'tersedia';

    return (
        <>
            <Head title={`${aset.kode_aset} · Scan aset`} />

            <div className="min-h-dvh bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-teal-50 via-background to-background text-foreground dark:from-teal-950/40">
                <div className="mx-auto flex min-h-dvh max-w-lg flex-col px-4 py-6 sm:px-6 sm:py-10">
                    <header className="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p className="text-[10px] font-semibold tracking-[0.2em] text-teal-700 uppercase dark:text-teal-400">
                                PortalSifast · Scan QR
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                Informasi aset lapangan
                            </p>
                        </div>
                        <div className="flex flex-col items-end gap-1">
                            <Badge
                                variant="outline"
                                className={cn(
                                    'rounded-md capitalize',
                                    aset.siklus_hidup === 'aktif'
                                        ? 'border-teal-700/30 bg-teal-50 text-teal-800 dark:bg-teal-950/50 dark:text-teal-300'
                                        : 'bg-muted',
                                )}
                            >
                                {aset.siklus_hidup}
                            </Badge>
                            <Badge
                                variant={tersedia ? 'secondary' : 'destructive'}
                                className="rounded-md capitalize"
                            >
                                {aset.status_ketersediaan ?? 'tersedia'}
                            </Badge>
                        </div>
                    </header>

                    <main className="flex-1 space-y-4">
                        <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-sm animate-in fade-in slide-in-from-bottom-2 duration-500">
                            <div className="relative aspect-[16/10] bg-muted sm:aspect-[2/1]">
                                {aset.photo_src ? (
                                    <img
                                        src={aset.photo_src}
                                        alt={aset.nama_barang}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full flex-col items-center justify-center gap-2 text-muted-foreground">
                                        <ImageOff className="h-8 w-8 opacity-50" />
                                        <span className="text-xs">Belum ada foto</span>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2 p-4 sm:p-5">
                                <p className="font-mono text-xs tracking-tight text-teal-800 dark:text-teal-300">
                                    {aset.kode_aset}
                                </p>
                                <h1 className="text-xl leading-snug font-semibold tracking-tight sm:text-2xl">
                                    {aset.nama_barang}
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {[aset.nama_merk, aset.nama_jenis, aset.nama_kategori]
                                        .filter(Boolean)
                                        .join(' · ') || 'Klasifikasi belum diisi'}
                                </p>
                                <div className="flex flex-wrap gap-1.5 pt-1">
                                    {aset.kelas_aset && (
                                        <Badge variant="secondary" className="rounded-md capitalize">
                                            {aset.kelas_aset}
                                        </Badge>
                                    )}
                                    {aset.wajib_kalibrasi && (
                                        <Badge
                                            variant="outline"
                                            className="rounded-md border-amber-500/40 text-amber-800 dark:text-amber-200"
                                        >
                                            <ShieldCheck className="mr-1 h-3 w-3" />
                                            Wajib kalibrasi
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        </section>

                        <section className="grid grid-cols-2 gap-2">
                            <div
                                className={cn(
                                    'rounded-xl border p-3',
                                    fungsi.ok
                                        ? 'border-teal-700/20 bg-teal-50/80 dark:bg-teal-950/30'
                                        : 'border-destructive/30 bg-destructive/10',
                                )}
                            >
                                <p className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                    Fungsi
                                </p>
                                <p className="mt-1 flex items-center gap-1.5 text-sm font-semibold">
                                    {fungsi.ok ? (
                                        <CheckCircle2 className="h-4 w-4 text-teal-700 dark:text-teal-400" />
                                    ) : (
                                        <AlertTriangle className="h-4 w-4 text-destructive" />
                                    )}
                                    {fungsi.text}
                                </p>
                            </div>
                            <div className="rounded-xl border border-border/80 bg-card p-3">
                                <p className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                    Kondisi fisik
                                </p>
                                <p className="mt-1 text-sm font-semibold capitalize">
                                    {labelKerusakan(aset.tingkat_kerusakan)}
                                </p>
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-xl border border-border/80 bg-card">
                            <ul className="divide-y divide-border/60 text-sm">
                                <DetailRow
                                    icon={MapPin}
                                    label="Lokasi"
                                    value={
                                        aset.nama_ruang
                                            ? `${aset.nama_ruang}${aset.kode_ruang ? ` (${aset.kode_ruang})` : ''}`
                                            : 'Belum diisi'
                                    }
                                />
                                <DetailRow
                                    icon={Package}
                                    label="Nomor seri"
                                    value={aset.no_seri ?? 'Belum diisi'}
                                    mono={Boolean(aset.no_seri)}
                                />
                                {aset.no_simrs && (
                                    <DetailRow label="No. SIMRS" value={aset.no_simrs} mono />
                                )}
                                {aset.kode_barang && (
                                    <DetailRow label="Kode barang" value={aset.kode_barang} mono />
                                )}
                                {aset.tahun_registrasi && (
                                    <DetailRow label="Tahun registrasi" value={String(aset.tahun_registrasi)} />
                                )}
                            </ul>
                        </section>

                        <section className="overflow-hidden rounded-xl border border-border/80 bg-card">
                            <div className="border-b border-border/60 px-3 pt-3 pb-2">
                                <h2 className="px-1 text-sm font-semibold">Riwayat aset</h2>
                                {!authenticated && (
                                    <p className="mt-0.5 px-1 text-[10px] text-muted-foreground">
                                        Nama & judul detail muncul setelah login
                                    </p>
                                )}
                            </div>
                            <Tabs defaultValue="peminjaman" className="gap-0">
                                <TabsList className="h-auto w-full rounded-none border-b border-border/60 bg-transparent p-1">
                                    <TabsTrigger
                                        value="peminjaman"
                                        className="flex-1 gap-1 rounded-md px-2 py-2 text-xs data-[state=active]:bg-teal-50 data-[state=active]:text-teal-900 dark:data-[state=active]:bg-teal-950/50 dark:data-[state=active]:text-teal-200"
                                    >
                                        <HandCoins className="size-3.5" />
                                        Pinjam
                                        <CountPill count={riwayat.peminjaman.length} />
                                    </TabsTrigger>
                                    <TabsTrigger
                                        value="mutasi"
                                        className="flex-1 gap-1 rounded-md px-2 py-2 text-xs data-[state=active]:bg-teal-50 data-[state=active]:text-teal-900 dark:data-[state=active]:bg-teal-950/50 dark:data-[state=active]:text-teal-200"
                                    >
                                        <ArrowLeftRight className="size-3.5" />
                                        Mutasi
                                        <CountPill count={riwayat.mutasi.length} />
                                    </TabsTrigger>
                                    <TabsTrigger
                                        value="tiket"
                                        className="flex-1 gap-1 rounded-md px-2 py-2 text-xs data-[state=active]:bg-teal-50 data-[state=active]:text-teal-900 dark:data-[state=active]:bg-teal-950/50 dark:data-[state=active]:text-teal-200"
                                    >
                                        <Ticket className="size-3.5" />
                                        Tiket
                                        <CountPill count={riwayat.tiket.length} />
                                    </TabsTrigger>
                                </TabsList>

                                <TabsContent value="peminjaman" className="mt-0">
                                    <HistoryList empty="Belum ada peminjaman." count={riwayat.peminjaman.length}>
                                        {riwayat.peminjaman.map((item) => (
                                            <HistoryItem
                                                key={item.id}
                                                href={item.url}
                                                mono={item.nomor}
                                                meta={item.tanggal ?? '–'}
                                                badge={
                                                    item.is_terlambat
                                                        ? { text: 'terlambat', tone: 'danger' }
                                                        : { text: item.status, tone: 'muted' }
                                                }
                                                detail={
                                                    authenticated
                                                        ? item.peminjam_label ?? undefined
                                                        : item.tanggal_kembali_rencana
                                                          ? `Rencana kembali ${item.tanggal_kembali_rencana}`
                                                          : undefined
                                                }
                                            />
                                        ))}
                                    </HistoryList>
                                </TabsContent>

                                <TabsContent value="mutasi" className="mt-0">
                                    <HistoryList empty="Belum ada mutasi." count={riwayat.mutasi.length}>
                                        {riwayat.mutasi.map((item) => (
                                            <HistoryItem
                                                key={item.id}
                                                href={item.url}
                                                mono={item.nomor}
                                                meta={item.tanggal ?? '–'}
                                                detail={`${item.ruang_asal ?? '–'} → ${item.ruang_tujuan ?? '–'}${
                                                    authenticated && item.penerima_label
                                                        ? ` · ${item.penerima_label}`
                                                        : ''
                                                }`}
                                            />
                                        ))}
                                    </HistoryList>
                                </TabsContent>

                                <TabsContent value="tiket" className="mt-0">
                                    <HistoryList empty="Belum ada tiket." count={riwayat.tiket.length}>
                                        {riwayat.tiket.map((item) => (
                                            <HistoryItem
                                                key={item.id}
                                                href={item.url}
                                                mono={item.ticket_number}
                                                meta={item.tanggal ?? '–'}
                                                badge={
                                                    item.status
                                                        ? { text: item.status, tone: 'muted' }
                                                        : undefined
                                                }
                                                detail={authenticated ? item.title ?? undefined : undefined}
                                            />
                                        ))}
                                    </HistoryList>
                                </TabsContent>
                            </Tabs>
                        </section>

                        {!authenticated && (
                            <p className="flex items-start gap-2 rounded-xl border border-dashed border-teal-700/25 bg-teal-50/50 px-3 py-2.5 text-[11px] leading-relaxed text-muted-foreground dark:bg-teal-950/20">
                                <Lock className="mt-0.5 h-3.5 w-3.5 shrink-0 text-teal-700 dark:text-teal-400" />
                                Guest melihat ringkasan tanggal/status. Nama peminjam, penerima mutasi, dan
                                judul tiket muncul setelah masuk portal.
                            </p>
                        )}
                    </main>

                    <footer className="mt-6 space-y-2 border-t border-border/60 pt-4">
                        <Button asChild variant="outline" className="h-11 w-full">
                            <Link href={ticketCreateUrl}>Buat tiket untuk aset ini</Link>
                        </Button>
                        {canManage ? (
                            <Button asChild className="h-11 w-full bg-teal-700 hover:bg-teal-800">
                                <Link href={manageUrl}>Buka di portal</Link>
                            </Button>
                        ) : (
                            <Button asChild className="h-11 w-full bg-teal-700 hover:bg-teal-800">
                                <Link href={manageUrl}>Masuk untuk lihat detail lengkap</Link>
                            </Button>
                        )}
                        <p className="text-center text-[11px] text-muted-foreground">
                            RS Aisyiyah Siti Fatimah · Inventaris Portal
                        </p>
                    </footer>
                </div>
            </div>
        </>
    );
}

function CountPill({ count }: { count: number }) {
    return (
        <span
            className={cn(
                'rounded-full px-1.5 py-0 text-[10px] font-semibold tabular-nums',
                count > 0
                    ? 'bg-teal-700/10 text-teal-800 dark:text-teal-300'
                    : 'bg-muted text-muted-foreground',
            )}
        >
            {count}
        </span>
    );
}

function HistoryList({
    empty,
    count,
    children,
}: {
    empty: string;
    count: number;
    children: React.ReactNode;
}) {
    if (count === 0) {
        return <p className="px-4 py-6 text-center text-sm text-muted-foreground">{empty}</p>;
    }

    return <ul className="divide-y divide-border/60">{children}</ul>;
}

function HistoryItem({
    href,
    mono,
    meta,
    detail,
    badge,
}: {
    href?: string;
    mono: string;
    meta: string;
    detail?: string;
    badge?: { text: string; tone: 'danger' | 'muted' };
}) {
    const inner = (
        <>
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-mono text-xs font-medium text-teal-800 dark:text-teal-300">
                        {mono}
                    </span>
                    {badge && (
                        <Badge
                            variant={badge.tone === 'danger' ? 'destructive' : 'secondary'}
                            className="rounded-md capitalize text-[10px]"
                        >
                            {badge.text}
                        </Badge>
                    )}
                </div>
                {detail && (
                    <p className="mt-0.5 truncate text-xs text-muted-foreground">{detail}</p>
                )}
            </div>
            <span className="shrink-0 text-[11px] text-muted-foreground">{meta}</span>
        </>
    );

    if (href) {
        return (
            <li>
                <Link
                    href={href}
                    className="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-teal-50/60 dark:hover:bg-teal-950/20"
                >
                    {inner}
                </Link>
            </li>
        );
    }

    return <li className="flex items-start justify-between gap-3 px-4 py-3">{inner}</li>;
}

function DetailRow({
    icon: Icon,
    label,
    value,
    mono = false,
}: {
    icon?: typeof MapPin;
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <li className="flex items-start justify-between gap-3 px-4 py-3">
            <span className="flex items-center gap-2 text-muted-foreground">
                {Icon && <Icon className="h-3.5 w-3.5 shrink-0 opacity-70" />}
                {label}
            </span>
            <span className={cn('max-w-[60%] text-right font-medium', mono && 'font-mono text-xs')}>
                {value}
            </span>
        </li>
    );
}
