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
    { title: 'Master Ruang', href: '/inventaris-ruang' },
    { title: 'Tambah', href: '/inventaris-ruang/create' },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        id_ruang: '',
        nama_ruang: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Ruang" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-ruang">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Tambah Ruang" description="Master data inventaris" />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/inventaris-ruang');
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="id_ruang">ID Ruang</Label>
                        <Input
                            id="id_ruang"
                            value={data.id_ruang}
                            onChange={(e) => setData('id_ruang', e.target.value)}
                            maxLength={5}
                            required
                        />
                        <InputError message={errors.id_ruang} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="nama_ruang">Nama Ruang</Label>
                        <Input
                            id="nama_ruang"
                            value={data.nama_ruang}
                            onChange={(e) => setData('nama_ruang', e.target.value)}
                            maxLength={40}
                            required
                        />
                        <InputError message={errors.nama_ruang} />
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inventaris-ruang">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
