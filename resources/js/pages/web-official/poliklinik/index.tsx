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
    { title: 'Poliklinik', href: '/web-official/poliklinik' },
];

type PolyclinicRow = {
    id: string;
    kd_poli: string;
    slug: string;
    name: string;
    sort_order: number;
    is_active: boolean;
};

type Paginated = {
    data: PolyclinicRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    polyclinics: Paginated;
    filters: { q: string; is_active: string | boolean | null };
};

export default function WebOfficialPolyclinicIndex({ polyclinics, filters }: Props) {
    function applySearch(q: string): void {
        router.get('/web-official/poliklinik', { ...filters, q }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Poliklinik" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading title="Poliklinik" description="Kelola konten poliklinik landing page website official." />
                    <Button asChild>
                        <Link href="/web-official/poliklinik/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah poliklinik
                        </Link>
                    </Button>
                </div>

                <Input
                    placeholder="Cari poliklinik..."
                    value={filters.q}
                    onChange={(event) => applySearch(event.target.value)}
                    className="max-w-xs"
                />

                <div className="rounded-2xl border border-border/80 bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Nama Poliklinik</th>
                                    <th className="px-4 py-3 font-medium">Kode Poli</th>
                                    <th className="px-4 py-3 font-medium">Urutan</th>
                                    <th className="px-4 py-3 font-medium">Aktif</th>
                                    <th className="w-28 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {polyclinics.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada konten poliklinik.
                                        </td>
                                    </tr>
                                ) : (
                                    polyclinics.data.map((polyclinic) => (
                                        <tr key={polyclinic.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{polyclinic.name}</div>
                                                <div className="text-xs text-muted-foreground">{polyclinic.slug}</div>
                                            </td>
                                            <td className="px-4 py-3">{polyclinic.kd_poli}</td>
                                            <td className="px-4 py-3">{polyclinic.sort_order}</td>
                                            <td className="px-4 py-3">
                                                <ActiveBadge active={polyclinic.is_active} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={`/web-official/poliklinik/${polyclinic.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {polyclinic.is_active && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                if (confirm('Nonaktifkan poliklinik ini?')) {
                                                                    router.delete(`/web-official/poliklinik/${polyclinic.id}`);
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

                <p className="text-sm text-muted-foreground">Total: {polyclinics.total} poliklinik</p>
            </div>
        </AppLayout>
    );
}
