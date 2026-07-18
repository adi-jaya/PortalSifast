import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { ActiveBadge, YesNoBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Promosi', href: '/web-official/promosi' },
];

type PromoRow = {
    id: string;
    slug: string;
    title: string;
    label: string;
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
    start_date: string | null;
    end_date: string | null;
};

type Paginated = {
    data: PromoRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    promos: Paginated;
    filters: { q: string; is_active: string | boolean | null };
};

export default function WebOfficialPromosiIndex({ promos, filters }: Props) {
    function applySearch(q: string): void {
        router.get('/web-official/promosi', { ...filters, q }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Promosi" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading title="Promosi" description="Kelola promo website official." />
                    <Button asChild>
                        <Link href="/web-official/promosi/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah promo
                        </Link>
                    </Button>
                </div>

                <Input
                    placeholder="Cari judul promo…"
                    value={filters.q}
                    onChange={(e) => applySearch(e.target.value)}
                    className="max-w-xs"
                />

                <div className="rounded-2xl border border-border/80 bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Judul</th>
                                    <th className="px-4 py-3 font-medium">Periode</th>
                                    <th className="px-4 py-3 font-medium">Featured</th>
                                    <th className="px-4 py-3 font-medium">Aktif</th>
                                    <th className="px-4 py-3 font-medium">Urutan</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {promos.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada data promosi.
                                        </td>
                                    </tr>
                                ) : (
                                    promos.data.map((promo) => (
                                        <tr key={promo.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{promo.title}</div>
                                                <div className="text-xs text-muted-foreground">{promo.slug}</div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {promo.start_date ? new Date(promo.start_date).toLocaleDateString('id-ID') : '–'} -{' '}
                                                {promo.end_date ? new Date(promo.end_date).toLocaleDateString('id-ID') : '–'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <YesNoBadge value={promo.is_featured} yesLabel="Unggulan" noLabel="–" />
                                            </td>
                                            <td className="px-4 py-3">
                                                <ActiveBadge active={promo.is_active} />
                                            </td>
                                            <td className="px-4 py-3">{promo.sort_order}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/web-official/promosi/${promo.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {promo.is_active && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (confirm('Nonaktifkan promo ini?')) {
                                                                    router.delete(`/web-official/promosi/${promo.id}`);
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
                </div>

                <p className="text-sm text-muted-foreground">Total: {promos.total} promo</p>
            </div>
        </AppLayout>
    );
}
