import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    checked_at: string | null;
    kode: string | null;
    nama_ruang: string | null;
    nama_area: string | null;
    petugas: string | null;
    template: string | null;
    jumlah_item: number;
    jumlah_tidak_berfungsi: number;
};

type Props = {
    rows: {
        data: Row[];
    };
    filters: { q?: string; temuan?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Check-in Patroli', href: '/patroli/checkin' },
];

export default function PatroliCheckinIndex({ rows, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [temuan, setTemuan] = useState(filters.temuan ?? 'all');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/patroli/checkin',
            { q: q || undefined, temuan: temuan === 'all' ? undefined : temuan },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Check-in Patroli" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Check-in Patroli</h1>
                        <p className="text-sm text-muted-foreground">Riwayat scan QR ruang + checklist</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/patroli/area">Ke area / QR</Link>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <div className="relative min-w-[200px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari area / ruang / petugas..."
                            className="pl-9"
                        />
                    </div>
                    <Select value={temuan} onValueChange={setTemuan}>
                        <SelectTrigger className="w-[200px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua</SelectItem>
                            <SelectItem value="ya">Ada temuan</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Waktu</th>
                                <th className="p-3">Lokasi</th>
                                <th className="p-3">Petugas</th>
                                <th className="p-3">Template</th>
                                <th className="p-3">Temuan</th>
                                <th className="p-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="p-6 text-center text-muted-foreground">
                                        Belum ada check-in.
                                    </td>
                                </tr>
                            ) : (
                                rows.data.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="p-3 whitespace-nowrap">{row.checked_at}</td>
                                        <td className="p-3">
                                            <div className="font-medium">{row.nama_ruang}</div>
                                            <div className="text-muted-foreground">
                                                {row.nama_area}
                                                {row.kode ? ` · ${row.kode}` : ''}
                                            </div>
                                        </td>
                                        <td className="p-3">{row.petugas}</td>
                                        <td className="p-3">{row.template}</td>
                                        <td className="p-3">
                                            {row.jumlah_tidak_berfungsi > 0 ? (
                                                <Badge variant="destructive">{row.jumlah_tidak_berfungsi} item</Badge>
                                            ) : (
                                                <span className="text-muted-foreground">OK</span>
                                            )}
                                        </td>
                                        <td className="p-3 text-right">
                                            <Button asChild size="sm" variant="outline">
                                                <Link href={`/patroli/checkin/${row.id}`}>Detail</Link>
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
