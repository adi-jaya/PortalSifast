import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Poliklinik', href: '/web-official/poliklinik' },
    { title: 'Tambah', href: '#' },
];

type AvailablePolyclinic = {
    kdPoli: string;
    namaPoli: string;
};

type Props = {
    availablePolyclinics: AvailablePolyclinic[];
};

export default function WebOfficialPolyclinicCreate({ availablePolyclinics }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        kd_poli: availablePolyclinics[0]?.kdPoli ?? '',
        slug: '',
        label: 'KLINIK SPESIALIS',
        name_override: '',
        short_description: '',
        long_description: '',
        photo: '',
        photo_file: null as File | null,
        icon: '',
        sort_order: 1,
        is_active: true,
    });

    function submit(event: React.FormEvent): void {
        event.preventDefault();
        post('/web-official/poliklinik', {
            forceFormData: Boolean(data.photo_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Poliklinik" />

            <div className="flex flex-col gap-4">
                <Heading title="Tambah Poliklinik" description="Tambahkan konten landing page poliklinik." />

                <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-xl border bg-card p-6">
                    <div className="grid gap-2">
                        <Label>Kode Poliklinik SIMRS</Label>
                        {availablePolyclinics.length === 0 ? (
                            <p className="rounded-md border border-dashed px-3 py-2 text-sm text-muted-foreground">
                                Data poliklinik SIMRS belum tersedia. Periksa koneksi database SIMRS lalu muat ulang
                                halaman ini.
                            </p>
                        ) : (
                            <Select value={data.kd_poli} onValueChange={(value) => setData('kd_poli', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih poliklinik" />
                                </SelectTrigger>
                                <SelectContent className="max-h-72">
                                    {availablePolyclinics.map((item) => (
                                        <SelectItem key={item.kdPoli} value={item.kdPoli}>
                                            {item.kdPoli} - {item.namaPoli}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <InputError message={errors.kd_poli} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug (opsional)</Label>
                        <Input id="slug" value={data.slug} onChange={(event) => setData('slug', event.target.value)} />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="label">Label</Label>
                        <Input id="label" value={data.label} onChange={(event) => setData('label', event.target.value)} />
                        <InputError message={errors.label} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name_override">Nama Override (opsional)</Label>
                        <Input
                            id="name_override"
                            value={data.name_override}
                            onChange={(event) => setData('name_override', event.target.value)}
                            placeholder="Kosongkan untuk pakai nama SIMRS"
                        />
                        <InputError message={errors.name_override} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="short_description">Deskripsi Singkat</Label>
                        <Textarea
                            id="short_description"
                            value={data.short_description}
                            onChange={(event) => setData('short_description', event.target.value)}
                            rows={3}
                            required
                        />
                        <InputError message={errors.short_description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="long_description">Tentang Layanan</Label>
                        <Textarea
                            id="long_description"
                            value={data.long_description}
                            onChange={(event) => setData('long_description', event.target.value)}
                            rows={8}
                        />
                        <InputError message={errors.long_description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="photo">URL Foto</Label>
                        <Input
                            id="photo"
                            type="url"
                            value={data.photo}
                            onChange={(event) => setData('photo', event.target.value)}
                            placeholder="https://..."
                        />
                        <InputError message={errors.photo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="photo_file">Upload Foto</Label>
                        <Input
                            id="photo_file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => setData('photo_file', event.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.photo_file} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="icon">Icon (opsional)</Label>
                        <Input id="icon" value={data.icon} onChange={(event) => setData('icon', event.target.value)} />
                        <InputError message={errors.icon} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Urutan</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(event) => setData('sort_order', Number(event.target.value))}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(event) => setData('is_active', event.target.checked)}
                        />
                        Poliklinik aktif
                    </label>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing || availablePolyclinics.length === 0}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/web-official/poliklinik">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
