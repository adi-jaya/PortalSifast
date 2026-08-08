import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Circle,
    ClipboardCheck,
    MapPin,
    Package,
    RotateCcw,
    Ticket,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AuditItem = {
    no_inventaris: string;
    kode_barang: string;
    nama_barang: string;
    status_barang: string | null;
    has_photo: boolean;
    photo_src: string | null;
    open_tickets: number;
};

type RuangOption = { id_ruang: string; nama_ruang: string };

type Props = {
    ruang: { id_ruang: string; nama_ruang: string };
    items: AuditItem[];
    statusOptions: string[];
    allRuang: RuangOption[];
};

function storageKey(idRuang: string): string {
    return `inventaris-audit-checked:${idRuang}`;
}

function loadChecked(idRuang: string): Set<string> {
    try {
        const raw = localStorage.getItem(storageKey(idRuang));
        if (!raw) {
            return new Set();
        }
        const parsed = JSON.parse(raw) as string[];
        return new Set(Array.isArray(parsed) ? parsed : []);
    } catch {
        return new Set();
    }
}

function saveChecked(idRuang: string, checked: Set<string>): void {
    localStorage.setItem(storageKey(idRuang), JSON.stringify([...checked]));
}

function statusTone(status: string | null): string {
    switch (status) {
        case 'Ada':
            return 'bg-emerald-500/15 text-emerald-800 ring-emerald-500/25 dark:text-emerald-200';
        case 'Rusak':
            return 'bg-rose-500/15 text-rose-800 ring-rose-500/25 dark:text-rose-200';
        case 'Hilang':
            return 'bg-amber-500/15 text-amber-900 ring-amber-500/25 dark:text-amber-200';
        case 'Perbaikan':
            return 'bg-sky-500/15 text-sky-900 ring-sky-500/25 dark:text-sky-200';
        case 'Dipinjam':
            return 'bg-violet-500/15 text-violet-900 ring-violet-500/25 dark:text-violet-200';
        default:
            return 'bg-muted text-muted-foreground ring-border';
    }
}

export default function InventarisAudit({ ruang, items, statusOptions, allRuang }: Props) {
    const [checked, setChecked] = useState<Set<string>>(() => new Set());
    const [hideChecked, setHideChecked] = useState(false);
    const [ready, setReady] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Inventaris', href: '/inventaris' },
        { title: `Audit ${ruang.nama_ruang}`, href: `/inventaris/audit?id_ruang=${ruang.id_ruang}` },
    ];

    useEffect(() => {
        setChecked(loadChecked(ruang.id_ruang));
        setReady(true);
    }, [ruang.id_ruang]);

    useEffect(() => {
        if (!ready) {
            return;
        }
        saveChecked(ruang.id_ruang, checked);
    }, [checked, ready, ruang.id_ruang]);

    const checkedCount = useMemo(
        () => items.filter((item) => checked.has(item.no_inventaris)).length,
        [checked, items],
    );
    const progress = items.length === 0 ? 0 : Math.round((checkedCount / items.length) * 100);

    const visibleItems = hideChecked
        ? items.filter((item) => !checked.has(item.no_inventaris))
        : items;

    const toggleChecked = (noInventaris: string) => {
        setChecked((prev) => {
            const next = new Set(prev);
            if (next.has(noInventaris)) {
                next.delete(noInventaris);
            } else {
                next.add(noInventaris);
            }
            return next;
        });
    };

    const updateStatus = (noInventaris: string, statusBarang: string) => {
        router.patch(
            `/inventaris/${encodeURIComponent(noInventaris)}/status`,
            { status_barang: statusBarang },
            { preserveScroll: true, preserveState: true },
        );
    };

    const resetChecklist = () => {
        if (!confirm('Reset checklist audit ruang ini?')) {
            return;
        }
        setChecked(new Set());
        localStorage.removeItem(storageKey(ruang.id_ruang));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Audit ${ruang.nama_ruang}`} />

            <div className="flex flex-col gap-5 pb-8">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/inventaris?id_ruang=${encodeURIComponent(ruang.id_ruang)}`}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <p className="text-xs font-semibold tracking-[0.2em] text-teal-700 uppercase dark:text-teal-300">
                                Mode audit ruang
                            </p>
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {ruang.nama_ruang}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Centang aset yang sudah dicek. Progress disimpan di perangkat ini (tanpa tabel baru).
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Select
                            value={ruang.id_ruang}
                            onValueChange={(v) => router.get('/inventaris/audit', { id_ruang: v })}
                        >
                            <SelectTrigger className="w-full sm:w-56">
                                <SelectValue placeholder="Pilih ruang" />
                            </SelectTrigger>
                            <SelectContent>
                                {allRuang.map((r) => (
                                    <SelectItem key={r.id_ruang} value={r.id_ruang}>
                                        {r.nama_ruang}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            variant={hideChecked ? 'default' : 'outline'}
                            onClick={() => setHideChecked((v) => !v)}
                        >
                            {hideChecked ? 'Tampilkan semua' : 'Sembunyikan sudah dicek'}
                        </Button>
                        <Button variant="ghost" onClick={resetChecklist}>
                            <RotateCcw className="mr-2 h-4 w-4" />
                            Reset
                        </Button>
                    </div>
                </div>

                <div className="rounded-2xl border border-teal-900/10 bg-card p-4 dark:border-teal-100/10">
                    <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                        <span className="inline-flex items-center gap-2 font-medium">
                            <ClipboardCheck className="h-4 w-4 text-teal-700 dark:text-teal-300" />
                            {checkedCount} / {items.length} dicek
                        </span>
                        <span className="text-muted-foreground">{progress}%</span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-teal-600 transition-all duration-300"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>

                {visibleItems.length === 0 ? (
                    <div className="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {items.length === 0
                            ? 'Tidak ada inventaris di ruang ini.'
                            : 'Semua aset sudah dicek. Matikan filter untuk melihat daftar lengkap.'}
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {visibleItems.map((item) => {
                            const isChecked = checked.has(item.no_inventaris);

                            return (
                                <article
                                    key={item.no_inventaris}
                                    className={cn(
                                        'flex flex-col gap-3 rounded-2xl border p-3 sm:flex-row sm:items-center',
                                        isChecked
                                            ? 'border-emerald-500/30 bg-emerald-500/5'
                                            : 'border-border bg-card',
                                    )}
                                >
                                    <button
                                        type="button"
                                        onClick={() => toggleChecked(item.no_inventaris)}
                                        className="flex min-w-0 flex-1 items-start gap-3 text-left"
                                    >
                                        <span className="mt-0.5 shrink-0">
                                            {isChecked ? (
                                                <CheckCircle2 className="h-6 w-6 text-emerald-600" />
                                            ) : (
                                                <Circle className="h-6 w-6 text-muted-foreground" />
                                            )}
                                        </span>
                                        <div className="flex min-w-0 flex-1 gap-3">
                                            <div className="hidden size-14 shrink-0 overflow-hidden rounded-xl bg-muted sm:block">
                                                {item.photo_src ? (
                                                    <img
                                                        src={item.photo_src}
                                                        alt=""
                                                        className="h-full w-full object-cover"
                                                        onError={(e) => {
                                                            e.currentTarget.style.display = 'none';
                                                        }}
                                                        onLoad={(e) => {
                                                            const img = e.currentTarget;
                                                            if (img.naturalWidth <= 1 && img.naturalHeight <= 1) {
                                                                img.style.display = 'none';
                                                            }
                                                        }}
                                                    />
                                                ) : (
                                                    <div className="flex h-full items-center justify-center">
                                                        <Package className="h-5 w-5 text-muted-foreground" />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="min-w-0 space-y-1">
                                                <p
                                                    className={cn(
                                                        'font-semibold tracking-tight',
                                                        isChecked && 'line-through opacity-70',
                                                    )}
                                                >
                                                    {item.nama_barang}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {item.no_inventaris}
                                                </p>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    {item.status_barang && (
                                                        <span
                                                            className={cn(
                                                                'rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                                                statusTone(item.status_barang),
                                                            )}
                                                        >
                                                            {item.status_barang}
                                                        </span>
                                                    )}
                                                    {item.open_tickets > 0 && (
                                                        <span className="inline-flex items-center gap-1 text-[11px] text-amber-700 dark:text-amber-300">
                                                            <Ticket className="h-3 w-3" />
                                                            {item.open_tickets} tiket terbuka
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </button>

                                    <div className="flex flex-wrap items-center gap-2 sm:shrink-0">
                                        <Select
                                            value={item.status_barang || undefined}
                                            onValueChange={(v) => updateStatus(item.no_inventaris, v)}
                                        >
                                            <SelectTrigger className="h-10 w-full sm:w-36">
                                                <SelectValue placeholder="Status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map((opt) => (
                                                    <SelectItem key={opt} value={opt}>
                                                        {opt}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={`/inventaris/${item.no_inventaris}`}>Detail</Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link
                                                href={`/tickets/create?asset_no_inventaris=${encodeURIComponent(item.no_inventaris)}`}
                                            >
                                                <Ticket className="mr-1 h-4 w-4" />
                                                Tiket
                                            </Link>
                                        </Button>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}

                <p className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                    <MapPin className="h-3 w-3" />
                    Checklist lokal untuk ruang {ruang.nama_ruang}. Status disimpan ke SIMRS saat diubah.
                </p>
            </div>
        </AppLayout>
    );
}
