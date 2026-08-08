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
    { title: 'Master Kategori', href: '/inventaris-kategori' },
    { title: 'Tambah', href: '/inventaris-kategori/create' },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        id_kategori: '',
        nama_kategori: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Kategori" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-kategori">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Tambah Kategori" description="Master data inventaris" />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/inventaris-kategori');
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="id_kategori">ID Kategori</Label>
                        <Input
                            id="id_kategori"
                            value={data.id_kategori}
                            onChange={(e) => setData('id_kategori', e.target.value)}
                            maxLength={10}
                            required
                        />
                        <InputError message={errors.id_kategori} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="nama_kategori">Nama Kategori</Label>
                        <Input
                            id="nama_kategori"
                            value={data.nama_kategori}
                            onChange={(e) => setData('nama_kategori', e.target.value)}
                            maxLength={40}
                            required
                        />
                        <InputError message={errors.nama_kategori} />
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inventaris-kategori">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
