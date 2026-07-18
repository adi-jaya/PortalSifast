import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
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
    { title: 'Website Official', href: '/web-official' },
    { title: 'Promosi', href: '/web-official/promosi' },
    { title: 'Edit', href: '#' },
];

type PromoForm = {
    id: string;
    slug: string;
    title: string;
    label: string;
    excerpt: string;
    body: string | null;
    cover: string;
    start_date: string | null;
    end_date: string | null;
    is_featured: boolean;
    sort_order: number;
    is_active: boolean;
};

type Props = {
    promo: PromoForm;
};

export default function WebOfficialPromosiEdit({ promo }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        title: promo.title,
        slug: promo.slug,
        label: promo.label,
        excerpt: promo.excerpt,
        body: promo.body ?? '',
        cover: promo.cover,
        cover_file: null as File | null,
        start_date: promo.start_date ?? '',
        end_date: promo.end_date ?? '',
        sort_order: promo.sort_order,
        is_featured: promo.is_featured,
        is_active: promo.is_active,
    });

    function submit(e: React.FormEvent): void {
        e.preventDefault();

        put(`/web-official/promosi/${promo.id}`, {
            forceFormData: Boolean(data.cover_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Promo" />

            <div className="flex flex-col gap-4">
                <Heading title="Edit Promo" description="Perbarui data promo website official." />

                <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-xl border bg-card p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="label">Label</Label>
                        <Input id="label" value={data.label} onChange={(e) => setData('label', e.target.value)} />
                        <InputError message={errors.label} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="excerpt">Ringkasan</Label>
                        <Textarea
                            id="excerpt"
                            value={data.excerpt}
                            onChange={(e) => setData('excerpt', e.target.value)}
                            rows={3}
                            required
                        />
                        <InputError message={errors.excerpt} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="body">Detail promo</Label>
                        <Textarea id="body" value={data.body} onChange={(e) => setData('body', e.target.value)} rows={8} />
                        <InputError message={errors.body} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cover">URL cover</Label>
                        <Input id="cover" type="url" value={data.cover} onChange={(e) => setData('cover', e.target.value)} />
                        <InputError message={errors.cover} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cover_file">Upload cover baru</Label>
                        <Input
                            id="cover_file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => setData('cover_file', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.cover_file} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="start_date">Mulai promo</Label>
                            <Input
                                id="start_date"
                                type="datetime-local"
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                                required
                            />
                            <InputError message={errors.start_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="end_date">Akhir promo</Label>
                            <Input
                                id="end_date"
                                type="datetime-local"
                                value={data.end_date}
                                onChange={(e) => setData('end_date', e.target.value)}
                                required
                            />
                            <InputError message={errors.end_date} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Urutan</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', Number(e.target.value))}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_featured}
                            onChange={(e) => setData('is_featured', e.target.checked)}
                        />
                        Tampilkan di hero promo
                    </label>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                        />
                        Promo aktif
                    </label>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan…' : 'Simpan perubahan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/web-official/promosi">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
