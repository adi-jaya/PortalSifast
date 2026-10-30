import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { MasterCsvActions } from '@/components/aset/master-csv-actions';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    kode_ruang: string;
    nama_ruang: string;
    aset_count: number;
};

type Paginated = {
    data: Row[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string };
    stats: { total: number };
};

type Flash = { success?: string; error?: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Master Ruang', href: '/aset/master/ruang' },
];

export default function MasterRuangIndex({ items, filters, stats }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [isDeleting, setIsDeleting] = useState(false);

    const createForm = useForm({
        kode_ruang: '',
        nama_ruang: '',
    });

    const editForm = useForm({
        kode_ruang: '',
        nama_ruang: '',
    });

    const pageIds = useMemo(() => items.data.map((row) => row.id), [items.data]);
    const allSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));
    const someSelected = selectedIds.size > 0;
    const selectedAsetCount = useMemo(
        () => items.data.filter((row) => selectedIds.has(row.id)).reduce((sum, row) => sum + row.aset_count, 0),
        [items.data, selectedIds],
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset/master/ruang', { q: search || undefined }, { preserveState: true });
    };

    const openCreate = () => {
        setEditingId(null);
        setShowCreate(true);
        createForm.reset();
        createForm.clearErrors();
    };

    const openEdit = (row: Row) => {
        setShowCreate(false);
        setEditingId(row.id);
        editForm.setData({
            kode_ruang: row.kode_ruang,
            nama_ruang: row.nama_ruang,
        });
        editForm.clearErrors();
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/aset/master/ruang/simpan', {
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
        editForm.patch(`/aset/master/ruang/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const toggleSelect = (id: number): void => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    const toggleSelectAll = (): void => {
        if (allSelected) {
            setSelectedIds(new Set());

            return;
        }

        setSelectedIds(new Set(pageIds));
    };

    const destroyRuang = (row: Row) => {
        const asetNote =
            row.aset_count > 0
                ? `\n\n${row.aset_count} aset di ruang ini ikut dihapus (cascade).`
                : '';
        if (!confirm(`Hapus ruang "${row.nama_ruang}"?${asetNote}`)) {
            return;
        }
        router.delete(`/aset/master/ruang/${row.id}`, { preserveScroll: true });
    };

    const handleBulkDelete = (): void => {
        if (selectedIds.size === 0) {
            return;
        }

        const asetNote =
            selectedAsetCount > 0
                ? `\n\n${selectedAsetCount} aset di ruang terpilih ikut dihapus (cascade).`
                : '';
        if (!confirm(`Hapus ${selectedIds.size} ruang yang dipilih?${asetNote}`)) {
            return;
        }

        setIsDeleting(true);
        router.post(
            '/aset/master/ruang/bulk-delete',
            { ids: Array.from(selectedIds) },
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsDeleting(false);
                    setSelectedIds(new Set());
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Ruang" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Ruang</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kode ruang dipakai di form tambah aset dan import unit CSV (`kode_ruang`). Hapus ruang
                            akan cascade: aset di ruang ikut dihapus, audit ruang ikut terhapus.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{stats.total} ruang</Badge>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Ruang
                        </Button>
                    </div>
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

                {showCreate ? (
                    <form
                        onSubmit={submitCreate}
                        className="grid gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="create_kode">Kode ruang</Label>
                            <Input
                                id="create_kode"
                                value={createForm.data.kode_ruang}
                                onChange={(e) => createForm.setData('kode_ruang', e.target.value.toUpperCase())}
                                placeholder="Contoh: IGD01"
                                required
                            />
                            <InputError message={createForm.errors.kode_ruang} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="create_nama">Nama ruang</Label>
                            <Input
                                id="create_nama"
                                value={createForm.data.nama_ruang}
                                onChange={(e) => createForm.setData('nama_ruang', e.target.value)}
                                placeholder="Contoh: IGD"
                                required
                            />
                            <InputError message={createForm.errors.nama_ruang} />
                        </div>
                        <div className="flex items-end gap-2">
                            <Button type="submit" disabled={createForm.processing}>
                                {createForm.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => setShowCreate(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : null}

                {editingId ? (
                    <form
                        onSubmit={submitEdit}
                        className="grid gap-4 rounded-xl border border-teal-700/20 bg-teal-50/30 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:bg-teal-950/20"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_kode">Kode ruang</Label>
                            <Input
                                id="edit_kode"
                                value={editForm.data.kode_ruang}
                                onChange={(e) => editForm.setData('kode_ruang', e.target.value.toUpperCase())}
                                required
                            />
                            <InputError message={editForm.errors.kode_ruang} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_nama">Nama ruang</Label>
                            <Input
                                id="edit_nama"
                                value={editForm.data.nama_ruang}
                                onChange={(e) => editForm.setData('nama_ruang', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.nama_ruang} />
                        </div>
                        <div className="flex items-end gap-2">
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui'}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => setEditingId(null)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : null}

                <MasterCsvActions tipe="ruang" />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end"
                >
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Kode atau nama ruang..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <Button type="submit" size="sm">
                        Cari
                    </Button>
                </form>

                {someSelected ? (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-destructive/25 bg-destructive/5 px-3 py-2.5">
                        <p className="text-sm font-medium">
                            {selectedIds.size} ruang dipilih
                            {selectedAsetCount > 0 ? ` · ${selectedAsetCount} aset ikut cascade` : ''}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => setSelectedIds(new Set())}>
                                Batalkan
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                disabled={isDeleting}
                                onClick={handleBulkDelete}
                            >
                                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                Hapus {selectedIds.size} dipilih
                            </Button>
                        </div>
                    </div>
                ) : null}

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="w-10 px-4 py-3">
                                    <Checkbox
                                        checked={allSelected}
                                        disabled={pageIds.length === 0}
                                        onCheckedChange={() => toggleSelectAll()}
                                        aria-label="Pilih semua ruang di halaman ini"
                                    />
                                </th>
                                <th className="px-4 py-3 font-medium">Kode</th>
                                <th className="px-4 py-3 font-medium">Nama ruang</th>
                                <th className="px-4 py-3 font-medium">Aset</th>
                                <th className="px-4 py-3 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                        Belum ada ruang. Tambah manual, import CSV, atau sinkron dari SIMRS.
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((row) => {
                                    const selected = selectedIds.has(row.id);

                                    return (
                                        <tr
                                            key={row.id}
                                            className={cn(
                                                'border-b border-border/60',
                                                selected && 'bg-primary/5',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <Checkbox
                                                    checked={selected}
                                                    onCheckedChange={() => toggleSelect(row.id)}
                                                    aria-label={`Pilih ${row.nama_ruang}`}
                                                />
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode_ruang}</td>
                                            <td className="px-4 py-3">{row.nama_ruang}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.aset_count}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_ruang}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => destroyRuang(row)}
                                                        aria-label={`Hapus ${row.nama_ruang}`}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                {items.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {items.links.map((link, i) => (
                            <Button
                                key={i}
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
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
