import { Head, Link, router } from '@inertiajs/react';
import { Handshake, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { ActiveBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Rekanan', href: '/web-official/rekanan' },
];

type PartnerRow = {
    id: string;
    slug: string;
    name: string;
    category: string | null;
    logo: string;
    website_url: string | null;
    is_active: boolean;
    sort_order: number;
};

type Paginated = {
    data: PartnerRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    partners: Paginated;
    filters: { q: string };
};

export default function WebOfficialRekananIndex({ partners, filters }: Props) {
    function applySearch(q: string): void {
        router.get('/web-official/rekanan', { ...filters, q }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rekanan" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Rekanan Kami"
                        description="Kelola logo dan data mitra/rekanan yang tampil di website official."
                    />
                    <Button asChild>
                        <Link href="/web-official/rekanan/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah rekanan
                        </Link>
                    </Button>
                </div>

                <Input
                    placeholder="Cari nama atau kategori…"
                    value={filters.q}
                    onChange={(e) => applySearch(e.target.value)}
                    className="max-w-xs"
                />

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Logo</th>
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Kategori</th>
                                    <th className="px-4 py-3 font-medium">Urutan</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {partners.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            <Handshake className="mx-auto mb-2 h-8 w-8 opacity-40" />
                                            Belum ada data rekanan.
                                        </td>
                                    </tr>
                                ) : (
                                    partners.data.map((partner) => (
                                        <tr key={partner.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <img
                                                    src={partner.logo}
                                                    alt={partner.name}
                                                    className="h-10 w-20 rounded-md border bg-white object-contain p-1"
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{partner.name}</div>
                                                <div className="text-xs text-muted-foreground">{partner.slug}</div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {partner.category ?? '–'}
                                            </td>
                                            <td className="px-4 py-3">{partner.sort_order}</td>
                                            <td className="px-4 py-3">
                                                <ActiveBadge active={partner.is_active} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/web-official/rekanan/${partner.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {partner.is_active && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (confirm('Nonaktifkan rekanan ini?')) {
                                                                    router.delete(`/web-official/rekanan/${partner.id}`);
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

                <p className="text-sm text-muted-foreground">Total: {partners.total} rekanan</p>
            </div>
        </AppLayout>
    );
}
