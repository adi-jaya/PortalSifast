import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    nama: string;
    deskripsi: string | null;
    is_active: boolean;
    items_count: number;
    active_items_count: number;
    ruang_count: number;
};

type Props = {
    rows: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Patroli', href: '/patroli/checkin' },
    { title: 'Template', href: '/patroli/templates' },
];

export default function PatroliTemplatesIndex({ rows, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/patroli/templates', { q: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Template Patroli" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Template Checklist</h1>
                        <p className="text-sm text-muted-foreground">Paket item yang di-assign ke ruang</p>
                    </div>
                    <Button asChild>
                        <Link href="/patroli/templates/create">
                            <Plus className="mr-2 h-4 w-4" /> Template baru
                        </Link>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <div className="relative min-w-[200px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari template..." className="pl-9" />
                    </div>
                    <Button type="submit" variant="secondary">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Nama</th>
                                <th className="p-3">Item</th>
                                <th className="p-3">Ruang</th>
                                <th className="p-3">Status</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-muted-foreground">
                                        Belum ada template.
                                    </td>
                                </tr>
                            ) : (
                                rows.data.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="p-3 font-medium">{row.nama}</td>
                                        <td className="p-3">
                                            {row.active_items_count}/{row.items_count} aktif
                                        </td>
                                        <td className="p-3">{row.ruang_count}</td>
                                        <td className="p-3">{row.is_active ? 'Aktif' : 'Nonaktif'}</td>
                                        <td className="p-3 text-right">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={`/patroli/templates/${row.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
