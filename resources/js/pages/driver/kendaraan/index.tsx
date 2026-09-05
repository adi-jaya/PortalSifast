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
    no_polisi: string | null;
    merk: string | null;
    model: string | null;
    tahun: number | null;
    status: string;
};

type Paginated = {
    data: Row[];
};

type Props = {
    items: Paginated;
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Master Kendaraan', href: '/driver/kendaraan' },
];

export default function DriverKendaraanIndex({ items, filters }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string };
    const [search, setSearch] = useState(filters.q ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm({
        nama: '',
        no_polisi: '',
        merk: '',
        model: '',
        tahun: '',
        status: 'aktif',
    });

    const editForm = useForm({
        nama: '',
        no_polisi: '',
        merk: '',
        model: '',
        tahun: '',
        status: 'aktif',
    });

    const applySearch = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/driver/kendaraan', { q: search || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Kendaraan" />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold">Master Kendaraan</h1>
                    <Button
                        onClick={() => {
                            setEditingId(null);
                            setShowCreate(true);
                            createForm.reset();
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

                <form onSubmit={applySearch} className="flex gap-2">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari nama / nopol..."
                    />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>

                {showCreate && (
                    <form
                        className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            createForm.post('/driver/kendaraan', {
                                onSuccess: () => {
                                    createForm.reset();
                                    setShowCreate(false);
                                },
                            });
                        }}
                    >
                        <div className="grid gap-1 sm:col-span-2">
                            <Label>Nama</Label>
                            <Input value={createForm.data.nama} onChange={(e) => createForm.setData('nama', e.target.value)} />
                            <InputError message={createForm.errors.nama} />
                        </div>
                        <div className="grid gap-1">
                            <Label>No Polisi</Label>
                            <Input value={createForm.data.no_polisi} onChange={(e) => createForm.setData('no_polisi', e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Merk</Label>
                            <Input value={createForm.data.merk} onChange={(e) => createForm.setData('merk', e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Model</Label>
                            <Input value={createForm.data.model} onChange={(e) => createForm.setData('model', e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Tahun</Label>
                            <Input value={createForm.data.tahun} onChange={(e) => createForm.setData('tahun', e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <Label>Status</Label>
                            <select
                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                value={createForm.data.status}
                                onChange={(e) => createForm.setData('status', e.target.value)}
                            >
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div className="flex gap-2 sm:col-span-2">
                            <Button type="submit" disabled={createForm.processing}>
                                Simpan
                            </Button>
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
                                    className="grid gap-3 sm:grid-cols-2"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        editForm.put(`/driver/kendaraan/${row.id}`, {
                                            onSuccess: () => setEditingId(null),
                                        });
                                    }}
                                >
                                    <div className="grid gap-1 sm:col-span-2">
                                        <Label>Nama</Label>
                                        <Input value={editForm.data.nama} onChange={(e) => editForm.setData('nama', e.target.value)} />
                                    </div>
                                    <div className="grid gap-1">
                                        <Label>No Polisi</Label>
                                        <Input value={editForm.data.no_polisi} onChange={(e) => editForm.setData('no_polisi', e.target.value)} />
                                    </div>
                                    <div className="grid gap-1">
                                        <Label>Status</Label>
                                        <select
                                            className="h-9 rounded-md border bg-background px-3 text-sm"
                                            value={editForm.data.status}
                                            onChange={(e) => editForm.setData('status', e.target.value)}
                                        >
                                            <option value="aktif">Aktif</option>
                                            <option value="nonaktif">Nonaktif</option>
                                        </select>
                                    </div>
                                    <div className="flex gap-2 sm:col-span-2">
                                        <Button type="submit">Update</Button>
                                        <Button type="button" variant="outline" onClick={() => setEditingId(null)}>
                                            Batal
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div className="font-medium">{row.nama}</div>
                                        <div className="text-sm text-muted-foreground">
                                            {[row.no_polisi, row.merk, row.model, row.tahun, row.status]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                setShowCreate(false);
                                                setEditingId(row.id);
                                                editForm.setData({
                                                    nama: row.nama,
                                                    no_polisi: row.no_polisi ?? '',
                                                    merk: row.merk ?? '',
                                                    model: row.model ?? '',
                                                    tahun: row.tahun ? String(row.tahun) : '',
                                                    status: row.status,
                                                });
                                            }}
                                        >
                                            Edit
                                        </Button>
                                        <Button size="sm" variant="outline" asChild>
                                            <a href={`/driver/kendaraan/${row.id}/item`}>Item berlaku</a>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
