import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { MasterCsvActions } from '@/components/aset/master-csv-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    kode_ruang: string;
    nama_ruang: string;
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

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset/master/ruang', { q: search || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Ruang" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Master Ruang</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kode ruang dipakai di form tambah aset dan import unit CSV (`kode_ruang`).
                        </p>
                    </div>
                    <Badge variant="secondary">{stats.total} ruang</Badge>
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

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 font-medium">Kode</th>
                                <th className="px-4 py-3 font-medium">Nama ruang</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={2} className="px-4 py-8 text-center text-muted-foreground">
                                        Belum ada ruang. Import CSV atau tambah lewat data existing.
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((row) => (
                                    <tr key={row.id} className="border-b border-border/60">
                                        <td className="px-4 py-3 font-mono text-xs">{row.kode_ruang}</td>
                                        <td className="px-4 py-3">{row.nama_ruang}</td>
                                    </tr>
                                ))
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
