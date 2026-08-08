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
    id_ruang: string;
    nama_ruang: string;
};

type Props = { item: Item };

export default function Edit({ item }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Master Ruang', href: '/inventaris-ruang' },
        { title: 'Edit', href: `/inventaris-ruang/${item.id_ruang}/edit` },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        nama_ruang: item.nama_ruang,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Ruang" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/inventaris-ruang">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading title="Edit Ruang" description={item.id_ruang} />
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        patch(`/inventaris-ruang/${item.id_ruang}`);
                    }}
                    className="max-w-xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label>ID Ruang</Label>
                        <p className="font-mono text-sm text-muted-foreground">{item.id_ruang} (tidak dapat diubah)</p>
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
