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
    ruang_count: number;
};

type Props = {
    rows: {
        data: Row[];
    };
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Patroli', href: '/patroli/checkin' },
    { title: 'Area & Ruang', href: '/patroli/area' },
];

export default function PatroliAreaIndex({ rows, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/patroli/area', { q: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Area Patroli" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Area & Ruang Patroli</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelompokkan ruang kecil per area, assign template, cetak QR
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/patroli/area/create">
                            <Plus className="mr-2 h-4 w-4" /> Area baru
                        </Link>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <div className="relative min-w-[200px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari area..."
                            className="pl-9"
                        />
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
                                <th className="p-3">Ruang</th>
                                <th className="p-3">Status</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="p-6 text-center text-muted-foreground">
                                        Belum ada area. Buat area lalu tambah ruang di dalamnya.
                                    </td>
                                </tr>
                            ) : (
                                rows.data.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="p-3">
                                            <div className="font-medium">{row.nama}</div>
                                            {row.deskripsi && (
                                                <div className="text-muted-foreground">{row.deskripsi}</div>
                                            )}
                                        </td>
                                        <td className="p-3">{row.ruang_count}</td>
                                        <td className="p-3">{row.is_active ? 'Aktif' : 'Nonaktif'}</td>
                                        <td className="p-3 text-right">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={`/patroli/area/${row.id}`}>Kelola</Link>
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
