import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
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
    nomor: string;
    status: string;
    is_terlambat: boolean;
    tanggal_pinjam: string | null;
    tanggal_kembali_rencana: string | null;
    kode_aset: string | null;
    nama_barang: string | null;
    peminjam_label: string;
    diserahkan_oleh: string | null;
};

type Props = {
    rows: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status?: string; q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Peminjaman Aset', href: '/aset-peminjaman' },
];

export default function AsetPeminjamanIndex({ rows, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/aset-peminjaman',
            { q: q || undefined, status: status === 'all' ? undefined : status },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Peminjaman Aset" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Peminjaman Aset</h1>
                        <p className="text-sm text-muted-foreground">Catat pinjam & pengembalian unit aset</p>
                    </div>
                    <Button asChild>
                        <Link href="/aset-peminjaman/create">
                            <Plus className="mr-2 h-4 w-4" /> Pinjamkan
                        </Link>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <div className="relative min-w-[200px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari nomor / kode aset..."
                            className="pl-9"
                        />
                    </div>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua</SelectItem>
                            <SelectItem value="dipinjam">Dipinjam</SelectItem>
                            <SelectItem value="terlambat">Terlambat</SelectItem>
                            <SelectItem value="dikembalikan">Dikembalikan</SelectItem>
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
                                <th className="px-3 py-2 font-medium">Nomor</th>
                                <th className="px-3 py-2 font-medium">Aset</th>
                                <th className="px-3 py-2 font-medium">Peminjam</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                                <th className="px-3 py-2 font-medium">Pinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-3 py-8 text-center text-muted-foreground">
                                        Belum ada peminjaman
                                    </td>
                                </tr>
                            )}
                            {rows.data.map((row) => (
                                <tr key={row.id} className="border-t hover:bg-muted/30">
                                    <td className="px-3 py-2">
                                        <Link
                                            href={`/aset-peminjaman/${row.id}`}
                                            className="font-mono text-primary hover:underline"
                                        >
                                            {row.nomor}
                                        </Link>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="font-mono text-xs">{row.kode_aset}</div>
                                        <div className="text-muted-foreground">{row.nama_barang}</div>
                                    </td>
                                    <td className="px-3 py-2">{row.peminjam_label}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={row.is_terlambat ? 'destructive' : 'secondary'}>
                                            {row.is_terlambat ? 'terlambat' : row.status}
                                        </Badge>
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {row.tanggal_pinjam?.slice(0, 16) ?? '–'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
