import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
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
    kode_jenis: string | null;
    nama_jenis: string;
    aset_merk_id: number | null;
    merk_nama: string | null;
    barang_count: number;
};

type MerkOpt = { id: number; nama: string };

type Paginated = {
    data: Row[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string; merk_id?: string; only_unassigned?: boolean };
    stats: { total: number; unassigned: number; assigned: number };
    merkOptions: MerkOpt[];
};

type Flash = { success?: string; error?: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Master Jenis', href: '/aset/master/jenis' },
];

export default function MasterJenisIndex({ items, filters, stats, merkOptions }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [merkFilter, setMerkFilter] = useState(filters.merk_id || '__all__');
    const [onlyUnassigned, setOnlyUnassigned] = useState(Boolean(filters.only_unassigned));
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [isDeleting, setIsDeleting] = useState(false);

    const deletablePageIds = useMemo(
        () => items.data.filter((row) => row.barang_count === 0).map((row) => row.id),
        [items.data],
    );
    const allDeletableSelected =
        deletablePageIds.length > 0 && deletablePageIds.every((id) => selectedIds.has(id));
    const someSelected = selectedIds.size > 0;

    const createForm = useForm({
        kode_jenis: '',
        nama_jenis: '',
        aset_merk_id: '__none__',
    });

    const editForm = useForm({
        kode_jenis: '',
        nama_jenis: '',
        aset_merk_id: '__none__',
    });

    const merkFilterOptions = useMemo<SearchSelectOption[]>(
        () => [
            { value: '__all__', label: 'Semua merk' },
            ...merkOptions.map((m) => ({ value: String(m.id), label: m.nama })),
        ],
        [merkOptions],
    );

    const merkSelectOptions = useMemo<SearchSelectOption[]>(
        () => [
            { value: '__none__', label: 'Belum diisi' },
            ...merkOptions.map((m) => ({ value: String(m.id), label: m.nama })),
        ],
        [merkOptions],
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/aset/master/jenis',
            {
                q: search || undefined,
                merk_id: merkFilter === '__all__' ? undefined : merkFilter,
                only_unassigned: onlyUnassigned ? 1 : undefined,
            },
            { preserveState: true },
        );
    };

    const openCreate = () => {
        setEditingId(null);
        setShowCreate(true);
        createForm.reset();
        createForm.setData('aset_merk_id', '__none__');
        createForm.clearErrors();
    };

    const openEdit = (row: Row) => {
        setShowCreate(false);
        setEditingId(row.id);
        editForm.setData({
            kode_jenis: row.kode_jenis ?? '',
            nama_jenis: row.nama_jenis,
            aset_merk_id: row.aset_merk_id ? String(row.aset_merk_id) : '__none__',
        });
        editForm.clearErrors();
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.transform((data) => ({
            ...data,
            aset_merk_id: data.aset_merk_id === '__none__' ? null : data.aset_merk_id,
        }));
        createForm.post('/aset/master/jenis/simpan', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.setData('aset_merk_id', '__none__');
                setShowCreate(false);
            },
            onFinish: () => createForm.transform((data) => data),
        });
    };

    const submitEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingId) {
            return;
        }
        editForm.transform((data) => ({
            ...data,
            aset_merk_id: data.aset_merk_id === '__none__' ? null : data.aset_merk_id,
        }));
        editForm.patch(`/aset/master/jenis/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
            onFinish: () => editForm.transform((data) => data),
        });
    };

    const updateMerk = (rowId: number, value: string) => {
        router.patch(
            `/aset/master/jenis/${rowId}/merk`,
            { aset_merk_id: value === '__none__' ? null : Number(value) },
            { preserveScroll: true, preserveState: true },
        );
    };

    const destroyJenis = (row: Row) => {
        if (row.barang_count > 0) {
            alert(
                `Jenis "${row.nama_jenis}" masih dipakai (${row.barang_count} barang). Ubah referensi barang sebelum menghapus.`,
            );
            return;
        }
        if (!confirm(`Hapus jenis "${row.nama_jenis}"?`)) {
            return;
        }
        router.delete(`/aset/master/jenis/${row.id}`, { preserveScroll: true });
    };

    const toggleSelect = (row: Row): void => {
        if (row.barang_count > 0) {
            return;
        }

        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(row.id)) {
                next.delete(row.id);
            } else {
                next.add(row.id);
            }

            return next;
        });
    };

    const toggleSelectAll = (): void => {
        if (allDeletableSelected) {
            setSelectedIds(new Set());

            return;
        }

        setSelectedIds(new Set(deletablePageIds));
    };

    const handleBulkDelete = (): void => {
        if (selectedIds.size === 0) {
            return;
        }

        const selectedRows = items.data.filter((row) => selectedIds.has(row.id));
        const inUseCount = selectedRows.filter((row) => row.barang_count > 0).length;
        const deletableCount = selectedRows.length - inUseCount;

        if (deletableCount === 0) {
            alert('Semua jenis yang dipilih masih dipakai barang dan tidak bisa dihapus.');

            return;
        }

        const message =
            inUseCount > 0
                ? `Hapus ${deletableCount} jenis yang bisa dihapus?\n\n${inUseCount} jenis lain dilewati karena masih dipakai barang.`
                : `Hapus ${deletableCount} jenis yang dipilih?`;

        if (!confirm(message)) {
            return;
        }

        setIsDeleting(true);
        router.post(
            '/aset/master/jenis/bulk-delete',
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
            <Head title="Master Jenis" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Jenis</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola tipe/jenis barang dan hubungkan ke merk agar form tambah aset hanya menampilkan
                            jenis yang relevan. Import:{' '}
                            <code className="text-xs">
                                php artisan aset:import-master file.csv --tipe=jenis
                            </code>
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex flex-wrap gap-2 text-sm">
                            <Badge variant="secondary">{stats.total} total</Badge>
                            <Badge variant="outline">{stats.assigned} terhubung</Badge>
                            <Badge variant="destructive">{stats.unassigned} belum</Badge>
                        </div>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Jenis
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
                        className="grid gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="create_kode">Kode (opsional)</Label>
                            <Input
                                id="create_kode"
                                value={createForm.data.kode_jenis}
                                onChange={(e) => createForm.setData('kode_jenis', e.target.value)}
                                placeholder="Auto jika kosong"
                            />
                            <InputError message={createForm.errors.kode_jenis} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="create_nama">Nama jenis</Label>
                            <Input
                                id="create_nama"
                                value={createForm.data.nama_jenis}
                                onChange={(e) => createForm.setData('nama_jenis', e.target.value)}
                                placeholder="Contoh: ThinkPad T14"
                                required
                            />
                            <InputError message={createForm.errors.nama_jenis} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Merk</Label>
                            <SearchSelect
                                options={merkSelectOptions}
                                value={createForm.data.aset_merk_id}
                                onChange={(v) => createForm.setData('aset_merk_id', v || '__none__')}
                                placeholder="Pilih merk"
                                isClearable={false}
                            />
                            <InputError message={createForm.errors.aset_merk_id} />
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
                        className="grid gap-4 rounded-xl border border-teal-700/20 bg-teal-50/30 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:bg-teal-950/20"
                    >
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_kode">Kode</Label>
                            <Input
                                id="edit_kode"
                                value={editForm.data.kode_jenis}
                                onChange={(e) => editForm.setData('kode_jenis', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.kode_jenis} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_nama">Nama jenis</Label>
                            <Input
                                id="edit_nama"
                                value={editForm.data.nama_jenis}
                                onChange={(e) => editForm.setData('nama_jenis', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.nama_jenis} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Merk</Label>
                            <SearchSelect
                                options={merkSelectOptions}
                                value={editForm.data.aset_merk_id}
                                onChange={(v) => editForm.setData('aset_merk_id', v || '__none__')}
                                placeholder="Pilih merk"
                                isClearable={false}
                            />
                            <InputError message={editForm.errors.aset_merk_id} />
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
                                placeholder="Nama atau kode jenis..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="w-full space-y-1.5 sm:w-48">
                        <Label>Filter merk</Label>
                        <SearchSelect
                            options={merkFilterOptions}
                            value={merkFilter}
                            onChange={setMerkFilter}
                            placeholder="Semua"
                            isClearable={false}
                        />
                    </div>
                    <div className="flex items-center gap-2 pb-2 sm:pb-2.5">
                        <Checkbox
                            id="only_unassigned"
                            checked={onlyUnassigned}
                            onCheckedChange={(c) => setOnlyUnassigned(Boolean(c))}
                        />
                        <Label htmlFor="only_unassigned" className="cursor-pointer font-normal">
                            Hanya belum terhubung
                        </Label>
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                {someSelected ? (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-destructive/25 bg-destructive/5 px-3 py-2.5">
                        <p className="text-sm font-medium">{selectedIds.size} jenis dipilih</p>
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

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="w-10 px-4 py-3">
                                        <Checkbox
                                            checked={allDeletableSelected}
                                            disabled={deletablePageIds.length === 0}
                                            onCheckedChange={() => toggleSelectAll()}
                                            aria-label="Pilih semua jenis yang bisa dihapus di halaman ini"
                                        />
                                    </th>
                                    <th className="px-4 py-3 font-medium">Kode</th>
                                    <th className="px-4 py-3 font-medium">Nama jenis</th>
                                    <th className="px-4 py-3 font-medium">Merk</th>
                                    <th className="px-4 py-3 font-medium">Dipakai</th>
                                    <th className="px-4 py-3 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada data jenis. Tambah manual, import CSV, atau buat dari form
                                            tambah aset.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => {
                                        const selected = selectedIds.has(row.id);
                                        const canDelete = row.barang_count === 0;

                                        return (
                                        <tr
                                            key={row.id}
                                            className={cn(
                                                'border-b last:border-0',
                                                selected && 'bg-primary/5',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <Checkbox
                                                    checked={selected}
                                                    disabled={!canDelete}
                                                    onCheckedChange={() => toggleSelect(row)}
                                                    aria-label={
                                                        canDelete
                                                            ? `Pilih ${row.nama_jenis}`
                                                            : `${row.nama_jenis} masih dipakai barang`
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode_jenis ?? '—'}</td>
                                            <td className="px-4 py-3 font-medium">{row.nama_jenis}</td>
                                            <td className="px-4 py-3">
                                                <SearchSelect
                                                    options={merkSelectOptions}
                                                    value={
                                                        row.aset_merk_id ? String(row.aset_merk_id) : '__none__'
                                                    }
                                                    onChange={(v) => updateMerk(row.id, v || '__none__')}
                                                    placeholder="Pilih merk"
                                                    isClearable={false}
                                                    size="sm"
                                                    className="w-[200px]"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.barang_count}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_jenis}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => destroyJenis(row)}
                                                        aria-label={`Hapus ${row.nama_jenis}`}
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
