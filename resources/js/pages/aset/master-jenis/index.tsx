import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    kode_jenis: string | null;
    nama_jenis: string;
    aset_merk_id: number | null;
    merk_nama: string | null;
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Master Jenis', href: '/aset/master/jenis' },
];

export default function MasterJenisIndex({ items, filters, stats, merkOptions }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [merkFilter, setMerkFilter] = useState(filters.merk_id || '__all__');
    const [onlyUnassigned, setOnlyUnassigned] = useState(Boolean(filters.only_unassigned));

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

    const updateMerk = (rowId: number, value: string) => {
        router.patch(
            `/aset/master/jenis/${rowId}/merk`,
            { aset_merk_id: value === '__none__' ? null : Number(value) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Jenis" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Jenis</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Hubungkan jenis ke merk agar form tambah aset hanya menampilkan jenis yang relevan.
                            Import:{' '}
                            <code className="text-xs">
                                php artisan aset:import-master file.csv --tipe=jenis
                            </code>{' '}
                            (kolom opsional <code className="text-xs">kode_merk</code>)
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 text-sm">
                        <Badge variant="secondary">{stats.total} total</Badge>
                        <Badge variant="outline">{stats.assigned} terhubung</Badge>
                        <Badge variant="destructive">{stats.unassigned} belum</Badge>
                    </div>
                </div>

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
                        <Select value={merkFilter} onValueChange={setMerkFilter}>
                            <SelectTrigger>
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua merk</SelectItem>
                                {merkOptions.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Kode</th>
                                    <th className="px-4 py-3 font-medium">Nama jenis</th>
                                    <th className="px-4 py-3 font-medium">Merk</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada data jenis. Import CSV atau buat dari form tambah aset.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode_jenis ?? '—'}</td>
                                            <td className="px-4 py-3 font-medium">{row.nama_jenis}</td>
                                            <td className="px-4 py-3">
                                                <Select
                                                    value={
                                                        row.aset_merk_id ? String(row.aset_merk_id) : '__none__'
                                                    }
                                                    onValueChange={(v) => updateMerk(row.id, v)}
                                                >
                                                    <SelectTrigger className="h-8 w-[200px]">
                                                        <SelectValue placeholder="Pilih merk" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="__none__">Belum diisi</SelectItem>
                                                        {merkOptions.map((m) => (
                                                            <SelectItem key={m.id} value={String(m.id)}>
                                                                {m.nama}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
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
