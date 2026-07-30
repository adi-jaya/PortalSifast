import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { ActiveBadge, StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Kamar Inap', href: '/web-official/rooms' },
];

type RoomRow = {
    id: string;
    slug: string;
    name: string;
    price: number;
    badge: string | null;
    is_active: boolean;
    sort_order: number;
};

type Paginated = {
    data: RoomRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    rooms: Paginated;
    filters: { q: string; is_active: string | boolean | null };
};

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(
        price,
    );
}

export default function WebOfficialRoomsIndex({ rooms, filters }: Props) {
    function applySearch(q: string): void {
        router.get('/web-official/rooms', { ...filters, q }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kamar Inap" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading title="Kamar Inap" description="Kelola data kamar untuk website official." />
                    <Button asChild>
                        <Link href="/web-official/rooms/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah kamar
                        </Link>
                    </Button>
                </div>

                <Input
                    placeholder="Cari nama kamar…"
                    value={filters.q}
                    onChange={(e) => applySearch(e.target.value)}
                    className="max-w-xs"
                />

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Harga</th>
                                    <th className="px-4 py-3 font-medium">Badge</th>
                                    <th className="px-4 py-3 font-medium">Urutan</th>
                                    <th className="px-4 py-3 font-medium">Aktif</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rooms.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada data kamar.
                                        </td>
                                    </tr>
                                ) : (
                                    rooms.data.map((room) => (
                                        <tr key={room.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{room.name}</div>
                                                <div className="text-xs text-muted-foreground">{room.slug}</div>
                                            </td>
                                            <td className="px-4 py-3">{formatPrice(room.price)}</td>
                                            <td className="px-4 py-3">
                                                {room.badge ? (
                                                    <StatusBadge tone="follow-up" label={room.badge} />
                                                ) : (
                                                    <span className="text-muted-foreground">–</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{room.sort_order}</td>
                                            <td className="px-4 py-3">
                                                <ActiveBadge active={room.is_active} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/web-official/rooms/${room.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {room.is_active && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (confirm('Nonaktifkan kamar ini?')) {
                                                                    router.delete(`/web-official/rooms/${room.id}`);
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {rooms.last_page > 1 && (
                        <div className="flex flex-wrap items-center justify-center gap-2 border-t px-4 py-3">
                            {rooms.links.map((link, i) => (
                                <span key={i}>
                                    {link.url ? (
                                        <Button size="sm" variant={link.active ? 'default' : 'outline'} asChild>
                                            <Link href={link.url} preserveState>
                                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <span
                                            className="inline-flex size-8 items-center justify-center text-muted-foreground"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <p className="text-sm text-muted-foreground">Total: {rooms.total} kamar</p>
            </div>
        </AppLayout>
    );
}
