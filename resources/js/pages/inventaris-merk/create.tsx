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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Master Merk', href: '/inventaris-merk' },
    { title: 'Tambah', href: '/inventaris-merk/create' },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        id_merk: '',
        nama_merk: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Merk" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-merk">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Tambah Merk" description="Master data inventaris" />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/inventaris-merk');
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="id_merk">ID Merk</Label>
                        <Input
                            id="id_merk"
                            value={data.id_merk}
                            onChange={(e) => setData('id_merk', e.target.value)}
                            maxLength={10}
                            required
                        />
                        <InputError message={errors.id_merk} />
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
