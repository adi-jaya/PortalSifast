import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Detail = {
    id: number;
    nama_item: string | null;
    hasil: string;
    temuan: string | null;
    rekomendasi: string | null;
    keterangan: string | null;
};

type Props = {
    pemeriksaan: {
        id: number;
        tanggal: string;
        waktu: string;
        pemeriksaan_ke: number;
        status: string;
        catatan: string | null;
        kendaraan: { id: number; nama: string; no_polisi: string | null };
        petugas: string | null;
        details: Detail[];
    };
    canCancel: boolean;
};

function labelHasil(hasil: string): string {
    if (hasil === 'layak') {
        return 'Baik';
    }
    if (hasil === 'tidak_layak') {
        return 'Tidak Baik';
    }
    if (hasil === 'na') {
        return 'Tidak berlaku';
    }

    return hasil.replaceAll('_', ' ');
}

export default function DriverPemeriksaanShow({ pemeriksaan, canCancel }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Riwayat', href: '/driver/riwayat' },
        { title: `Pemeriksaan #${pemeriksaan.id}`, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pemeriksaan ${pemeriksaan.kendaraan.nama}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{pemeriksaan.kendaraan.nama}</h1>
                    <p className="text-sm text-muted-foreground">
                        {pemeriksaan.tanggal} · {pemeriksaan.waktu} · Ke-{pemeriksaan.pemeriksaan_ke} ·{' '}
                        {pemeriksaan.status}
                    </p>
                    <p className="text-sm text-muted-foreground">Petugas: {pemeriksaan.petugas}</p>
                </div>

                <div className="grid gap-3">
                    {pemeriksaan.details.map((detail) => (
                        <div key={detail.id} className="rounded-lg border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="font-medium">{detail.nama_item}</div>
                                <span className="text-sm uppercase tracking-wide">{labelHasil(detail.hasil)}</span>
                            </div>
                            {detail.hasil === 'tidak_layak' && (
                                <div className="mt-2 space-y-1 text-sm">
                                    <p>
                                        <span className="font-medium">Temuan:</span> {detail.temuan}
                                    </p>
                                    <p>
                                        <span className="font-medium">Rekomendasi:</span> {detail.rekomendasi}
                                    </p>
                                    {detail.keterangan && (
                                        <p>
                                            <span className="font-medium">Keterangan:</span> {detail.keterangan}
                                        </p>
                                    )}
                                </div>
                            )}
                            {detail.hasil === 'na' && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {detail.keterangan ?? 'Tidak berlaku'}
                                </p>
                            )}
                        </div>
                    ))}
                </div>

                {pemeriksaan.catatan && (
                    <div className="rounded-md border p-3 text-sm">
                        <span className="font-medium">Catatan:</span> {pemeriksaan.catatan}
                    </div>
                )}

                {canCancel && (
                    <Button
                        variant="destructive"
                        onClick={() => {
                            if (confirm('Batalkan pemeriksaan ini?')) {
                                router.delete(`/driver/pemeriksaan/${pemeriksaan.id}`);
                            }
                        }}
                    >
                        Batalkan Pemeriksaan
                    </Button>
                )}
            </div>
        </AppLayout>
    );
}
