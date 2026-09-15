import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
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
    kode_distributor: string | null;
    nama_distributor: string;
    alamat: string | null;
    no_telp: string | null;
    email: string | null;
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
    { title: 'Master Distributor', href: '/aset/master/distributor' },
];

const emptyForm = {
    kode_distributor: '',
    nama_distributor: '',
    alamat: '',
    no_telp: '',
    email: '',
};

export default function MasterDistributorIndex({ items, filters, stats }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [isDeleting, setIsDeleting] = useState(false);

    const createForm = useForm({ ...emptyForm });
    const editForm = useForm({ ...emptyForm });

    const deletablePageIds = useMemo(
        () => items.data.filter((row) => row.aset_count === 0).map((row) => row.id),
        [items.data],
    );
    const allDeletableSelected =
        deletablePageIds.length > 0 && deletablePageIds.every((id) => selectedIds.has(id));
    const someSelected = selectedIds.size > 0;

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset/master/distributor', { q: search || undefined }, { preserveState: true });
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
            kode_distributor: row.kode_distributor ?? '',
            nama_distributor: row.nama_distributor,
            alamat: row.alamat ?? '',
            no_telp: row.no_telp ?? '',
            email: row.email ?? '',
        });
        editForm.clearErrors();
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/aset/master/distributor/simpan', {
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
        editForm.patch(`/aset/master/distributor/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const destroyDistributor = (row: Row) => {
        if (row.aset_count > 0) {
            alert(
                `Distributor "${row.nama_distributor}" masih dipakai (${row.aset_count} aset). Ubah referensi aset sebelum menghapus.`,
            );
            return;
        }

        if (!confirm(`Hapus distributor "${row.nama_distributor}"?`)) {
            return;
        }
        router.delete(`/aset/master/distributor/${row.id}`, { preserveScroll: true });
    };

    const toggleSelect = (row: Row): void => {
        if (row.aset_count > 0) {
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
        const inUseCount = selectedRows.filter((row) => row.aset_count > 0).length;
        const deletableCount = selectedRows.length - inUseCount;

        if (deletableCount === 0) {
            alert('Semua distributor yang dipilih masih dipakai aset dan tidak bisa dihapus.');

            return;
        }

        const message =
            inUseCount > 0
                ? `Hapus ${deletableCount} distributor yang bisa dihapus?\n\n${inUseCount} distributor lain dilewati karena masih dipakai aset.`
                : `Hapus ${deletableCount} distributor yang dipilih?`;

        if (!confirm(message)) {
            return;
        }

        setIsDeleting(true);
        router.post(
            '/aset/master/distributor/bulk-delete',
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
            <Head title="Master Distributor" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Distributor</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola distributor untuk form tambah aset. Import:{' '}
                            <code className="text-xs">
                                php artisan aset:import-master file.csv --tipe=distributor
                            </code>
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{stats.total} distributor</Badge>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Distributor
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
                            <Label htmlFor="create_kode">Kode (opsional)</Label>
                            <Input
                                id="create_kode"
                                value={createForm.data.kode_distributor}
                                onChange={(e) =>
                                    createForm.setData('kode_distributor', e.target.value.toUpperCase())
                                }
                                placeholder="Auto jika kosong"
                            />
                            <InputError message={createForm.errors.kode_distributor} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="create_nama">Nama distributor</Label>
                            <Input
                                id="create_nama"
                                value={createForm.data.nama_distributor}
                                onChange={(e) => createForm.setData('nama_distributor', e.target.value)}
                                placeholder="Contoh: PT Medika Sejahtera"
                                required
                            />
                            <InputError message={createForm.errors.nama_distributor} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="create_telp">No. telepon</Label>
                            <Input
                                id="create_telp"
                                value={createForm.data.no_telp}
                                onChange={(e) => createForm.setData('no_telp', e.target.value)}
                                placeholder="Opsional"
                            />
                            <InputError message={createForm.errors.no_telp} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="create_email">Email</Label>
                            <Input
                                id="create_email"
                                type="email"
                                value={createForm.data.email}
                                onChange={(e) => createForm.setData('email', e.target.value)}
                                placeholder="Opsional"
                            />
                            <InputError message={createForm.errors.email} />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="create_alamat">Alamat</Label>
                            <Input
                                id="create_alamat"
                                value={createForm.data.alamat}
                                onChange={(e) => createForm.setData('alamat', e.target.value)}
                                placeholder="Opsional"
                            />
                            <InputError message={createForm.errors.alamat} />
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
                            <Label htmlFor="edit_kode">Kode distributor</Label>
                            <Input
                                id="edit_kode"
                                value={editForm.data.kode_distributor}
                                onChange={(e) =>
                                    editForm.setData('kode_distributor', e.target.value.toUpperCase())
                                }
                                required
                            />
                            <InputError message={editForm.errors.kode_distributor} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_nama">Nama distributor</Label>
                            <Input
                                id="edit_nama"
                                value={editForm.data.nama_distributor}
                                onChange={(e) => editForm.setData('nama_distributor', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.nama_distributor} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_telp">No. telepon</Label>
                            <Input
                                id="edit_telp"
                                value={editForm.data.no_telp}
                                onChange={(e) => editForm.setData('no_telp', e.target.value)}
                            />
                            <InputError message={editForm.errors.no_telp} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_email">Email</Label>
                            <Input
                                id="edit_email"
                                type="email"
                                value={editForm.data.email}
                                onChange={(e) => editForm.setData('email', e.target.value)}
                            />
                            <InputError message={editForm.errors.email} />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="edit_alamat">Alamat</Label>
                            <Input
                                id="edit_alamat"
                                value={editForm.data.alamat}
                                onChange={(e) => editForm.setData('alamat', e.target.value)}
                            />
                            <InputError message={editForm.errors.alamat} />
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
                                placeholder="Kode, nama, telepon, atau email..."
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
                        <p className="text-sm font-medium">{selectedIds.size} distributor dipilih</p>
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
                                        checked={allDeletableSelected}
                                        disabled={deletablePageIds.length === 0}
                                        onCheckedChange={() => toggleSelectAll()}
                                        aria-label="Pilih semua distributor yang bisa dihapus di halaman ini"
                                    />
                                </th>
                                <th className="px-4 py-3 font-medium">Kode</th>
                                <th className="px-4 py-3 font-medium">Nama</th>
                                <th className="px-4 py-3 font-medium">Kontak</th>
                                <th className="px-4 py-3 font-medium">Aset</th>
                                <th className="px-4 py-3 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Belum ada distributor. Tambah manual atau import CSV.
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((row) => {
                                    const selected = selectedIds.has(row.id);
                                    const inUse = row.aset_count > 0;

                                    return (
                                        <tr
                                            key={row.id}
                                            className={cn(
                                                'border-b border-border/60',
                                                selected && 'bg-primary/5',
                                                inUse && 'opacity-80',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <Checkbox
                                                    checked={selected}
                                                    disabled={inUse}
                                                    onCheckedChange={() => toggleSelect(row)}
                                                    aria-label={`Pilih ${row.nama_distributor}`}
                                                />
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {row.kode_distributor ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>{row.nama_distributor}</div>
                                                {row.alamat ? (
                                                    <div className="mt-0.5 text-xs text-muted-foreground">
                                                        {row.alamat}
                                                    </div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                <div className="space-y-0.5 text-xs">
                                                    <div>{row.no_telp ?? '—'}</div>
                                                    <div>{row.email ?? '—'}</div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.aset_count}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_distributor}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => destroyDistributor(row)}
                                                        aria-label={`Hapus ${row.nama_distributor}`}
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
