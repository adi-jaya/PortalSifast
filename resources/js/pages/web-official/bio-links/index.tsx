import { Head, router, useForm } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowDown,
    ArrowUp,
    BedDouble,
    Briefcase,
    Calendar,
    FileText,
    Globe,
    HeartPulse,
    Hospital,
    Link as LinkIcon,
    MapPin,
    Megaphone,
    MessageCircle,
    MessageSquare,
    Pencil,
    Phone,
    Plus,
    Smartphone,
    Star,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const BASE = '/web-official/bio-links';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Bio Links', href: BASE },
];

const ICON_COMPONENTS: Record<string, LucideIcon> = {
    whatsapp: MessageCircle,
    phone: Phone,
    stethoscope: Stethoscope,
    hospital: Hospital,
    bed: BedDouble,
    star: Star,
    'message-square': MessageSquare,
    calendar: Calendar,
    megaphone: Megaphone,
    'heart-pulse': HeartPulse,
    smartphone: Smartphone,
    'file-text': FileText,
    briefcase: Briefcase,
    'map-pin': MapPin,
    globe: Globe,
    link: LinkIcon,
};

type BioLink = {
    id: string;
    title: string;
    description: string | null;
    url: string;
    icon: string;
    imageUrl: string | null;
    featured: boolean;
    sortOrder: number;
    isActive: boolean;
    startsAt: string | null;
    endsAt: string | null;
};

type BioSocial = { platform: string; url: string; isActive: boolean };

type Props = {
    links: BioLink[];
    socials: BioSocial[];
    icons: string[];
    maxActive: number;
};

/** ISO → nilai input datetime-local (zona waktu browser). */
function toLocalInput(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

/** Nilai datetime-local → ISO dengan zona waktu, atau null. */
function toIso(local: string): string | null {
    return local ? new Date(local).toISOString() : null;
}

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '…';
}

function BioIcon({ name, className }: { name: string; className?: string }) {
    const Icon = ICON_COMPONENTS[name] ?? LinkIcon;
    return <Icon className={className} />;
}

export default function WebOfficialBioLinksIndex({ links, socials, icons, maxActive }: Props) {
    const [editing, setEditing] = useState<BioLink | null>(null);
    const [open, setOpen] = useState(false);
    const activeCount = links.filter((l) => l.isActive).length;

    function openForm(link: BioLink | null): void {
        setEditing(link);
        setOpen(true);
    }

    function move(index: number, delta: number): void {
        const ids = links.map((l) => l.id);
        const target = index + delta;
        [ids[index], ids[target]] = [ids[target], ids[index]];
        router.put(`${BASE}/order`, { ids }, { preserveScroll: true });
    }

    function toggleActive(link: BioLink): void {
        router.patch(`${BASE}/${link.id}`, { isActive: !link.isActive }, { preserveScroll: true });
    }

    function remove(link: BioLink): void {
        if (confirm(`Hapus tautan "${link.title}"?`)) {
            router.delete(`${BASE}/${link.id}`, { preserveScroll: true });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bio Links" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Bio Links"
                        description="Kelola tautan di halaman /bio website official (dipakai di bio Instagram/TikTok dan QR code)."
                    />
                    <Button onClick={() => openForm(null)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah tautan
                    </Button>
                </div>

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="w-20 px-4 py-3 font-medium">Urutan</th>
                                    <th className="px-4 py-3 font-medium">Tautan</th>
                                    <th className="px-4 py-3 font-medium">Periode tayang</th>
                                    <th className="px-4 py-3 font-medium">Aktif</th>
                                    <th className="w-24 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {links.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada tautan. Website akan memakai data statis bawaan.
                                        </td>
                                    </tr>
                                ) : (
                                    links.map((link, i) => (
                                        <tr key={link.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" disabled={i === 0} onClick={() => move(i, -1)} aria-label="Naikkan">
                                                        <ArrowUp className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        disabled={i === links.length - 1}
                                                        onClick={() => move(i, 1)}
                                                        aria-label="Turunkan"
                                                    >
                                                        <ArrowDown className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    {link.imageUrl ? (
                                                        <img src={link.imageUrl} alt="" className="h-10 w-10 rounded-full border object-cover" />
                                                    ) : (
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full border bg-muted">
                                                            <BioIcon name={link.icon} className="h-5 w-5" />
                                                        </div>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-medium">
                                                            {link.title}
                                                            {link.featured && (
                                                                <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Unggulan</span>
                                                            )}
                                                        </div>
                                                        {link.description && <div className="text-xs text-muted-foreground">{link.description}</div>}
                                                        <div className="truncate text-xs text-muted-foreground">{link.url}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground">
                                                {link.startsAt || link.endsAt ? `${formatDate(link.startsAt)} – ${formatDate(link.endsAt)}` : 'Selalu'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Checkbox checked={link.isActive} onCheckedChange={() => toggleActive(link)} aria-label="Aktif" />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" onClick={() => openForm(link)} aria-label="Ubah">
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" onClick={() => remove(link)} aria-label="Hapus">
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">
                    {activeCount} dari maksimal {maxActive} tautan aktif. Tombol IGD dan identitas RS tidak diatur di sini.
                </p>

                <SocialsForm socials={socials} />
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Ubah tautan' : 'Tambah tautan'}</DialogTitle>
                    </DialogHeader>
                    <LinkForm key={editing?.id ?? 'new'} link={editing} icons={icons} onDone={() => setOpen(false)} />
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function LinkForm({ link, icons, onDone }: { link: BioLink | null; icons: string[]; onDone: () => void }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        title: link?.title ?? '',
        description: link?.description ?? '',
        url: link?.url ?? '',
        icon: link?.icon ?? 'link',
        imageUrl: link?.imageUrl ?? '',
        imageFile: null as File | null,
        featured: link?.featured ?? false,
        isActive: link?.isActive ?? true,
        startsAt: toLocalInput(link?.startsAt ?? null),
        endsAt: toLocalInput(link?.endsAt ?? null),
    });

    const previewUrl = data.imageFile ? URL.createObjectURL(data.imageFile) : data.imageUrl;
    const hasImage = Boolean(previewUrl);

    function submit(e: React.FormEvent): void {
        e.preventDefault();
        transform((d) => ({
            ...d,
            featured: hasImage && d.featured,
            startsAt: toIso(d.startsAt),
            endsAt: toIso(d.endsAt),
            ...(link ? { _method: 'patch' } : {}),
        }));
        post(link ? `${BASE}/${link.id}` : BASE, { forceFormData: true, preserveScroll: true, onSuccess: onDone });
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="title">Judul</Label>
                <Input id="title" value={data.title} maxLength={60} onChange={(e) => setData('title', e.target.value)} required />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Deskripsi (opsional)</Label>
                <Input id="description" value={data.description} maxLength={100} onChange={(e) => setData('description', e.target.value)} />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="url">URL tujuan</Label>
                <Input
                    id="url"
                    value={data.url}
                    maxLength={500}
                    onChange={(e) => setData('url', e.target.value)}
                    placeholder="/dokter, https://..., tel:..., mailto:..."
                    required
                />
                <InputError message={errors.url} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="icon">Ikon</Label>
                <Select value={data.icon} onValueChange={(v) => setData('icon', v)}>
                    <SelectTrigger id="icon">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {icons.map((key) => (
                            <SelectItem key={key} value={key}>
                                <span className="flex items-center gap-2">
                                    <BioIcon name={key} className="h-4 w-4" />
                                    {key}
                                </span>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.icon} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="imageFile">Gambar (opsional)</Label>
                {previewUrl && <img src={previewUrl} alt="" className="aspect-video w-full rounded-md border object-cover" />}
                <Input
                    id="imageFile"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={(e) => setData('imageFile', e.target.files?.[0] ?? null)}
                />
                {data.imageUrl && !data.imageFile && (
                    <Button type="button" variant="outline" size="sm" className="w-fit" onClick={() => setData('imageUrl', '')}>
                        Hapus gambar
                    </Button>
                )}
                <p className="text-xs text-muted-foreground">Kartu unggulan 1200×675 px (16:9), thumbnail min. 200×200 px. Maks 2 MB.</p>
                <InputError message={errors.imageFile ?? errors.imageUrl} />
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="featured"
                    checked={hasImage && data.featured}
                    disabled={!hasImage}
                    onCheckedChange={(c) => setData('featured', c === true)}
                />
                <Label htmlFor="featured" className={hasImage ? 'cursor-pointer' : 'text-muted-foreground'}>
                    Tampilkan sebagai kartu unggulan {!hasImage && '(perlu gambar)'}
                </Label>
            </div>
            <InputError message={errors.featured} />

            <div className="grid grid-cols-2 gap-3">
                <div className="grid gap-2">
                    <Label htmlFor="startsAt">Mulai tayang</Label>
                    <Input id="startsAt" type="datetime-local" value={data.startsAt} onChange={(e) => setData('startsAt', e.target.value)} />
                    <InputError message={errors.startsAt} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="endsAt">Selesai tayang</Label>
                    <Input id="endsAt" type="datetime-local" value={data.endsAt} onChange={(e) => setData('endsAt', e.target.value)} />
                    <InputError message={errors.endsAt} />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <Checkbox id="isActive" checked={data.isActive} onCheckedChange={(c) => setData('isActive', c === true)} />
                <Label htmlFor="isActive" className="cursor-pointer">
                    Aktif (tampil di halaman /bio)
                </Label>
            </div>
            <InputError message={errors.isActive} />

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Menyimpan…' : 'Simpan'}
                </Button>
                <Button type="button" variant="outline" onClick={onDone}>
                    Batal
                </Button>
            </div>
        </form>
    );
}

function SocialsForm({ socials }: { socials: BioSocial[] }) {
    const { data, setData, put, processing, errors } = useForm({ socials });
    const fieldErrors = errors as Record<string, string | undefined>;

    function update(index: number, patch: Partial<BioSocial>): void {
        setData(
            'socials',
            data.socials.map((s, i) => (i === index ? { ...s, ...patch } : s)),
        );
    }

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                put(`${BASE}/socials`, { preserveScroll: true });
            }}
            className="max-w-2xl space-y-4 rounded-xl border bg-card p-6"
        >
            <div>
                <h3 className="font-semibold">Media sosial</h3>
                <p className="text-sm text-muted-foreground">Ikon media sosial di bagian bawah halaman /bio. URL harus https://.</p>
            </div>

            {data.socials.map((social, i) => (
                <div key={social.platform} className="grid gap-2">
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id={`social-${social.platform}`}
                            checked={social.isActive}
                            onCheckedChange={(c) => update(i, { isActive: c === true })}
                        />
                        <Label htmlFor={`social-${social.platform}`} className="w-24 cursor-pointer capitalize">
                            {social.platform}
                        </Label>
                        <Input
                            value={social.url}
                            onChange={(e) => update(i, { url: e.target.value })}
                            placeholder={`https://${social.platform}.com/...`}
                        />
                    </div>
                    <InputError message={fieldErrors[`socials.${i}.url`]} />
                </div>
            ))}

            <Button type="submit" disabled={processing}>
                {processing ? 'Menyimpan…' : 'Simpan media sosial'}
            </Button>
        </form>
    );
}
