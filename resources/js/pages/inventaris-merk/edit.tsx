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
    id_merk: string;
    nama_merk: string;
};

type Props = { item: Item };

export default function Edit({ item }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Master Merk', href: '/inventaris-merk' },
        { title: 'Edit', href: `/inventaris-merk/${item.id_merk}/edit` },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        nama_merk: item.nama_merk,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Merk" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-merk">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Edit Merk" description={item.id_merk} />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        patch(`/inventaris-merk/${item.id_merk}`);
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label>ID Merk</Label>
                        <p className="font-mono text-sm text-muted-foreground">{item.id_merk} (tidak dapat diubah)</p>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="nama_merk">Nama Merk</Label>
                        <Input
                            id="nama_merk"
                            value={data.nama_merk}
                            onChange={(e) => setData('nama_merk', e.target.value)}
                            maxLength={40}
                            required
                        />
                        <InputError message={errors.nama_merk} />
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inventaris-merk">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
