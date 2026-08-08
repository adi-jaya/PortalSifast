import { Head, Link, router } from '@inertiajs/react';
import { FileStack, PenLine, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
    { title: 'Tata Naskah', href: '/tatanaskah/dokumen' },
];

type JenisOption = { kode: string; nama: string };
type StatusOption = { value: string; label: string };

type DokumenItem = {
    id: number;
    judul: string;
    nomor_dokumen: string | null;
    kode_jenis: string;
    status: string;
    penandatangan_nama: string | null;
    updated_at: string;
    jenis?: { kode: string; nama: string };
    unit_klasifikasi?: { kode: string; nama: string };
    pembuat?: { name: string };
};

type Props = {
    dokumen: {
        data: DokumenItem[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q?: string; status?: string; kode_jenis?: string; menunggu_saya?: boolean };
    jenisOptions: JenisOption[];
    statusOptions: StatusOption[];
    menungguPersetujuanCount: number;
};

function statusLabel(statusOptions: StatusOption[], value: string): string {
    return statusOptions.find((s) => s.value === value)?.label ?? value;
}

export default function DokumenIndex({
    dokumen,
    filters,
    jenisOptions,
    statusOptions,
    menungguPersetujuanCount,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? '__all__');
    const [kodeJenis, setKodeJenis] = useState(filters.kode_jenis ?? '__all__');

    const submitFilters = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/tatanaskah/dokumen',
            {
                q: q || undefined,
                status: status === '__all__' ? undefined : status,
                kode_jenis: kodeJenis === '__all__' ? undefined : kodeJenis,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Naskah Dinas Arahan" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Naskah Dinas Arahan"
                        description="SPO, PER, SK, INS, SE, dan naskah regulasi lainnya (Fase 1)"
                    />
                    <Button asChild>
                        <Link href="/tatanaskah/dokumen/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Buat Dokumen
                        </Link>
                    </Button>
                </div>

                {menungguPersetujuanCount > 0 && (
                    <div className="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:bg-amber-950/30">
                        <div className="flex items-start gap-3">
                            <PenLine className="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-400" />
                            <div>
                                <p className="font-medium text-amber-900 dark:text-amber-100">
                                    {menungguPersetujuanCount} dokumen menunggu persetujuan Anda
                                </p>
                                <p className="text-sm text-amber-800/80 dark:text-amber-200/80">
                                    Buka detail dokumen → tinjau PDF → klik Setujui & Aktifkan
                                </p>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/tatanaskah/dokumen?menunggu_saya=1">Lihat daftar</Link>
                        </Button>
                    </div>
                )}

                <form onSubmit={submitFilters} className="flex flex-wrap gap-2">
                    <div className="relative min-w-[200px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari judul atau nomor..."
                            className="pl-9"
                        />
                    </div>
                    <Select value={kodeJenis} onValueChange={setKodeJenis}>
                        <SelectTrigger className="w-[160px]">
                            <SelectValue placeholder="Jenis" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua jenis</SelectItem>
                            {jenisOptions.map((j) => (
                                <SelectItem key={j.kode} value={j.kode}>
                                    {j.kode} — {j.nama}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[160px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua status</SelectItem>
                            {statusOptions.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Filter
                    </Button>
                </form>

                {dokumen.data.length === 0 ? (
                    <EmptyState
                        icon={<FileStack className="size-7" />}
                        title="Belum ada dokumen"
                        description="Buat draft dokumen baru — upload PDF tanpa nomor resmi."
                        action={
                            <Button asChild>
                                <Link href="/tatanaskah/dokumen/create">Buat Dokumen</Link>
                            </Button>
                        }
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Judul</th>
                                    <th className="px-4 py-3 text-left font-medium">Nomor</th>
                                    <th className="px-4 py-3 text-left font-medium">Jenis</th>
                                    <th className="px-4 py-3 text-left font-medium">Penandatangan</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                    <th className="px-4 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {dokumen.data.map((item) => (
                                    <tr key={item.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">{item.judul}</td>
                                        <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                                            {item.nomor_dokumen ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">{item.kode_jenis}</td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.penandatangan_nama ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline">
                                                {statusLabel(statusOptions, item.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/tatanaskah/dokumen/${item.id}`}>Detail</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
