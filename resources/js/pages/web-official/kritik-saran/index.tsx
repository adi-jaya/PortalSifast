import { Head, Link, router } from '@inertiajs/react';
import { Eye, MessageSquareWarning, Star } from 'lucide-react';
import Heading from '@/components/heading';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Website Official', href: '/web-official' },
    { title: 'Kritik & Saran', href: '/web-official/kritik-saran' },
];

type FeedbackRow = {
    id: string;
    full_name: string;
    phone: string;
    service_unit: string;
    rating: number;
    message: string;
    status: string;
    created_at: string | null;
};

type Paginated = {
    data: FeedbackRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Option = { value: string; label: string };

type Props = {
    feedbacks: Paginated;
    filters: {
        q: string;
        status: string;
        service_unit: string;
        rating: string | number | null;
    };
    statusOptions: Option[];
    serviceUnitOptions: string[];
};

function formatDate(value: string | null): string {
    if (!value) {
        return '–';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

function truncate(text: string, max = 80): string {
    return text.length > max ? `${text.slice(0, max)}…` : text;
}

export default function WebOfficialKritikSaranIndex({
    feedbacks,
    filters,
    statusOptions,
    serviceUnitOptions,
}: Props) {
    function applyFilters(next: Partial<Props['filters']>): void {
        router.get('/web-official/kritik-saran', { ...filters, ...next }, { preserveState: true, replace: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kritik & Saran" />

            <div className="flex flex-col gap-4">
                <Heading
                    title="Kritik & Saran"
                    description="Pengaduan dari pengunjung website official — formulir 'Tidak Puas' di halaman /kritik-saran."
                />

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <Input
                        placeholder="Cari nama, telepon, atau pesan…"
                        value={filters.q}
                        onChange={(e) => applyFilters({ q: e.target.value })}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.status || '__all__'}
                        onValueChange={(v) => applyFilters({ status: v === '__all__' ? '' : v })}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua status</SelectItem>
                            {statusOptions.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.service_unit || '__all__'}
                        onValueChange={(v) => applyFilters({ service_unit: v === '__all__' ? '' : v })}
                    >
                        <SelectTrigger className="w-[220px]">
                            <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua unit</SelectItem>
                            {serviceUnitOptions.map((unit) => (
                                <SelectItem key={unit} value={unit}>
                                    {unit}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.rating ? String(filters.rating) : '__all__'}
                        onValueChange={(v) => applyFilters({ rating: v === '__all__' ? '' : v })}
                    >
                        <SelectTrigger className="w-[140px]">
                            <SelectValue placeholder="Rating" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua rating</SelectItem>
                            {[5, 4, 3, 2, 1].map((r) => (
                                <SelectItem key={r} value={String(r)}>
                                    {r} bintang
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-2xl border border-border/80 bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Pengirim</th>
                                    <th className="px-4 py-3 font-medium">Unit</th>
                                    <th className="px-4 py-3 font-medium">Rating</th>
                                    <th className="px-4 py-3 font-medium">Pesan</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Diterima</th>
                                    <th className="w-20 px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {feedbacks.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                                            <MessageSquareWarning className="mx-auto mb-2 h-8 w-8 opacity-40" />
                                            Belum ada pengaduan masuk.
                                        </td>
                                    </tr>
                                ) : (
                                    feedbacks.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.full_name}</div>
                                                <div className="text-xs text-muted-foreground">{row.phone}</div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.service_unit}</td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center gap-1">
                                                    <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                                    {row.rating}
                                                </span>
                                            </td>
                                            <td className="max-w-xs px-4 py-3 text-muted-foreground">
                                                {truncate(row.message)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={row.status} />
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {formatDate(row.created_at)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/web-official/kritik-saran/${row.id}`}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">Total: {feedbacks.total} pengaduan</p>
            </div>
        </AppLayout>
    );
}
