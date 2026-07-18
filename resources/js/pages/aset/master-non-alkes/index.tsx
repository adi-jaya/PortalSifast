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
    id_alat: string;
    nama_alat: string;
    kode: string | null;
    level: number;
    sinonim: string | null;
    parent_nama: string | null;
    is_leaf: boolean;
};

type Paginated = {
    data: Row[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string; only_leaf?: boolean; level?: string };
    stats: { total: number; leaf: number };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Katalog Non-Alkes', href: '/aset/master/non-alkes' },
];

export default function MasterNonAlkesIndex({ items, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [onlyLeaf, setOnlyLeaf] = useState(Boolean(filters.only_leaf));
    const [level, setLevel] = useState(filters.level || '__all__');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/aset/master/non-alkes',
            {
                q: search || undefined,
                only_leaf: onlyLeaf ? 1 : undefined,
                level: level === '__all__' ? undefined : level,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Katalog Non-Alkes" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Katalog Non-Alkes</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Master nama barang portal. Hanya item <span className="font-medium text-foreground">leaf</span> yang
                            bisa dipilih saat tambah aset. Import: <code className="text-xs">php artisan aset:import-master file.csv --tipe=non_alkes</code>
                        </p>
                    </div>
                    <div className="flex gap-2 text-sm">
                        <Badge variant="secondary">{stats.total} total</Badge>
                        <Badge variant="outline">{stats.leaf} leaf</Badge>
                    </div>
                </div>

                <form onSubmit={applyFilters} className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end">
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Nama, kode, sinonim..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="w-full space-y-1.5 sm:w-36">
                        <Label>Level</Label>
                        <Select value={level} onValueChange={setLevel}>
                            <SelectTrigger>
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua</SelectItem>
                                <SelectItem value="1">1</SelectItem>
                                <SelectItem value="2">2</SelectItem>
                                <SelectItem value="3">3</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-center gap-2 pb-2 sm:pb-2.5">
                        <Checkbox
                            id="only_leaf"
                            checked={onlyLeaf}
                            onCheckedChange={(c) => setOnlyLeaf(Boolean(c))}
                        />
                        <Label htmlFor="only_leaf" className="cursor-pointer font-normal">
                            Hanya leaf
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
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Induk</th>
                                    <th className="px-4 py-3 font-medium">Level</th>
                                    <th className="px-4 py-3 font-medium">Tipe</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada data. Import CSV katalog terlebih dahulu.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.nama_alat}</div>
                                                {row.sinonim ? (
                                                    <div className="text-xs text-muted-foreground">{row.sinonim}</div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.parent_nama ?? '—'}</td>
                                            <td className="px-4 py-3">{row.level}</td>
                                            <td className="px-4 py-3">
                                                {row.is_leaf ? (
                                                    <Badge className="bg-teal-700 text-white hover:bg-teal-700">Leaf</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Folder</Badge>
                                                )}
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
