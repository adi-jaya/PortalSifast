import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
    { title: 'Berita & Informasi', href: '/web-official/articles' },
    { title: 'Tambah', href: '#' },
];

type Option = { value: string; label: string };

type Props = {
    categoryOptions: Option[];
    statusOptions: Option[];
};

export default function WebOfficialArticlesCreate({ categoryOptions, statusOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        category: categoryOptions[0]?.value ?? 'Berita',
        excerpt: '',
        body: '',
        cover: '',
        cover_file: null as File | null,
        valid_until: '',
        status: 'draft',
    });

    const isPromo = data.category === 'Promo';

    function submit(e: React.FormEvent): void {
        e.preventDefault();
        post('/web-official/articles', {
            forceFormData: Boolean(data.cover_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Artikel" />

            <div className="flex flex-col gap-4">
                <Heading title="Tambah Artikel" description="Buat artikel baru untuk website official." />

                <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-xl border bg-card p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug (opsional)</Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            placeholder="otomatis-dari-judul"
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Kategori</Label>
                        <Select value={data.category} onValueChange={(v) => setData('category', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
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
                        <Label htmlFor="body">Isi artikel</Label>
                        <Textarea
                            id="body"
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={10}
                            required
                        />
                        <InputError message={errors.body} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cover">URL gambar cover (opsional jika upload file)</Label>
                        <Input
                            id="cover"
                            type="url"
                            value={data.cover}
                            onChange={(e) => setData('cover', e.target.value)}
                            placeholder="https://..."
                        />
                        <InputError message={errors.cover} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cover_file">Upload gambar cover</Label>
                        <Input
                            id="cover_file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => setData('cover_file', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.cover_file} />
                    </div>

                    {isPromo && (
                        <div className="grid gap-2">
                            <Label htmlFor="valid_until">Berlaku sampai</Label>
                            <Input
                                id="valid_until"
                                type="datetime-local"
                                value={data.valid_until}
                                onChange={(e) => setData('valid_until', e.target.value)}
                                required={isPromo}
                            />
                            <InputError message={errors.valid_until} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label>Status</Label>
                        <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan…' : 'Simpan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/web-official/articles">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
