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
    nomor: string;
    tanggal_mutasi: string | null;
    kode_aset: string | null;
    nama_barang: string | null;
    ruang_asal: string | null;
    ruang_tujuan: string | null;
    penerima_label: string;
    dicatat_oleh: string | null;
};

type Props = {
    rows: { data: Row[] };
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Mutasi Lokasi', href: '/aset-mutasi-lokasi' },
];

export default function AsetMutasiLokasiIndex({ rows, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/aset-mutasi-lokasi', { q: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mutasi Lokasi Aset" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Mutasi Lokasi</h1>
                        <p className="text-sm text-muted-foreground">Pindah ruang aset unit</p>
                    </div>
                    <Button asChild>
                        <Link href="/aset-mutasi-lokasi/create">
                            <Plus className="mr-2 h-4 w-4" /> Mutasi
                        </Link>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari nomor / kode aset..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2 font-medium">Nomor</th>
                                <th className="px-3 py-2 font-medium">Aset</th>
                                <th className="px-3 py-2 font-medium">Asal → Tujuan</th>
                                <th className="px-3 py-2 font-medium">Penerima</th>
                                <th className="px-3 py-2 font-medium">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-3 py-8 text-center text-muted-foreground">
                                        Belum ada mutasi
                                    </td>
                                </tr>
                            )}
                            {rows.data.map((row) => (
                                <tr key={row.id} className="border-t hover:bg-muted/30">
                                    <td className="px-3 py-2">
                                        <Link
                                            href={`/aset-mutasi-lokasi/${row.id}`}
                                            className="font-mono text-primary hover:underline"
                                        >
                                            {row.nomor}
                                        </Link>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="font-mono text-xs">{row.kode_aset}</div>
                                        <div className="text-muted-foreground">{row.nama_barang}</div>
                                    </td>
                                    <td className="px-3 py-2">
                                        {row.ruang_asal} → {row.ruang_tujuan}
                                    </td>
                                    <td className="px-3 py-2">{row.penerima_label}</td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {row.tanggal_mutasi?.slice(0, 16) ?? '–'}
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
