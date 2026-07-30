import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { MasterCsvActions } from '@/components/aset/master-csv-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    id_alat_aspak: string;
    nama_alat: string;
    kode: string | null;
    sinonim: string | null;
    parent_nama: string | null;
    wajib_kalibrasi: boolean;
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
    filters: { q?: string; only_leaf?: boolean };
    stats: { total: number; leaf: number };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Katalog ASPAK', href: '/aset/master/aspak' },
];

export default function MasterAspakIndex({ items, filters, stats }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string; error?: string };
    const [search, setSearch] = useState(filters.q ?? '');
    const [onlyLeaf, setOnlyLeaf] = useState(Boolean(filters.only_leaf));

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/aset/master/aspak',
            {
                q: search || undefined,
                only_leaf: onlyLeaf ? 1 : undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Katalog ASPAK" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Katalog ASPAK</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Nomenklatur alat medis. Hanya item <span className="font-medium text-foreground">leaf</span> yang
                            bisa dipilih saat tambah aset medis. Kode leaf = kolom <code className="text-xs">kode_aspak</code>{' '}
                            di import unit.
                        </p>
                    </div>
                    <div className="flex gap-2 text-sm">
                        <Badge variant="secondary">{stats.total} total</Badge>
                        <Badge variant="outline">{stats.leaf} leaf</Badge>
                    </div>
                </div>

                {(flash.success || flash.error) && (
                    <div
                        className={`rounded-lg border px-3 py-2 text-sm ${
                            flash.error
                                ? 'border-destructive/30 bg-destructive/10 text-destructive'
                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200'
                        }`}
                    >
                        {flash.error ?? flash.success}
                    </div>
                )}

                <MasterCsvActions tipe="aspak" />

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
                                placeholder="Nama, kode, sinonim..."
                                className="pl-9"
                            />
                        </div>
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
                                    <th className="px-4 py-3 font-medium">Kalibrasi</th>
                                    <th className="px-4 py-3 font-medium">Tipe</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada data. Import CSV ASPAK terlebih dahulu.
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
                                            <td className="px-4 py-3">
                                                {row.wajib_kalibrasi ? (
                                                    <Badge variant="outline">Wajib</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
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
