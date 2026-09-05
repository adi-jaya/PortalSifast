import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    kode: string;
    nama_berkas: string;
    kategori: string;
    no_urut: number;
    berkas_count: number;
};

type Paginated = {
    data: Row[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string; kategori?: string };
    kategoriOptions: string[];
};

type Flash = { success?: string; error?: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
    { title: 'Master Jenis Berkas', href: '/berkas-kepegawaian/master' },
];

export default function MasterBerkasPegawaiIndex({ items, filters, kategoriOptions }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [search, setSearch] = useState(filters.q ?? '');
    const [kategoriFilter, setKategoriFilter] = useState(filters.kategori ?? '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingKode, setEditingKode] = useState<string | null>(null);

    const form = useForm({
        kode: '',
        nama_berkas: '',
        kategori: '',
        no_urut: 0,
    });

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/berkas-kepegawaian/master',
            {
                q: search || undefined,
                kategori: kategoriFilter || undefined,
            },
            { preserveState: true },
        );
    };

    const openCreate = () => {
        setEditingKode(null);
        form.reset();
        form.setData({
            kode: '',
            nama_berkas: '',
            kategori: '',
            no_urut: 0,
        });
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (row: Row) => {
        setEditingKode(row.kode);
        form.setData({
            kode: row.kode,
            nama_berkas: row.nama_berkas,
            kategori: row.kategori,
            no_urut: row.no_urut,
        });
        form.clearErrors();
        setDialogOpen(true);
    };

    const submitForm = (e: FormEvent) => {
        e.preventDefault();

        if (editingKode) {
            form.put(`/berkas-kepegawaian/master/${encodeURIComponent(editingKode)}`, {
                preserveScroll: true,
                onSuccess: () => setDialogOpen(false),
            });

            return;
        }

        form.post('/berkas-kepegawaian/master', {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        });
    };

    const destroyRow = (row: Row) => {
        if (row.berkas_count > 0) {
            alert(
                `Jenis berkas "${row.nama_berkas}" masih dipakai (${row.berkas_count} berkas). Hapus atau pindahkan berkas sebelum menghapus master.`,
            );

            return;
        }

        if (!confirm(`Hapus jenis berkas "${row.nama_berkas}"?`)) {
            return;
        }

        router.delete(`/berkas-kepegawaian/master/${encodeURIComponent(row.kode)}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Jenis Berkas" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Jenis Berkas</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola jenis dokumen kepegawaian (kode, nama, kategori, urutan).
                        </p>
                    </div>
                    <Button type="button" onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah Jenis
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
                                placeholder="Kode atau nama berkas..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="w-full space-y-1.5 sm:w-56">
                        <Label htmlFor="kategori">Kategori</Label>
                        <select
                            id="kategori"
                            value={kategoriFilter}
                            onChange={(e) => setKategoriFilter(e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Semua kategori</option>
                            {kategoriOptions.map((kategori) => (
                                <option key={kategori} value={kategori}>
                                    {kategori}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">No Urut</th>
                                    <th className="px-4 py-3 font-medium">Kode</th>
                                    <th className="px-4 py-3 font-medium">Nama Berkas</th>
                                    <th className="px-4 py-3 font-medium">Kategori</th>
                                    <th className="px-4 py-3 font-medium">Dipakai</th>
                                    <th className="px-4 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada master jenis berkas.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.kode} className="border-b last:border-0">
                                            <td className="px-4 py-3 tabular-nums">{row.no_urut}</td>
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode}</td>
                                            <td className="px-4 py-3">{row.nama_berkas}</td>
                                            <td className="px-4 py-3">{row.kategori}</td>
                                            <td className="px-4 py-3 tabular-nums">{row.berkas_count}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_berkas}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => destroyRow(row)}
                                                        aria-label={`Hapus ${row.nama_berkas}`}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
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

                {items.last_page > 1 ? (
                    <div className="flex flex-wrap gap-2">
                        {items.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                type="button"
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                ) : null}
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingKode ? 'Edit Jenis Berkas' : 'Tambah Jenis Berkas'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitForm} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="kode">Kode</Label>
                            <Input
                                id="kode"
                                value={form.data.kode}
                                onChange={(e) => form.setData('kode', e.target.value)}
                                disabled={Boolean(editingKode)}
                                required={!editingKode}
                                maxLength={20}
                            />
                            <InputError message={form.errors.kode} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="nama_berkas">Nama berkas</Label>
                            <Input
                                id="nama_berkas"
                                value={form.data.nama_berkas}
                                onChange={(e) => form.setData('nama_berkas', e.target.value)}
                                required
                                maxLength={150}
                            />
                            <InputError message={form.errors.nama_berkas} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="kategori">Kategori</Label>
                            <Input
                                id="kategori"
                                value={form.data.kategori}
                                onChange={(e) => form.setData('kategori', e.target.value)}
                                list="kategori-suggestions"
                                required
                                maxLength={100}
                            />
                            <datalist id="kategori-suggestions">
                                {kategoriOptions.map((kategori) => (
                                    <option key={kategori} value={kategori} />
                                ))}
                            </datalist>
                            <InputError message={form.errors.kategori} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="no_urut">No urut</Label>
                            <Input
                                id="no_urut"
                                type="number"
                                min={0}
                                value={form.data.no_urut}
                                onChange={(e) => form.setData('no_urut', Number(e.target.value))}
                                required
                            />
                            <InputError message={form.errors.no_urut} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Menyimpan...' : editingKode ? 'Perbarui' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
