import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Package, Calendar, Tag, Building, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type BarangData = {
    kode_barang: string;
    nama_barang: string;
    jml_barang: number | null;
    kode_produsen: string | null;
    nama_produsen: string | null;
    alamat_produsen: string | null;
    no_telp_produsen: string | null;
    email_produsen: string | null;
    website_produsen: string | null;
    nama_merk: string | null;
    thn_produksi: number | null;
    isbn: string | null;
    nama_kategori: string | null;
    nama_jenis: string | null;
};

type UnitItem = {
    no_inventaris: string;
    status_barang: string | null;
    nama_ruang: string | null;
};

type Props = {
    barang: BarangData;
    units: UnitItem[];
};

export default function InventarisBarangShow({ barang, units }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Master Barang', href: '/inventaris-barang' },
        { title: barang.nama_barang, href: `/inventaris-barang/${barang.kode_barang}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Barang - ${barang.nama_barang}`} />

            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-barang">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <Heading
                            title={barang.nama_barang}
                            description={`Kode: ${barang.kode_barang}`}
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/inventaris/create?kode_barang=${barang.kode_barang}`}>
                                <Plus className="mr-2 h-4 w-4" />
                                Tambah Unit
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/inventaris-barang/${barang.kode_barang}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-4">
                        <div className="rounded-xl border bg-card p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <Package className="h-5 w-5" />
                                Informasi Barang
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Kode Barang</label>
                                    <p className="font-mono font-medium">{barang.kode_barang}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Nama Barang</label>
                                    <p className="font-medium">{barang.nama_barang}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Jumlah</label>
                                    <p className="font-medium">{barang.jml_barang ?? '–'}</p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border bg-card p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <Building className="h-5 w-5" />
                                Produsen & Merk
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Produsen</label>
                                    <p className="font-medium">
                                        {barang.kode_produsen ? (
                                            <Link
                                                href={`/inventaris-produsen/${barang.kode_produsen}/edit`}
                                                className="text-primary hover:underline"
                                            >
                                                {barang.nama_produsen ?? barang.kode_produsen}
                                            </Link>
                                        ) : (
                                            '–'
                                        )}
                                    </p>
                                </div>
                                {barang.alamat_produsen && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Alamat</label>
                                        <p className="font-medium">{barang.alamat_produsen}</p>
                                    </div>
                                )}
                                {barang.no_telp_produsen && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Telepon</label>
                                        <p className="font-medium">{barang.no_telp_produsen}</p>
                                    </div>
                                )}
                                {barang.email_produsen && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Email</label>
                                        <p className="font-medium">{barang.email_produsen}</p>
                                    </div>
                                )}
                                {barang.website_produsen && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Website</label>
                                        <p className="font-medium">{barang.website_produsen}</p>
                                    </div>
                                )}
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Merk</label>
                                    <p className="font-medium">{barang.nama_merk ?? '–'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border bg-card p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <Calendar className="h-5 w-5" />
                                Produksi & Identifikasi
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Tahun Produksi</label>
                                    <p className="font-medium">{barang.thn_produksi ?? '–'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">ISBN</label>
                                    <p className="font-mono font-medium">{barang.isbn ?? '–'}</p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border bg-card p-6">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <Tag className="h-5 w-5" />
                                Kategori & Jenis
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Kategori</label>
                                    <p className="font-medium">{barang.nama_kategori ?? '–'}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Jenis</label>
                                    <p className="font-medium">{barang.nama_jenis ?? '–'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-6">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <h3 className="text-lg font-semibold">Unit Inventaris</h3>
                        <Button size="sm" variant="outline" asChild>
                            <Link href={`/inventaris?q=${barang.kode_barang}`}>Lihat di Inventaris</Link>
                        </Button>
                    </div>
                    {units.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada unit inventaris untuk barang ini.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-2 py-2 font-medium">No Inventaris</th>
                                        <th className="px-2 py-2 font-medium">Status</th>
                                        <th className="px-2 py-2 font-medium">Ruang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {units.map((unit) => (
                                        <tr key={unit.no_inventaris} className="border-b last:border-0">
                                            <td className="px-2 py-2">
                                                <Link
                                                    href={`/inventaris/${unit.no_inventaris}`}
                                                    className="font-mono text-primary hover:underline"
                                                >
                                                    {unit.no_inventaris}
                                                </Link>
                                            </td>
                                            <td className="px-2 py-2 text-muted-foreground">
                                                {unit.status_barang ?? '–'}
                                            </td>
                                            <td className="px-2 py-2 text-muted-foreground">
                                                {unit.nama_ruang ?? '–'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
