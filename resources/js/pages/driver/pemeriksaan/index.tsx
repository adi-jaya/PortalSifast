import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ChecklistItem = {
    id: number;
    nama: string;
    kategori: string | null;
    urutan: number;
};

type CellMeta = {
    driver_checklist_item_id: number;
    berlaku: boolean;
};

type KendaraanCol = {
    id: number;
    nama: string;
    no_polisi: string | null;
    merk: string | null;
    model: string | null;
    foto_url?: string | null;
    jumlah_hari_ini: number;
    pemeriksaan_ke: number;
    cells: CellMeta[];
};

type CellValue = {
    hasil: 'layak' | 'tidak_layak' | '';
    temuan: string;
    rekomendasi: string;
    keterangan: string;
};

type Props = {
    items: ChecklistItem[];
    kendaraan: KendaraanCol[];
    tanggal: string;
    waktu: string;
    petugas: string | null;
    maxPerDay: number;
};

type BatchItem = {
    driver_checklist_item_id: number;
    hasil: 'layak' | 'tidak_layak';
    temuan: string;
    rekomendasi: string;
    keterangan: string;
};

type BatchRow = {
    driver_kendaraan_id: number;
    catatan: string;
    items: BatchItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Pemeriksaan', href: '/driver/pemeriksaan' },
];

function cellKey(kendaraanId: number, itemId: number): string {
    return `${kendaraanId}:${itemId}`;
}

export default function DriverPemeriksaanIndex({
    items,
    kendaraan,
    tanggal,
    waktu,
    petugas,
    maxPerDay,
}: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string };

    const [values, setValues] = useState<Record<string, CellValue>>({});
    const [catatan, setCatatan] = useState('');
    const [clientError, setClientError] = useState<string | null>(null);

    const { setData, post, processing, errors, clearErrors } = useForm<{
        pemeriksaan: BatchRow[];
    }>({
        pemeriksaan: [],
    });

    const cellMeta = useMemo(() => {
        const map = new Map<string, boolean>();
        for (const k of kendaraan) {
            for (const cell of k.cells) {
                map.set(cellKey(k.id, cell.driver_checklist_item_id), cell.berlaku);
            }
        }

        return map;
    }, [kendaraan]);

    const fillableKeys = useMemo(() => {
        const keys: string[] = [];
        for (const k of kendaraan) {
            for (const cell of k.cells) {
                if (cell.berlaku) {
                    keys.push(cellKey(k.id, cell.driver_checklist_item_id));
                }
            }
        }

        return keys;
    }, [kendaraan]);

    const stats = useMemo(() => {
        let layak = 0;
        let tidakLayak = 0;
        let terisi = 0;

        for (const key of fillableKeys) {
            const hasil = values[key]?.hasil;
            if (hasil === 'layak') {
                layak += 1;
                terisi += 1;
            } else if (hasil === 'tidak_layak') {
                tidakLayak += 1;
                terisi += 1;
            }
        }

        return {
            pemeriksaan: terisi,
            layak,
            tidakLayak,
            belum: fillableKeys.length - terisi,
        };
    }, [fillableKeys, values]);

    const temuanPending = useMemo(() => {
        const rows: Array<{
            key: string;
            kendaraanNama: string;
            itemNama: string;
            value: CellValue;
        }> = [];

        for (const k of kendaraan) {
            for (const item of items) {
                const key = cellKey(k.id, item.id);
                if (!cellMeta.get(key)) {
                    continue;
                }
                const value = values[key];
                if (value?.hasil !== 'tidak_layak') {
                    continue;
                }
                rows.push({
                    key,
                    kendaraanNama: k.nama,
                    itemNama: item.nama,
                    value,
                });
            }
        }

        return rows;
    }, [kendaraan, items, cellMeta, values]);

    const setHasil = (
        kendaraanId: number,
        itemId: number,
        hasil: 'layak' | 'tidak_layak',
        checked: boolean,
    ) => {
        const key = cellKey(kendaraanId, itemId);
        setClientError(null);
        setValues((prev) => {
            const current = prev[key] ?? { hasil: '', temuan: '', rekomendasi: '', keterangan: '' };

            if (!checked) {
                return {
                    ...prev,
                    [key]: {
                        ...current,
                        hasil: '',
                        temuan: '',
                        rekomendasi: '',
                    },
                };
            }

            return {
                ...prev,
                [key]: {
                    ...current,
                    hasil,
                    temuan: hasil === 'layak' ? '' : current.temuan,
                    rekomendasi: hasil === 'layak' ? '' : current.rekomendasi,
                },
            };
        });
    };

    const tandaiSemuaLayak = () => {
        setClientError(null);
        setValues((prev) => {
            const next = { ...prev };
            for (const key of fillableKeys) {
                next[key] = {
                    hasil: 'layak',
                    temuan: '',
                    rekomendasi: '',
                    keterangan: next[key]?.keterangan ?? '',
                };
            }

            return next;
        });
    };

    const setTemuanField = (key: string, field: 'temuan' | 'rekomendasi' | 'keterangan', value: string) => {
        setValues((prev) => {
            const current = prev[key] ?? { hasil: 'tidak_layak', temuan: '', rekomendasi: '', keterangan: '' };

            return {
                ...prev,
                [key]: {
                    ...current,
                    [field]: value,
                },
            };
        });
    };

    const buildPayload = (): BatchRow[] | null => {
        if (kendaraan.length === 0) {
            setClientError('Tidak ada kendaraan yang bisa diperiksa hari ini (batas tercapai).');

            return null;
        }

        if (stats.belum > 0) {
            setClientError(`Masih ada ${stats.belum} sel belum diisi. Lengkapi semua item terlebih dahulu.`);

            return null;
        }

        for (const row of temuanPending) {
            if (!row.value.temuan.trim() || !row.value.rekomendasi.trim()) {
                setClientError(
                    `Temuan & rekomendasi wajib untuk: ${row.kendaraanNama} — ${row.itemNama}`,
                );

                return null;
            }
        }

        return kendaraan.map((k) => ({
            driver_kendaraan_id: k.id,
            catatan,
            items: k.cells
                .filter((cell) => cell.berlaku)
                .map((cell) => {
                    const key = cellKey(k.id, cell.driver_checklist_item_id);
                    const value = values[key];

                    return {
                        driver_checklist_item_id: cell.driver_checklist_item_id,
                        hasil: value.hasil as 'layak' | 'tidak_layak',
                        temuan: value.temuan,
                        rekomendasi: value.rekomendasi,
                        keterangan: value.keterangan,
                    };
                }),
        }));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        clearErrors();
        const payload = buildPayload();
        if (!payload) {
            return;
        }

        setClientError(null);
        setData('pemeriksaan', payload);
        post('/driver/pemeriksaan/batch', {
            preserveScroll: true,
            transform: () => ({ pemeriksaan: payload }),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Checklist Pemeriksaan" />
            <form className="mx-auto flex w-full max-w-[1400px] flex-col gap-4 p-4" onSubmit={submit}>
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Checklist Pemeriksaan</h1>
                        <p className="text-sm text-muted-foreground">
                            {tanggal} · {waktu}
                            {petugas ? ` · Petugas: ${petugas}` : ''}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Maksimal {maxPerDay}× per kendaraan / hari. Centang Layak atau Tidak Layak per item.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={kendaraan.length === 0}
                            onClick={tandaiSemuaLayak}
                        >
                            Centang semua Layak
                        </Button>
                        <Button type="submit" disabled={processing || kendaraan.length === 0}>
                            Simpan Semua
                        </Button>
                    </div>
                </div>

                {flash.success && (
                    <div className="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                        {flash.success}
                    </div>
                )}

                {kendaraan.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Semua kendaraan aktif sudah mencapai batas pemeriksaan hari ini.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full min-w-[900px] border-collapse text-sm">
                            <thead>
                                <tr className="bg-muted/60">
                                    <th
                                        rowSpan={2}
                                        className="sticky left-0 z-20 border bg-muted px-2 py-2 text-center font-semibold"
                                    >
                                        NO
                                    </th>
                                    <th
                                        rowSpan={2}
                                        className="sticky left-10 z-20 min-w-[220px] border bg-muted px-3 py-2 text-left font-semibold"
                                    >
                                        JENIS KEGIATAN
                                    </th>
                                    {kendaraan.map((k) => (
                                        <th
                                            key={k.id}
                                            colSpan={2}
                                            className="border px-2 py-2 text-center font-semibold uppercase"
                                        >
                                            {k.foto_url ? (
                                                <img
                                                    src={k.foto_url}
                                                    alt={k.nama}
                                                    className="mx-auto mb-1 h-14 w-20 rounded object-cover"
                                                />
                                            ) : null}
                                            <div>{k.nama}</div>
                                            <div className="text-[11px] font-normal text-muted-foreground">
                                                ke-{k.pemeriksaan_ke}
                                                {k.no_polisi ? ` · ${k.no_polisi}` : ''}
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                                <tr className="bg-muted/40">
                                    {kendaraan.map((k) => (
                                        <th
                                            key={`${k.id}-sub`}
                                            colSpan={2}
                                            className="border p-0"
                                        >
                                            <div className="grid grid-cols-2">
                                                <div className="border-r px-2 py-1 text-center text-xs font-semibold">
                                                    LAYAK
                                                </div>
                                                <div className="px-2 py-1 text-center text-xs font-semibold">
                                                    TIDAK LAYAK
                                                </div>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item, index) => (
                                    <tr key={item.id} className="odd:bg-background even:bg-muted/20">
                                        <td className="sticky left-0 z-10 border bg-inherit px-2 py-2 text-center">
                                            {index + 1}
                                        </td>
                                        <td className="sticky left-10 z-10 border bg-inherit px-3 py-2 font-medium">
                                            {item.nama}
                                        </td>
                                        {kendaraan.map((k) => {
                                            const key = cellKey(k.id, item.id);
                                            const berlaku = cellMeta.get(key) ?? true;
                                            const current = values[key];

                                            if (!berlaku) {
                                                return (
                                                    <td
                                                        key={key}
                                                        colSpan={2}
                                                        className="border bg-zinc-900 px-2 py-3 text-center text-[11px] font-semibold tracking-wide text-white"
                                                    >
                                                        TIDAK TERSEDIA
                                                    </td>
                                                );
                                            }

                                            return (
                                                <td key={key} colSpan={2} className="border p-0">
                                                    <div className="grid h-full min-h-12 grid-cols-2">
                                                        <button
                                                            type="button"
                                                            className={cn(
                                                                'flex items-center justify-center border-r hover:bg-emerald-50',
                                                                current?.hasil === 'layak' && 'bg-emerald-50',
                                                            )}
                                                            onClick={() =>
                                                                setHasil(
                                                                    k.id,
                                                                    item.id,
                                                                    'layak',
                                                                    current?.hasil !== 'layak',
                                                                )
                                                            }
                                                        >
                                                            <Checkbox
                                                                checked={current?.hasil === 'layak'}
                                                                className="pointer-events-none border-emerald-600 data-[state=checked]:border-emerald-600 data-[state=checked]:bg-emerald-600"
                                                                tabIndex={-1}
                                                                aria-label={`Layak ${k.nama} ${item.nama}`}
                                                            />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className={cn(
                                                                'flex items-center justify-center hover:bg-rose-50',
                                                                current?.hasil === 'tidak_layak' && 'bg-rose-50',
                                                            )}
                                                            onClick={() =>
                                                                setHasil(
                                                                    k.id,
                                                                    item.id,
                                                                    'tidak_layak',
                                                                    current?.hasil !== 'tidak_layak',
                                                                )
                                                            }
                                                        >
                                                            <Checkbox
                                                                checked={current?.hasil === 'tidak_layak'}
                                                                className="pointer-events-none border-rose-600 data-[state=checked]:border-rose-600 data-[state=checked]:bg-rose-600"
                                                                tabIndex={-1}
                                                                aria-label={`Tidak Layak ${k.nama} ${item.nama}`}
                                                            />
                                                        </button>
                                                    </div>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-xl border bg-sky-50 px-4 py-3">
                        <div className="text-sm text-sky-800">Pemeriksaan</div>
                        <div className="text-2xl font-semibold text-sky-950">{stats.pemeriksaan}</div>
                    </div>
                    <div className="rounded-xl border bg-emerald-50 px-4 py-3">
                        <div className="text-sm text-emerald-800">Layak</div>
                        <div className="text-2xl font-semibold text-emerald-950">{stats.layak}</div>
                    </div>
                    <div className="rounded-xl border bg-rose-50 px-4 py-3">
                        <div className="text-sm text-rose-800">Tidak Layak</div>
                        <div className="text-2xl font-semibold text-rose-950">{stats.tidakLayak}</div>
                    </div>
                    <div className="rounded-xl border bg-amber-50 px-4 py-3">
                        <div className="text-sm text-amber-800">Belum Diisi</div>
                        <div className="text-2xl font-semibold text-amber-950">{stats.belum}</div>
                    </div>
                </div>

                {temuanPending.length > 0 && (
                    <div className="grid gap-3 rounded-lg border border-rose-200 bg-rose-50/50 p-4">
                        <div>
                            <h2 className="font-semibold text-rose-950">Detail temuan (wajib)</h2>
                            <p className="text-sm text-rose-800">
                                Isi temuan dan rekomendasi untuk setiap item Tidak Layak.
                            </p>
                        </div>
                        {temuanPending.map((row) => (
                            <div key={row.key} className="grid gap-2 rounded-md border bg-background p-3">
                                <div className="text-sm font-medium">
                                    {row.kendaraanNama} — {row.itemNama}
                                </div>
                                <div className="grid gap-2 md:grid-cols-2">
                                    <div className="grid gap-1">
                                        <Label>Temuan</Label>
                                        <Textarea
                                            rows={2}
                                            value={row.value.temuan}
                                            onChange={(e) => setTemuanField(row.key, 'temuan', e.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        <Label>Rekomendasi</Label>
                                        <Textarea
                                            rows={2}
                                            value={row.value.rekomendasi}
                                            onChange={(e) =>
                                                setTemuanField(row.key, 'rekomendasi', e.target.value)
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <div className="grid gap-2">
                    <Label htmlFor="catatan">Catatan umum (opsional, berlaku ke semua kendaraan)</Label>
                    <Textarea
                        id="catatan"
                        rows={2}
                        value={catatan}
                        onChange={(e) => setCatatan(e.target.value)}
                    />
                </div>

                {clientError && <p className="text-sm text-destructive">{clientError}</p>}
                <InputError message={errors.pemeriksaan as string | undefined} />
                {Object.entries(errors).map(([key, message]) =>
                    key === 'pemeriksaan' ? null : (
                        <InputError key={key} message={typeof message === 'string' ? message : undefined} />
                    ),
                )}

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing || kendaraan.length === 0}>
                        Simpan Semua
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
