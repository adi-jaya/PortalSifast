import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Rekanan', href: '/web-official/rekanan' },
    { title: 'Tambah', href: '#' },
];

type Option = { value: string; label: string };

type Props = {
    categoryOptions: Option[];
};

export default function WebOfficialRekananCreate({ categoryOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        category: '',
        description: '',
        logo: '',
        logo_file: null as File | null,
        website_url: '',
        sort_order: '',
        is_active: true,
    });

    function submit(e: React.FormEvent): void {
        e.preventDefault();
        post('/web-official/rekanan', {
            forceFormData: Boolean(data.logo_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Rekanan" />

            <div className="flex flex-col gap-4">
                <Heading title="Tambah Rekanan" description="Tambah mitra/rekanan baru untuk website official." />

                <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-xl border bg-card p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama rekanan</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug (opsional)</Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            placeholder="otomatis-dari-nama"
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category">Kategori</Label>
                        <Select
                            value={data.category || '__none__'}
                            onValueChange={(v) => setData('category', v === '__none__' ? '' : v)}
                        >
                            <SelectTrigger id="category">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none__">Tanpa kategori</SelectItem>
                                {categoryOptions.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Deskripsi (opsional)</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="logo">URL logo</Label>
                        <Input
                            id="logo"
                            value={data.logo}
                            onChange={(e) => setData('logo', e.target.value)}
                            placeholder="https://..."
                            disabled={Boolean(data.logo_file)}
                        />
                        <InputError message={errors.logo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="logo_file">Atau upload logo</Label>
                        <Input
                            id="logo_file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/svg+xml"
                            onChange={(e) => setData('logo_file', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.logo_file} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="website_url">Website (opsional)</Label>
                        <Input
                            id="website_url"
                            value={data.website_url}
                            onChange={(e) => setData('website_url', e.target.value)}
                            placeholder="https://..."
                        />
                        <InputError message={errors.website_url} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Urutan tampil</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', e.target.value)}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(c) => setData('is_active', c === true)}
                        />
                        <Label htmlFor="is_active" className="cursor-pointer">
                            Aktif (tampil di website)
                        </Label>
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/web-official/rekanan">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
