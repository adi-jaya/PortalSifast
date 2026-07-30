import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Area & Ruang', href: '/patroli/area' },
    { title: 'Baru', href: '/patroli/area/create' },
];

export default function PatroliAreaCreate() {
    const { data, setData, post, processing, errors } = useForm({
        nama: '',
        deskripsi: '',
        is_active: true,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Area Patroli Baru" />
            <form
                className="mx-auto flex max-w-lg flex-col gap-4 p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/patroli/area');
                }}
            >
                <div>
                    <h1 className="text-xl font-semibold">Area baru</h1>
                    <p className="text-sm text-muted-foreground">
                        Contoh: Poli Klinik Lantai 1 Depan, Poli Eksekutif
                    </p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="nama">Nama area</Label>
                    <Input id="nama" value={data.nama} onChange={(e) => setData('nama', e.target.value)} />
                    <InputError message={errors.nama} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="deskripsi">Deskripsi (opsional)</Label>
                    <Textarea
                        id="deskripsi"
                        value={data.deskripsi}
                        onChange={(e) => setData('deskripsi', e.target.value)}
                    />
                    <InputError message={errors.deskripsi} />
                </div>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                    <Button asChild type="button" variant="outline">
                        <Link href="/patroli/area">Batal</Link>
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
