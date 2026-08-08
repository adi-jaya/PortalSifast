import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AuditItem = {
    id: number;
    kode_aset: string;
    hasil: string | null;
    kondisi_aktual: string | null;
    aset_ruang_ditemukan_id: number | null;
    catatan: string | null;
    path_foto_bukti: string | null;
    dicek_oleh: string | null;
    nama_barang: string;
    kondisi_sistem: string | null;
    siklus_hidup: string | null;
    nama_ruang: string | null;
    photo_src: string | null;
};

type Props = {
    audit: {
        id: number;
        judul: string | null;
        status: string;
        catatan: string | null;
        pemula: string | null;
        penyetuju: string | null;
        ruang: { id: number; kode_ruang: string; nama_ruang: string } | null;
        ringkasan: {
            total: number;
            belum_dicek: number;
            ditemukan: number;
            tidak_ditemukan: number;
            salah_ruang: number;
        };
        bisa_diedit: boolean;
        bisa_selesai: boolean;
        bisa_disetujui: boolean;
    };
    items: AuditItem[];
    ruangOptions: { id: number; kode_ruang: string; nama_ruang: string }[];
    kondisiOptions: string[];
};

function hasilTone(hasil: string | null): string {
    switch (hasil) {
        case 'ditemukan':
            return 'bg-emerald-500/15 text-emerald-900 ring-emerald-500/25';
        case 'tidak_ditemukan':
            return 'bg-rose-500/15 text-rose-900 ring-rose-500/25';
        case 'salah_ruang':
            return 'bg-amber-500/15 text-amber-900 ring-amber-500/25';
        default:
            return 'bg-muted text-muted-foreground ring-border';
    }
}

export default function AuditAsetShow({ audit, items, ruangOptions, kondisiOptions }: Props) {
    const { flash, errors } = usePage().props as {
        flash?: { success?: string };
        errors?: Record<string, string>;
    };
    const [hideChecked, setHideChecked] = useState(false);
    const [scanKode, setScanKode] = useState('');
    const [expandedId, setExpandedId] = useState<number | null>(null);
    const [paksa, setPaksa] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Aset', href: '/aset' },
        { title: 'Audit Fisik', href: '/aset/audit' },
        { title: audit.judul ?? `Audit #${audit.id}`, href: `/aset/audit/${audit.id}` },
    ];

    const progress =
        audit.ringkasan.total === 0
            ? 0
            : Math.round(
                  ((audit.ringkasan.total - audit.ringkasan.belum_dicek) / audit.ringkasan.total) *
                      100,
              );

    const visibleItems = useMemo(
        () => (hideChecked ? items.filter((i) => !i.hasil) : items),
        [hideChecked, items],
    );

    const scanForm = useForm({
        kode_aset: '',
        hasil: 'ditemukan',
        kondisi_aktual: 'Ada',
    });

    const selesaiForm = useForm({ catatan: audit.catatan ?? '', paksa: false });
    const setujuiForm = useForm({ catatan: '' });

    const submitScan = (e: FormEvent) => {
        e.preventDefault();
        scanForm.setData('kode_aset', scanKode.trim());
        scanForm.transform((data) => ({
            ...data,
            kode_aset: scanKode.trim(),
        }));
        scanForm.post(`/aset/audit/${audit.id}/scan`, {
            preserveScroll: true,
            onSuccess: () => {
                setScanKode('');
                scanForm.reset('kode_aset');
            },
        });
    };

    const saveItem = (
        item: AuditItem,
        payload: {
            hasil: string;
            kondisi_aktual?: string | null;
            aset_ruang_ditemukan_id?: number | null;
            catatan?: string | null;
        },
    ) => {
        router.patch(`/aset/audit/${audit.id}/item/${item.id}`, payload, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={audit.judul ?? `Audit #${audit.id}`} />
            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <Link href="/aset/audit" className="text-sm text-muted-foreground hover:underline">
                            ← Daftar audit
                        </Link>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                            {audit.judul ?? `Audit #${audit.id}`}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {audit.ruang?.nama_ruang ?? '—'} · status{' '}
                            <span className="font-medium">{audit.status}</span>
                            {audit.pemula ? ` · oleh ${audit.pemula}` : ''}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {audit.bisa_selesai && (
                            <Button
                                variant="secondary"
                                disabled={selesaiForm.processing}
                                onClick={() => {
                                    selesaiForm.transform((d) => ({ ...d, paksa }));
                                    selesaiForm.post(`/aset/audit/${audit.id}/selesai`);
                                }}
                            >
                                Tandai selesai
                            </Button>
                        )}
                        {audit.bisa_disetujui && (
                            <Button
                                disabled={setujuiForm.processing}
                                onClick={() => setujuiForm.post(`/aset/audit/${audit.id}/setujui`)}
                            >
                                Setujui & rekonsiliasi
                            </Button>
                        )}
                    </div>
                </div>

                {(flash?.success || errors?.status) && (
                    <div
                        className={cn(
                            'rounded-lg border px-3 py-2 text-sm',
                            errors?.status
                                ? 'border-rose-500/30 bg-rose-500/10'
                                : 'border-emerald-500/30 bg-emerald-500/10',
                        )}
                    >
                        {errors?.status ?? flash?.success}
                    </div>
                )}

                <div className="grid gap-2 sm:grid-cols-5">
                    {[
                        ['Total', audit.ringkasan.total],
                        ['Belum', audit.ringkasan.belum_dicek],
                        ['Ditemukan', audit.ringkasan.ditemukan],
                        ['Tidak ditemukan', audit.ringkasan.tidak_ditemukan],
                        ['Salah ruang', audit.ringkasan.salah_ruang],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg border px-3 py-2">
                            <div className="text-xs text-muted-foreground">{label}</div>
                            <div className="text-lg font-semibold">{value}</div>
                        </div>
                    ))}
                </div>

                <div className="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full bg-teal-600 transition-all"
                        style={{ width: `${progress}%` }}
                    />
                </div>

                {audit.bisa_diedit && (
                    <form
                        onSubmit={submitScan}
                        className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-end"
                    >
                        <div className="flex-1 space-y-1">
                            <Label htmlFor="scan">Scan / ketik kode aset</Label>
                            <Input
                                id="scan"
                                value={scanKode}
                                onChange={(e) => setScanKode(e.target.value)}
                                placeholder="INV-IGD01-2024-0001"
                                autoFocus
                            />
                            <InputError message={scanForm.errors.kode_aset ?? errors?.kode_aset} />
                        </div>
                        <Button type="submit" disabled={scanForm.processing || !scanKode.trim()}>
                            Tandai ditemukan
                        </Button>
                    </form>
                )}

                <div className="flex flex-wrap items-center gap-4">
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={hideChecked}
                            onCheckedChange={(v) => setHideChecked(Boolean(v))}
                        />
                        Sembunyikan yang sudah dicek
                    </label>
                    {audit.bisa_selesai && (
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={paksa} onCheckedChange={(v) => setPaksa(Boolean(v))} />
                            Paksa selesai (izinkan item belum dicek)
                        </label>
                    )}
                </div>

                <div className="flex flex-col gap-2">
                    {visibleItems.length === 0 && (
                        <div className="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground">
                            Tidak ada item untuk ditampilkan.
                        </div>
                    )}
                    {visibleItems.map((item) => {
                        const open = expandedId === item.id;
                        return (
                            <div key={item.id} className="rounded-lg border p-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
                                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded bg-muted">
                                        {item.photo_src ? (
                                            <img
                                                src={item.photo_src}
                                                alt=""
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-full items-center justify-center text-[10px] text-muted-foreground">
                                                No foto
                                            </div>
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={`/aset/${item.kode_aset}`}
                                                className="font-medium hover:underline"
                                            >
                                                {item.kode_aset}
                                            </Link>
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs ring-1 ${hasilTone(item.hasil)}`}
                                            >
                                                {item.hasil ?? 'belum dicek'}
                                            </span>
                                        </div>
                                        <div className="text-sm">{item.nama_barang}</div>
                                        <div className="text-xs text-muted-foreground">
                                            Sistem: {item.kondisi_sistem ?? '—'} ·{' '}
                                            {item.siklus_hidup ?? '—'}
                                            {item.dicek_oleh ? ` · dicek ${item.dicek_oleh}` : ''}
                                        </div>
                                    </div>
                                    {audit.bisa_diedit && (
                                        <div className="flex flex-wrap gap-1">
                                            <Button
                                                size="sm"
                                                variant={item.hasil === 'ditemukan' ? 'default' : 'outline'}
                                                onClick={() =>
                                                    saveItem(item, {
                                                        hasil: 'ditemukan',
                                                        kondisi_aktual: item.kondisi_aktual ?? 'Ada',
                                                    })
                                                }
                                            >
                                                Ada
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant={
                                                    item.hasil === 'tidak_ditemukan'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                onClick={() =>
                                                    saveItem(item, { hasil: 'tidak_ditemukan' })
                                                }
                                            >
                                                Hilang
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    setExpandedId(open ? null : item.id)
                                                }
                                            >
                                                Detail
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                {open && audit.bisa_diedit && (
                                    <ItemDetailForm
                                        item={item}
                                        auditId={audit.id}
                                        ruangOptions={ruangOptions}
                                        kondisiOptions={kondisiOptions}
                                        onSave={saveItem}
                                    />
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}

function ItemDetailForm({
    item,
    auditId,
    ruangOptions,
    kondisiOptions,
    onSave,
}: {
    item: AuditItem;
    auditId: number;
    ruangOptions: Props['ruangOptions'];
    kondisiOptions: string[];
    onSave: (
        item: AuditItem,
        payload: {
            hasil: string;
            kondisi_aktual?: string | null;
            aset_ruang_ditemukan_id?: number | null;
            catatan?: string | null;
        },
    ) => void;
}) {
    const [hasil, setHasil] = useState(item.hasil ?? 'ditemukan');
    const [kondisi, setKondisi] = useState(item.kondisi_aktual ?? 'Ada');
    const [ruangId, setRuangId] = useState(
        item.aset_ruang_ditemukan_id ? String(item.aset_ruang_ditemukan_id) : '',
    );
    const [catatan, setCatatan] = useState(item.catatan ?? '');
    const buktiForm = useForm<{ foto: File | null }>({ foto: null });

    return (
        <div className="mt-3 grid gap-3 border-t pt-3 sm:grid-cols-2">
            <div className="space-y-2">
                <Label>Hasil</Label>
                <Select value={hasil} onValueChange={setHasil}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ditemukan">Ditemukan</SelectItem>
                        <SelectItem value="tidak_ditemukan">Tidak ditemukan</SelectItem>
                        <SelectItem value="salah_ruang">Salah ruang</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-2">
                <Label>Kondisi aktual</Label>
                <Select value={kondisi} onValueChange={setKondisi}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {kondisiOptions.map((k) => (
                            <SelectItem key={k} value={k}>
                                {k}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {hasil === 'salah_ruang' && (
                <div className="space-y-2 sm:col-span-2">
                    <Label>Ruang ditemukan</Label>
                    <Select value={ruangId || undefined} onValueChange={setRuangId}>
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih ruang aktual" />
                        </SelectTrigger>
                        <SelectContent>
                            {ruangOptions.map((r) => (
                                <SelectItem key={r.id} value={String(r.id)}>
                                    {r.nama_ruang}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}
            <div className="space-y-2 sm:col-span-2">
                <Label>Catatan</Label>
                <Input value={catatan} onChange={(e) => setCatatan(e.target.value)} />
            </div>
            <div className="flex flex-wrap items-center gap-2 sm:col-span-2">
                <Button
                    size="sm"
                    onClick={() =>
                        onSave(item, {
                            hasil,
                            kondisi_aktual: kondisi,
                            aset_ruang_ditemukan_id:
                                hasil === 'salah_ruang' && ruangId ? Number(ruangId) : null,
                            catatan: catatan || null,
                        })
                    }
                >
                    Simpan item
                </Button>
                <label className="text-sm">
                    <input
                        type="file"
                        accept="image/*"
                        className="text-xs"
                        onChange={(e) => {
                            const file = e.target.files?.[0] ?? null;
                            if (!file) {
                                return;
                            }
                            buktiForm.setData('foto', file);
                            buktiForm.post(`/aset/audit/${auditId}/item/${item.id}/bukti`, {
                                forceFormData: true,
                                preserveScroll: true,
                            });
                        }}
                    />
                </label>
                {item.path_foto_bukti && (
                    <a
                        href={item.path_foto_bukti}
                        target="_blank"
                        rel="noreferrer"
                        className="text-xs text-sky-700 underline"
                    >
                        Lihat bukti
                    </a>
                )}
            </div>
        </div>
    );
}
