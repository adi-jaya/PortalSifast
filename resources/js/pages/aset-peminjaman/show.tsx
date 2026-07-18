import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, MapPin, Package, Printer, RotateCcw, User } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    peminjaman: {
        id: number;
        nomor: string;
        status: string;
        is_terlambat: boolean;
        tanggal_pinjam: string | null;
        tanggal_kembali_rencana: string | null;
        tanggal_kembali_aktual: string | null;
        catatan: string | null;
        kondisi_kembali: string | null;
        peminjam_label: string | null;
        peminjam_nik: string | null;
        diserahkan_oleh: string | null;
        diterima_kembali_oleh: string | null;
        aset: {
            id: number | null;
            kode_aset: string | null;
            nama_barang: string | null;
            nama_ruang: string | null;
        };
    };
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '–';
    }

    return value.replace('T', ' ').slice(0, 16);
}

export default function AsetPeminjamanShow({ peminjaman }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Peminjaman Aset', href: '/aset-peminjaman' },
        { title: peminjaman.nomor || 'Detail', href: `/aset-peminjaman/${peminjaman.id}` },
    ];

    const [showReturn, setShowReturn] = useState(false);
    const { data, setData, post, processing } = useForm({ kondisi_kembali: '' });

    const kembalikan = (e: FormEvent) => {
        e.preventDefault();
        post(`/aset-peminjaman/${peminjaman.id}/kembalikan`);
    };

    const statusLabel = peminjaman.is_terlambat ? 'Terlambat' : peminjaman.status;
    const statusVariant = peminjaman.is_terlambat
        ? 'destructive'
        : peminjaman.status === 'dikembalikan'
          ? 'outline'
          : 'secondary';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={peminjaman.nomor || 'Peminjaman'} />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/aset-peminjaman">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Bukti peminjaman
                            </p>
                            <h1 className="font-mono text-2xl font-semibold tracking-tight">
                                {peminjaman.nomor || '–'}
                            </h1>
                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                <Badge variant={statusVariant} className="capitalize">
                                    {statusLabel}
                                </Badge>
                                {peminjaman.tanggal_kembali_rencana && (
                                    <span className="text-xs text-muted-foreground">
                                        Rencana kembali {peminjaman.tanggal_kembali_rencana}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {peminjaman.id ? (
                            <Button variant="outline" asChild>
                                <a
                                    href={`/aset-peminjaman/${peminjaman.id}/print`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Printer className="mr-2 h-4 w-4" /> Cetak bukti
                                </a>
                            </Button>
                        ) : null}
                        {peminjaman.status === 'dipinjam' && peminjaman.id ? (
                            <Button type="button" onClick={() => setShowReturn(true)}>
                                <RotateCcw className="mr-2 h-4 w-4" /> Kembalikan
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4" /> Aset
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {peminjaman.aset?.kode_aset ? (
                                <>
                                    <div>
                                        <Link
                                            href={`/aset/${peminjaman.aset.kode_aset}`}
                                            className="font-mono text-lg font-medium text-primary hover:underline"
                                        >
                                            {peminjaman.aset.kode_aset}
                                        </Link>
                                        <p className="mt-1 text-muted-foreground">
                                            {peminjaman.aset.nama_barang ?? '–'}
                                        </p>
                                    </div>
                                    {peminjaman.aset.nama_ruang && (
                                        <p className="flex items-center gap-1.5 text-muted-foreground">
                                            <MapPin className="h-3.5 w-3.5" />
                                            {peminjaman.aset.nama_ruang}
                                        </p>
                                    )}
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
                            <Field label="Peminjam" value={peminjaman.peminjam_label} />
                            {peminjaman.peminjam_nik && (
                                <Field label="NIK" value={peminjaman.peminjam_nik} mono />
                            )}
                            <Field label="Diserahkan oleh" value={peminjaman.diserahkan_oleh} />
                            <Field label="Diterima kembali" value={peminjaman.diterima_kembali_oleh} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Jadwal & catatan</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                        <Field label="Tanggal pinjam" value={formatDateTime(peminjaman.tanggal_pinjam)} />
                        <Field label="Rencana kembali" value={peminjaman.tanggal_kembali_rencana} />
                        <Field label="Kembali aktual" value={formatDateTime(peminjaman.tanggal_kembali_aktual)} />
                        <Field label="Kondisi kembali" value={peminjaman.kondisi_kembali} />
                        <div className="sm:col-span-2">
                            <Field label="Catatan" value={peminjaman.catatan} />
                        </div>
                    </CardContent>
                </Card>

                {showReturn && peminjaman.status === 'dipinjam' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Konfirmasi pengembalian</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={kembalikan} className="flex max-w-lg flex-col gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="kondisi_kembali">Kondisi kembali (opsional)</Label>
                                    <Input
                                        id="kondisi_kembali"
                                        value={data.kondisi_kembali}
                                        onChange={(e) => setData('kondisi_kembali', e.target.value)}
                                        placeholder="Mis. Baik / ada goresan ringan"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Konfirmasi kembalikan
                                    </Button>
                                    <Button type="button" variant="ghost" onClick={() => setShowReturn(false)}>
                                        Batal
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
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
