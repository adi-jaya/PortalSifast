import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    tanggal: string;
    waktu: string;
    pemeriksaan_ke: number;
    status: string;
    temuan_count: number;
    kendaraan: string | null;
    petugas: string | null;
};

type Paginated = {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { tanggal: string; kendaraan_id: string; status: string };
    kendaraanOptions: { id: number; nama: string }[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Riwayat', href: '/driver/riwayat' },
];

export default function DriverRiwayat({ items, filters, kendaraanOptions }: Props) {
    const [tanggal, setTanggal] = useState(filters.tanggal);
    const [kendaraanId, setKendaraanId] = useState(filters.kendaraan_id);
    const [status, setStatus] = useState(filters.status);

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/driver/riwayat',
            {
                tanggal: tanggal || undefined,
                kendaraan_id: kendaraanId || undefined,
                status: status || undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Riwayat Pemeriksaan" />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Riwayat Pemeriksaan</h1>

                <form onSubmit={apply} className="grid gap-3 rounded-lg border p-4 sm:grid-cols-4">
                    <div className="grid gap-1">
                        <Label>Tanggal</Label>
                        <Input type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
                    </div>
                    <div className="grid gap-1">
                        <Label>Kendaraan</Label>
                        <select
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                            value={kendaraanId}
                            onChange={(e) => setKendaraanId(e.target.value)}
                        >
                            <option value="">Semua</option>
                            {kendaraanOptions.map((k) => (
                                <option key={k.id} value={k.id}>
                                    {k.nama}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-1">
                        <Label>Status</Label>
                        <select
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                        >
                            <option value="">Semua</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    <div className="flex items-end">
                        <Button type="submit" className="w-full">
                            Filter
                        </Button>
                    </div>
                </form>

                <div className="grid gap-2">
                    {items.data.map((row) => (
                        <Link
                            key={row.id}
                            href={`/driver/pemeriksaan/${row.id}`}
                            className="rounded-lg border p-4 hover:bg-muted/40"
                        >
                            <div className="font-medium">
                                {row.kendaraan} · Ke-{row.pemeriksaan_ke}
                            </div>
                            <div className="text-sm text-muted-foreground">
                                {row.tanggal} {row.waktu} · {row.petugas} · {row.status}
                                {row.temuan_count > 0 ? ` · ${row.temuan_count} temuan` : ''}
                            </div>
                        </Link>
                    ))}
                    {items.data.length === 0 && (
                        <p className="text-sm text-muted-foreground">Belum ada data riwayat.</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
