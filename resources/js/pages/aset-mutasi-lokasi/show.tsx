import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin, Package, Printer, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mutasi: {
        id: number;
        nomor: string;
        tanggal_mutasi: string | null;
        catatan: string | null;
        penerima_label: string | null;
        penerima_nik: string | null;
        dicatat_oleh: string | null;
        ruang_asal: string | null;
        ruang_tujuan: string | null;
        aset: {
            id: number | null;
            kode_aset: string | null;
            nama_barang: string | null;
        };
    };
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '–';
    }

    return value.replace('T', ' ').slice(0, 16);
}

export default function AsetMutasiLokasiShow({ mutasi }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Mutasi Lokasi', href: '/aset-mutasi-lokasi' },
        { title: mutasi.nomor || 'Detail', href: `/aset-mutasi-lokasi/${mutasi.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mutasi.nomor || 'Mutasi'} />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/aset-mutasi-lokasi">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Bukti mutasi lokasi
                            </p>
                            <h1 className="font-mono text-2xl font-semibold tracking-tight">
                                {mutasi.nomor || '–'}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {formatDateTime(mutasi.tanggal_mutasi)}
                            </p>
                        </div>
                    </div>
                    {mutasi.id ? (
                        <Button variant="outline" asChild>
                            <a href={`/aset-mutasi-lokasi/${mutasi.id}/print`} target="_blank" rel="noreferrer">
                                <Printer className="mr-2 h-4 w-4" /> Cetak bukti
                            </a>
                        </Button>
                    ) : null}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4" /> Aset
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {mutasi.aset?.kode_aset ? (
                                <>
                                    <Link
                                        href={`/aset/${mutasi.aset.kode_aset}`}
                                        className="font-mono text-lg font-medium text-primary hover:underline"
                                    >
                                        {mutasi.aset.kode_aset}
                                    </Link>
                                    <p className="text-muted-foreground">{mutasi.aset.nama_barang ?? '–'}</p>
                                </>
                            ) : (
                                <p className="text-muted-foreground">Data aset tidak tersedia.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <User className="h-4 w-4" /> Pihak
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Field label="Penerima" value={mutasi.penerima_label} />
                            {mutasi.penerima_nik && <Field label="NIK" value={mutasi.penerima_nik} mono />}
                            <Field label="Dicatat oleh" value={mutasi.dicatat_oleh} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <MapPin className="h-4 w-4" /> Perpindahan ruang
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2 text-sm">
                        <Field label="Ruang asal" value={mutasi.ruang_asal} />
                        <Field label="Ruang tujuan" value={mutasi.ruang_tujuan} />
                        <div className="sm:col-span-2">
                            <Field label="Catatan" value={mutasi.catatan} />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function Field({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string | null | undefined;
    mono?: boolean;
}) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className={mono ? 'font-mono text-sm' : 'font-medium'}>{value?.trim() ? value : '–'}</p>
        </div>
    );
}
