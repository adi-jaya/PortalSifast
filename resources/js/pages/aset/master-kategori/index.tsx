import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

type Flash = { success?: string; error?: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Master Kategori', href: '/aset/master/kategori' },
];

export default function MasterKategoriIndex({ items, filters, kategoriOptions }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [mergeTargetId, setMergeTargetId] = useState<number | null>(null);
    const [mergeSourceId, setMergeSourceId] = useState<string>('');

    const createForm = useForm({
        kode_kategori: '',
        nama_kategori: '',
    });

    const editForm = useForm({
        kode_kategori: '',
        nama_kategori: '',
    });

    const sourceOptions = useMemo(
        () => kategoriOptions.filter((k) => k.id !== mergeTargetId),
        [kategoriOptions, mergeTargetId],
    );

    const mergeSourceOptions = useMemo<SearchSelectOption[]>(
        () =>
            sourceOptions.map((k) => ({
                value: String(k.id),
                label: k.nama,
                description: k.kode ?? undefined,
            })),
        [sourceOptions],
    );

    const applySearch = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset/master/kategori', { q: search || undefined }, { preserveState: true });
    };

    const openCreate = () => {
        setEditingId(null);
        setMergeTargetId(null);
        setShowCreate(true);
        createForm.reset();
        createForm.clearErrors();
    };

    const openEdit = (row: Row) => {
        setShowCreate(false);
        setMergeTargetId(null);
        setEditingId(row.id);
        editForm.setData({
            kode_kategori: row.kode_kategori ?? '',
            nama_kategori: row.nama_kategori,
        });
        editForm.clearErrors();
    };

    const openMerge = (targetId: number) => {
        setShowCreate(false);
        setEditingId(null);
        setMergeTargetId(targetId);
        setMergeSourceId('');
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/aset/master/kategori/simpan', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setShowCreate(false);
            },
        });
    };

    const submitEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingId) {
            return;
        }
        editForm.patch(`/aset/master/kategori/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
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

    const destroyKategori = (row: Row) => {
        if (row.usage_count > 0) {
            alert(
                `Kategori "${row.nama_kategori}" masih dipakai (${row.usage_count} referensi). Gabungkan ke kategori lain sebelum menghapus.`,
            );
            return;
        }
        if (!confirm(`Hapus kategori "${row.nama_kategori}"?`)) {
            return;
        }
        router.delete(`/aset/master/kategori/${row.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Kategori" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Kategori</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Satu kategori = satu kotak laporan. Gabungkan entri mirip (mis. Laptop / Notebook →
                            Komputer) agar tidak overlap.
                        </p>
                    </div>
                    <Button type="button" onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah Kategori
                    </Button>
                </div>

                {flash.success ? (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash.success}
                    </div>
                ) : null}

                {flash.error ? (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {flash.error}
                    </div>
                ) : null}

                <form onSubmit={applySearch} className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end">
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Nama atau kode kategori..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                {showCreate && (
                    <form
                        onSubmit={submitCreate}
                        className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end"
                    >
                        <div className="w-full space-y-1.5 sm:w-40">
                            <Label htmlFor="create_kode">Kode (opsional)</Label>
                            <Input
                                id="create_kode"
                                value={createForm.data.kode_kategori}
                                onChange={(e) => createForm.setData('kode_kategori', e.target.value)}
                                placeholder="Otomatis"
                                maxLength={20}
                            />
                            <InputError message={createForm.errors.kode_kategori} />
                        </div>
                        <div className="flex-1 space-y-1.5">
                            <Label htmlFor="create_nama">Nama kategori</Label>
                            <Input
                                id="create_nama"
                                value={createForm.data.nama_kategori}
                                onChange={(e) => createForm.setData('nama_kategori', e.target.value)}
                                placeholder="Mis. Komputer"
                                required
                            />
                            <InputError message={createForm.errors.nama_kategori} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={() => setShowCreate(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </div>
                    </form>
                )}

                {editingId !== null && (
                    <form
                        onSubmit={submitEdit}
                        className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end"
                    >
                        <div className="w-full space-y-1.5 sm:w-40">
                            <Label htmlFor="edit_kode">Kode</Label>
                            <Input
                                id="edit_kode"
                                value={editForm.data.kode_kategori}
                                onChange={(e) => editForm.setData('kode_kategori', e.target.value)}
                                maxLength={20}
                                required
                            />
                            <InputError message={editForm.errors.kode_kategori} />
                        </div>
                        <div className="flex-1 space-y-1.5">
                            <Label htmlFor="edit_nama">Nama kategori</Label>
                            <Input
                                id="edit_nama"
                                value={editForm.data.nama_kategori}
                                onChange={(e) => editForm.setData('nama_kategori', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.nama_kategori} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={() => setEditingId(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui'}
                            </Button>
                        </div>
                    </form>
                )}

                {mergeTargetId !== null && (
                    <div className="flex flex-col gap-3 rounded-xl border border-amber-500/30 bg-amber-50/50 p-4 dark:bg-amber-950/20 sm:flex-row sm:items-end">
                        <div className="flex-1 space-y-1.5">
                            <Label>Gabung ke: {kategoriOptions.find((k) => k.id === mergeTargetId)?.nama}</Label>
                            <SearchSelect
                                options={mergeSourceOptions}
                                value={mergeSourceId || undefined}
                                onChange={setMergeSourceId}
                                placeholder="Pilih kategori sumber (akan dihapus)"
                                isClearable
                            />
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
                                                <div className="flex flex-wrap gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_kategori}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => destroyKategori(row)}
                                                        aria-label={`Hapus ${row.nama_kategori}`}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => openMerge(row.id)}
                                                    >
                                                        Gabung ke sini…
                                                    </Button>
                                                </div>
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
