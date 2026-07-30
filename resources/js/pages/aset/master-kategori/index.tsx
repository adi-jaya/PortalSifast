import { Head, router, usePage } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    kode_kategori: string | null;
    nama_kategori: string;
    barang_count: number;
    non_alkes_count: number;
    usage_count: number;
};

type Opt = { id: number; kode: string | null; nama: string };

type Paginated = {
    data: Row[];
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string };
    kategoriOptions: Opt[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Master Kategori', href: '/aset/master/kategori' },
];

export default function MasterKategoriIndex({ items, filters, kategoriOptions }: Props) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [mergeTargetId, setMergeTargetId] = useState<number | null>(null);
    const [mergeSourceId, setMergeSourceId] = useState<string>('');

    const sourceOptions = useMemo(
        () => kategoriOptions.filter((k) => k.id !== mergeTargetId),
        [kategoriOptions, mergeTargetId],
    );

    const applySearch = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset/master/kategori', { q: search || undefined }, { preserveState: true });
    };

    const openMerge = (targetId: number) => {
        setMergeTargetId(targetId);
        setMergeSourceId('');
    };

    const submitMerge = () => {
        if (!mergeTargetId || !mergeSourceId) {
            return;
        }
        const source = kategoriOptions.find((k) => String(k.id) === mergeSourceId);
        const target = kategoriOptions.find((k) => k.id === mergeTargetId);
        if (
            !confirm(
                `Gabung "${source?.nama}" ke "${target?.nama}"?\nSemua referensi dipindah, lalu kategori sumber dihapus.`,
            )
        ) {
            return;
        }
        router.post(
            `/aset/master/kategori/${mergeTargetId}/merge`,
            { source_id: Number(mergeSourceId) },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMergeTargetId(null);
                    setMergeSourceId('');
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Kategori" />

            <div className="flex flex-col gap-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">Master Kategori</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Satu kategori = satu kotak laporan. Gabungkan entri mirip (mis. Laptop / Notebook →
                        Komputer) agar tidak overlap.
                    </p>
                </div>

                {flash?.success ? (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash.success}
                    </div>
                ) : null}

                <form onSubmit={applySearch} className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end">
                    <div className="flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <Input
                            id="q"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Nama atau kode kategori..."
                        />
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                {mergeTargetId !== null && (
                    <div className="flex flex-col gap-3 rounded-xl border border-amber-500/30 bg-amber-50/50 p-4 dark:bg-amber-950/20 sm:flex-row sm:items-end">
                        <div className="flex-1 space-y-1.5">
                            <Label>Gabung ke: {kategoriOptions.find((k) => k.id === mergeTargetId)?.nama}</Label>
                            <Select value={mergeSourceId || undefined} onValueChange={setMergeSourceId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih kategori sumber (akan dihapus)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {sourceOptions.map((k) => (
                                        <SelectItem key={k.id} value={String(k.id)}>
                                            {k.nama} {k.kode ? `(${k.kode})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={() => setMergeTargetId(null)}>
                                Batal
                            </Button>
                            <Button type="button" onClick={submitMerge} disabled={!mergeSourceId}>
                                Gabungkan
                            </Button>
                        </div>
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Kode</th>
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Pemakaian</th>
                                    <th className="px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada kategori.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode_kategori ?? '—'}</td>
                                            <td className="px-4 py-3 font-medium">{row.nama_kategori}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-1.5">
                                                    <Badge variant="secondary">{row.barang_count} barang</Badge>
                                                    <Badge variant="outline">{row.non_alkes_count} katalog</Badge>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => openMerge(row.id)}
                                                >
                                                    Gabung ke sini…
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {items.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {items.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
