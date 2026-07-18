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
    { title: 'Edit', href: '#' },
];

type AvailablePolyclinic = {
    kdPoli: string;
    namaPoli: string;
};

type PolyclinicForm = {
    id: string;
    kd_poli: string;
    slug: string;
    label: string;
    simrs_name: string;
    name_override: string | null;
    short_description: string;
    long_description: string | null;
    photo: string | null;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
};

type Props = {
    polyclinic: PolyclinicForm;
    availablePolyclinics: AvailablePolyclinic[];
};

export default function WebOfficialPolyclinicEdit({ polyclinic, availablePolyclinics }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        kd_poli: polyclinic.kd_poli,
        slug: polyclinic.slug,
        label: polyclinic.label,
        name_override: polyclinic.name_override ?? '',
        short_description: polyclinic.short_description,
        long_description: polyclinic.long_description ?? '',
        photo: polyclinic.photo ?? '',
        photo_file: null as File | null,
        icon: polyclinic.icon ?? '',
        sort_order: polyclinic.sort_order,
        is_active: polyclinic.is_active,
    });

    function submit(event: React.FormEvent): void {
        event.preventDefault();
        put(`/web-official/poliklinik/${polyclinic.id}`, {
            forceFormData: Boolean(data.photo_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Poliklinik" />

            <div className="flex flex-col gap-4">
                <Heading title="Edit Poliklinik" description="Perbarui konten landing page poliklinik." />

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
                                    <SelectValue />
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
                        <p className="text-xs text-muted-foreground">Nama SIMRS saat ini: {polyclinic.simrs_name}</p>
                        <InputError message={errors.kd_poli} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
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
                        <Input id="photo" type="url" value={data.photo} onChange={(event) => setData('photo', event.target.value)} />
                        <InputError message={errors.photo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="photo_file">Upload Foto Baru</Label>
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
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan perubahan'}
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
