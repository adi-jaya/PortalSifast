import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Room = {
    id: string;
    slug: string;
    name: string;
    tagline: string | null;
    badge: string | null;
    description: string;
    price: number;
    photo: string | null;
    facilities_text: string;
    sort_order: number;
    is_active: boolean;
};

type Props = {
    room: Room;
};

export default function WebOfficialRoomsEdit({ room }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Website Official', href: '/web-official' },
        { title: 'Kamar Inap', href: '/web-official/rooms' },
        { title: 'Edit', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        name: room.name,
        slug: room.slug,
        tagline: room.tagline ?? '',
        badge: room.badge ?? '',
        description: room.description,
        price: String(room.price),
        photo: room.photo ?? '',
        photo_file: null as File | null,
        facilities_text: room.facilities_text,
        sort_order: String(room.sort_order),
        is_active: room.is_active,
    });

    function submit(e: React.FormEvent): void {
        e.preventDefault();
        post(`/web-official/rooms/${room.id}`, {
            forceFormData: Boolean(data.photo_file),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${room.name}`} />

            <div className="flex flex-col gap-4">
                <Heading title="Edit Kamar" description={room.name} />

                <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-xl border bg-card p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama kamar</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="tagline">Tagline</Label>
                        <Input
                            id="tagline"
                            value={data.tagline}
                            onChange={(e) => setData('tagline', e.target.value)}
                        />
                        <InputError message={errors.tagline} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="badge">Badge</Label>
                        <Input
                            id="badge"
                            value={data.badge}
                            onChange={(e) => setData('badge', e.target.value)}
                        />
                        <InputError message={errors.badge} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={5}
                            required
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="price">Harga per malam (Rp)</Label>
                        <Input
                            id="price"
                            type="number"
                            min={0}
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                            required
                        />
                        <InputError message={errors.price} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="facilities_text">Fasilitas (satu per baris)</Label>
                        <Textarea
                            id="facilities_text"
                            value={data.facilities_text}
                            onChange={(e) => setData('facilities_text', e.target.value)}
                            rows={5}
                            required
                        />
                        <InputError message={errors.facilities_text} />
                    </div>

                    {room.photo && (
                        <div className="rounded-lg border p-3">
                            <p className="mb-2 text-xs text-muted-foreground">Foto saat ini</p>
                            <img src={room.photo} alt="" className="max-h-40 rounded object-cover" />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="photo">URL foto baru</Label>
                        <Input
                            id="photo"
                            type="url"
                            value={data.photo}
                            onChange={(e) => setData('photo', e.target.value)}
                        />
                        <InputError message={errors.photo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="photo_file">Upload foto baru</Label>
                        <Input
                            id="photo_file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => setData('photo_file', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.photo_file} />
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

                    <div className="flex items-center gap-3 rounded-lg border p-4">
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
                            {processing ? 'Menyimpan…' : 'Simpan perubahan'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/web-official/rooms">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
