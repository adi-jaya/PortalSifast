import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Package, Ticket, ImagePlus, Trash2, QrCode } from 'lucide-react';
import { FormEvent, useRef } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type InventarisData = {
    no_inventaris: string;
    kode_barang: string;
    nama_barang: string;
    asal_barang: string | null;
    tgl_pengadaan: string | null;
    harga: number | null;
    status_barang: string | null;
    nama_ruang: string | null;
    no_rak: string | null;
    no_box: string | null;
    photo: string | null;
    photo_url: string | null;
};

type TicketItem = {
    id: number;
    ticket_number: string;
    title: string;
    status: string | null;
    status_color: string | null;
    created_at: string | null;
};

type Props = {
    inventaris: InventarisData;
    tickets: TicketItem[];
    statusOptions: string[];
};

function formatDate(s: string | null): string {
    if (!s) return '–';
    return new Date(s).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatCurrency(n: number | null): string {
    if (n == null) return '–';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n);
}

export default function InventarisShow({ inventaris, tickets, statusOptions }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{ photo: File | null }>({
        photo: null,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Inventaris', href: '/inventaris' },
        { title: inventaris.no_inventaris, href: `/inventaris/${inventaris.no_inventaris}` },
    ];

    const handlePhotoSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(`/inventaris/${inventaris.no_inventaris}/gambar`, {
            forceFormData: true,
            onSuccess: () => {
                reset();
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    };

    const updateStatus = (statusBarang: string) => {
        router.patch(
            `/inventaris/${encodeURIComponent(inventaris.no_inventaris)}/status`,
            { status_barang: statusBarang },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Inventaris ${inventaris.no_inventaris}`} />

            <div className="flex flex-col gap-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/inventaris">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <Heading
                            title={inventaris.no_inventaris}
                            description={inventaris.nama_barang}
                            variant="small"
                        />
                    </div>
                    <div className="hidden flex-wrap gap-2 sm:flex">
                        <Button variant="outline" asChild>
                            <a
                                href={`/inventaris/${inventaris.no_inventaris}/label-print`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <QrCode className="mr-2 h-4 w-4" />
                                Cetak Label QR
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={`/inventaris/${inventaris.no_inventaris}/edit`}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:hidden">
                    <Select
                        value={inventaris.status_barang || undefined}
                        onValueChange={updateStatus}
                    >
                        <SelectTrigger className="h-12 col-span-2">
                            <SelectValue placeholder="Ubah status" />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((opt) => (
                                <SelectItem key={opt} value={opt}>
                                    {opt}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button className="h-12" variant="outline" asChild>
                        <Link
                            href={`/tickets/create?asset_no_inventaris=${encodeURIComponent(inventaris.no_inventaris)}`}
                        >
                            <Ticket className="mr-2 h-4 w-4" />
                            Buat tiket
                        </Link>
                    </Button>
                    <Button className="h-12" variant="outline" asChild>
                        <a
                            href={`/inventaris/${inventaris.no_inventaris}/label-print?autoprint=1`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <QrCode className="mr-2 h-4 w-4" />
                            Cetak label
                        </a>
                    </Button>
                    <Button className="h-12 col-span-2" asChild>
                        <Link href={`/inventaris/${inventaris.no_inventaris}/edit`}>
                            <Pencil className="mr-2 h-4 w-4" />
                            Edit aset
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4" />
                                Detail Inventaris
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-sm text-muted-foreground">No Inventaris</p>
                                    <p className="font-mono font-medium">{inventaris.no_inventaris}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Kode Barang</p>
                                    <Link
                                        href={`/inventaris-barang/${inventaris.kode_barang}`}
                                        className="font-mono text-primary hover:underline"
                                    >
                                        {inventaris.kode_barang}
                                    </Link>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Nama Barang</p>
                                    <p>{inventaris.nama_barang}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Ruang</p>
                                    <p>{inventaris.nama_ruang ?? '–'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Asal Barang</p>
                                    <p>{inventaris.asal_barang ?? '–'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Tanggal Pengadaan</p>
                                    <p>{formatDate(inventaris.tgl_pengadaan)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Harga</p>
                                    <p>{formatCurrency(inventaris.harga)}</p>
                                </div>
                                <div className="space-y-2">
                                    <p className="text-sm text-muted-foreground">Status Barang</p>
                                    <Select
                                        value={inventaris.status_barang || undefined}
                                        onValueChange={updateStatus}
                                    >
                                        <SelectTrigger className="w-full sm:max-w-xs">
                                            <SelectValue placeholder="Pilih status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statusOptions.map((opt) => (
                                                <SelectItem key={opt} value={opt}>
                                                    {opt}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">No Rak</p>
                                    <p>{inventaris.no_rak ?? '–'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">No Box</p>
                                    <p>{inventaris.no_box ?? '–'}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ImagePlus className="h-4 w-4" />
                                Foto Aset
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {inventaris.photo_url ? (
                                <div className="space-y-3">
                                    <img
                                        src={inventaris.photo_url}
                                        alt={`Foto ${inventaris.no_inventaris}`}
                                        className="max-h-56 w-full rounded-lg border object-contain"
                                    />
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => {
                                            if (confirm('Hapus foto inventaris ini?')) {
                                                router.delete(
                                                    `/inventaris/${inventaris.no_inventaris}/gambar`,
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Hapus Foto
                                    </Button>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">Belum ada foto.</p>
                            )}

                            <form onSubmit={handlePhotoSubmit} className="space-y-3">
                                <Input
                                    ref={fileRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setData('photo', e.target.files?.[0] ?? null)}
                                />
                                <InputError message={errors.photo} />
                                <Button type="submit" disabled={processing || !data.photo} className="w-full">
                                    {processing ? 'Mengunggah...' : inventaris.photo_url ? 'Ganti Foto' : 'Unggah Foto'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Ticket className="h-4 w-4" />
                            Tiket Terkait
                        </CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/tickets/create?asset_no_inventaris=${encodeURIComponent(inventaris.no_inventaris)}`}
                            >
                                Buat tiket
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {tickets.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada tiket yang menautkan inventaris ini.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="px-2 py-2 font-medium">No Tiket</th>
                                            <th className="px-2 py-2 font-medium">Judul</th>
                                            <th className="px-2 py-2 font-medium">Status</th>
                                            <th className="px-2 py-2 font-medium">Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tickets.map((ticket) => (
                                            <tr key={ticket.id} className="border-b last:border-0">
                                                <td className="px-2 py-2">
                                                    <Link
                                                        href={`/tickets/${ticket.id}`}
                                                        className="font-mono text-primary hover:underline"
                                                    >
                                                        {ticket.ticket_number}
                                                    </Link>
                                                </td>
                                                <td className="px-2 py-2">{ticket.title}</td>
                                                <td className="px-2 py-2 text-muted-foreground">
                                                    {ticket.status ?? '–'}
                                                </td>
                                                <td className="px-2 py-2 text-muted-foreground">
                                                    {formatDate(ticket.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
