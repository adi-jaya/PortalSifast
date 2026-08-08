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
    { title: 'Master Jenis', href: '/inventaris-jenis' },
    { title: 'Tambah', href: '/inventaris-jenis/create' },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        id_jenis: '',
        nama_jenis: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Jenis" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-jenis">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Tambah Jenis" description="Master data inventaris" />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/inventaris-jenis');
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="id_jenis">ID Jenis</Label>
                        <Input
                            id="id_jenis"
                            value={data.id_jenis}
                            onChange={(e) => setData('id_jenis', e.target.value)}
                            maxLength={10}
                            required
                        />
                        <InputError message={errors.id_jenis} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="nama_jenis">Nama Jenis</Label>
                        <Input
                            id="nama_jenis"
                            value={data.nama_jenis}
                            onChange={(e) => setData('nama_jenis', e.target.value)}
                            maxLength={40}
                            required
                        />
                        <InputError message={errors.nama_jenis} />
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inventaris-jenis">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
