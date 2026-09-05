import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    nama: string;
    no_polisi: string | null;
    merk: string | null;
    model: string | null;
    jumlah_hari_ini: number;
    bisa_buat_baru: boolean;
    pemeriksaan_ke_berikutnya: number;
};

type Props = {
    kendaraan: Row[];
    maxPerDay: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Pemeriksaan', href: '/driver/pemeriksaan' },
];

export default function DriverPemeriksaanIndex({ kendaraan, maxPerDay }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pemeriksaan Kendaraan" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Pemeriksaan</h1>
                    <p className="text-sm text-muted-foreground">
                        Maksimal {maxPerDay}× pemeriksaan per kendaraan per hari.
                    </p>
                </div>

                <div className="grid gap-3">
                    {kendaraan.map((row) => (
                        <div key={row.id} className="rounded-lg border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div className="font-medium">{row.nama}</div>
                                    <div className="text-sm text-muted-foreground">
                                        {[row.no_polisi, row.merk, row.model].filter(Boolean).join(' · ') || '—'}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Hari ini: {row.jumlah_hari_ini}×
                                    </div>
                                </div>
                                {row.bisa_buat_baru ? (
                                    <Button asChild>
                                        <Link href={`/driver/pemeriksaan/buat/${row.id}`}>
                                            Pemeriksaan ke-{row.pemeriksaan_ke_berikutnya}
                                        </Link>
                                    </Button>
                                ) : (
                                    <span className="text-sm text-muted-foreground">Batas tercapai</span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
