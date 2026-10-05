import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    nama: string;
    kategori: string | null;
    urutan: number;
    aktif: boolean;
};

type Paginated = { data: Row[] };

type Props = {
    items: Paginated;
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Item Checklist', href: '/driver/item-checklist' },
];

export default function DriverItemChecklistIndex({ items, filters }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string };
    const [search, setSearch] = useState(filters.q ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm({
        nama: '',
        kategori: '',
        urutan: '0',
        aktif: true,
    });

    const editForm = useForm({
        nama: '',
        kategori: '',
        urutan: '0',
        aktif: true,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Item Checklist" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold">Item Checklist</h1>
                    <Button
                        onClick={() => {
                            setEditingId(null);
                            setShowCreate(true);
                            createForm.reset();
                            createForm.setData('aktif', true);
                        }}
                    >
                        Tambah
                    </Button>
                </div>

                {flash.success && (
                    <div className="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm">
                        {flash.success}
                    </div>
                )}

                <form
                    onSubmit={(e: FormEvent) => {
                        e.preventDefault();
                        router.get('/driver/item-checklist', { q: search || undefined }, { preserveState: true });
                    }}
                    className="flex gap-2"
                >
                    <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari item..." />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>

                {showCreate && (
                    <form
                        className="grid gap-3 rounded-lg border p-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            createForm.transform((data) => ({
                                ...data,
                                urutan: Number(data.urutan),
                                aktif: Boolean(data.aktif),
                            }));
                            createForm.post('/driver/item-checklist', {
                                onSuccess: () => {
                                    createForm.reset();
                                    setShowCreate(false);
                                },
                            });
                        }}
                    >
                        <div className="grid gap-1">
                            <Label>Nama</Label>
                            <Input value={createForm.data.nama} onChange={(e) => createForm.setData('nama', e.target.value)} />
                            <InputError message={createForm.errors.nama} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Kategori</Label>
                            <Input value={createForm.data.kategori} onChange={(e) => createForm.setData('kategori', e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Urutan</Label>
                            <Input value={createForm.data.urutan} onChange={(e) => createForm.setData('urutan', e.target.value)} />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={createForm.data.aktif}
                                onChange={(e) => createForm.setData('aktif', e.target.checked)}
                            />
                            Aktif
                        </label>
                        <div className="flex gap-2">
                            <Button type="submit">Simpan</Button>
                            <Button type="button" variant="outline" onClick={() => setShowCreate(false)}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}

                <div className="grid gap-2">
                    {items.data.map((row) => (
                        <div key={row.id} className="rounded-lg border p-4">
                            {editingId === row.id ? (
                                <form
                                    className="grid gap-3"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        editForm.transform((data) => ({
                                            ...data,
                                            urutan: Number(data.urutan),
                                            aktif: Boolean(data.aktif),
                                        }));
                                        editForm.put(`/driver/item-checklist/${row.id}`, {
                                            onSuccess: () => setEditingId(null),
                                        });
                                    }}
                                >
                                    <Input value={editForm.data.nama} onChange={(e) => editForm.setData('nama', e.target.value)} />
                                    <Input value={editForm.data.kategori} onChange={(e) => editForm.setData('kategori', e.target.value)} />
                                    <Input value={editForm.data.urutan} onChange={(e) => editForm.setData('urutan', e.target.value)} />
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={editForm.data.aktif}
                                            onChange={(e) => editForm.setData('aktif', e.target.checked)}
                                        />
                                        Aktif
                                    </label>
                                    <div className="flex gap-2">
                                        <Button type="submit">Update</Button>
                                        <Button type="button" variant="outline" onClick={() => setEditingId(null)}>
                                            Batal
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <div className="flex items-center justify-between gap-2">
                                    <div>
                                        <div className="font-medium">
                                            {row.urutan}. {row.nama}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {row.kategori ?? '—'} · {row.aktif ? 'Aktif' : 'Nonaktif'}
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            setShowCreate(false);
                                            setEditingId(row.id);
                                            editForm.setData({
                                                nama: row.nama,
                                                kategori: row.kategori ?? '',
                                                urutan: String(row.urutan),
                                                aktif: row.aktif,
                                            });
                                        }}
                                    >
                                        Edit
                                    </Button>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
