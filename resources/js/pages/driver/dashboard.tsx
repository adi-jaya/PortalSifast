import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Card = {
    kendaraan_id: number;
    nama: string;
    foto_url?: string | null;
    check_1: boolean;
    check_2: boolean;
    temuan: number;
    status: string;
    jumlah_pemeriksaan: number;
    bisa_buat_baru: boolean;
};

type Props = {
    tanggal: string;
    summary: {
        total_kendaraan: number;
        belum: number;
        sudah: number;
        ada_temuan: number;
    };
    kendaraan: Card[];
    canCreate: boolean;
    maxPerDay: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Checklist Hari Ini', href: '/driver' },
];

function statusColor(status: string): string {
    if (status.includes('Temuan')) {
        return 'border-amber-400 bg-amber-50 text-amber-900';
    }
    if (status === 'Belum') {
        return 'border-red-300 bg-red-50 text-red-900';
    }
    return 'border-emerald-300 bg-emerald-50 text-emerald-900';
}

export default function DriverDashboard({ tanggal, summary, kendaraan, canCreate }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Checklist Hari Ini" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Checklist Hari Ini</h1>
                    <p className="text-sm text-muted-foreground">{tanggal}</p>
                </div>

                {flash.success && (
                    <div className="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                        {flash.success}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div className="rounded-md border p-3 text-sm">
                        <div className="text-muted-foreground">Kendaraan</div>
                        <div className="text-lg font-semibold">{summary.total_kendaraan}</div>
                    </div>
                    <div className="rounded-md border p-3 text-sm">
                        <div className="text-muted-foreground">Sudah</div>
                        <div className="text-lg font-semibold">{summary.sudah}</div>
                    </div>
                    <div className="rounded-md border p-3 text-sm">
                        <div className="text-muted-foreground">Belum</div>
                        <div className="text-lg font-semibold">{summary.belum}</div>
                    </div>
                    <div className="rounded-md border p-3 text-sm">
                        <div className="text-muted-foreground">Ada Temuan</div>
                        <div className="text-lg font-semibold">{summary.ada_temuan}</div>
                    </div>
                </div>

                <div className="grid gap-3">
                    {kendaraan.map((row) => (
                        <div key={row.kendaraan_id} className={`rounded-lg border p-4 ${statusColor(row.status)}`}>
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="flex items-start gap-3">
                                    {row.foto_url ? (
                                        <img
                                            src={row.foto_url}
                                            alt={row.nama}
                                            className="h-14 w-20 shrink-0 rounded-md border object-cover"
                                        />
                                    ) : null}
                                    <div>
                                        <div className="font-semibold">{row.nama}</div>
                                        <div className="text-sm opacity-80">{row.status}</div>
                                        <div className="text-xs opacity-70">
                                            {row.jumlah_pemeriksaan}× hari ini
                                            {row.temuan > 0 ? ` · ${row.temuan} temuan` : ''}
                                        </div>
                                    </div>
                                </div>
                                {canCreate && row.bisa_buat_baru && (
                                    <Button asChild size="sm">
                                        <Link href="/driver/pemeriksaan">Periksa</Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
