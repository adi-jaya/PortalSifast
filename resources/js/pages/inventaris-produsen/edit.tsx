import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Item = {
    kode_produsen: string;
    nama_produsen: string;
    alamat_produsen: string | null;
    no_telp: string | null;
    email: string | null;
    website_produsen: string | null;
};

type Props = { item: Item };

export default function InventarisProdusenEdit({ item }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Master Produsen', href: '/inventaris-produsen' },
        { title: 'Edit', href: `/inventaris-produsen/${item.kode_produsen}/edit` },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        nama_produsen: item.nama_produsen ?? '',
        alamat_produsen: item.alamat_produsen ?? '',
        no_telp: item.no_telp ?? '',
        email: item.email ?? '',
        website_produsen: item.website_produsen ?? '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Produsen" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-produsen">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Edit Produsen" description={item.kode_produsen} />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        patch(`/inventaris-produsen/${item.kode_produsen}`);
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label>Kode Produsen</Label>
                        <p className="font-mono text-sm text-muted-foreground">
                            {item.kode_produsen} (tidak dapat diubah)
                        </p>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="nama_produsen">Nama Produsen</Label>
                        <Input
                            id="nama_produsen"
                            value={data.nama_produsen}
                            onChange={(e) => setData('nama_produsen', e.target.value)}
                            maxLength={40}
                            required
                        />
                        <InputError message={errors.nama_produsen} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="alamat_produsen">Alamat</Label>
                        <Input
                            id="alamat_produsen"
                            value={data.alamat_produsen}
                            onChange={(e) => setData('alamat_produsen', e.target.value)}
                            maxLength={70}
                        />
                        <InputError message={errors.alamat_produsen} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="no_telp">Telepon</Label>
                        <Input
                            id="no_telp"
                            value={data.no_telp}
                            onChange={(e) => setData('no_telp', e.target.value)}
                            maxLength={13}
                        />
                        <InputError message={errors.no_telp} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            maxLength={25}
                        />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="website_produsen">Website</Label>
                        <Input
                            id="website_produsen"
                            value={data.website_produsen}
                            onChange={(e) => setData('website_produsen', e.target.value)}
                            maxLength={30}
                        />
                        <InputError message={errors.website_produsen} />
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inventaris-produsen">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
