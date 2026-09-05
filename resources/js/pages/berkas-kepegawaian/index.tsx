import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type PegawaiItem = {
    nik: string;
    nama: string;
    jbtn: string | null;
    berkas_count: number;
};

type PaginatedPegawai = {
    data: PegawaiItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    pegawai: PaginatedPegawai;
    filters: { q?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
];

export default function BerkasKepegawaianIndex({ pegawai, filters }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');

    const applySearch = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/berkas-kepegawaian',
            { q: search || undefined },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berkas Kepegawaian" />

            <div className="flex flex-col gap-4">
                <Heading
                    title="Berkas Kepegawaian"
                    description="Daftar pegawai aktif dan jumlah berkas yang sudah diunggah"
                />

                <form
                    onSubmit={applySearch}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end"
                >
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="NIK atau nama pegawai..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <Button type="submit">Cari</Button>
                </form>

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 font-medium">NIK</th>
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Jabatan</th>
                                    <th className="px-4 py-3 font-medium">Jumlah Berkas</th>
                                    <th className="px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pegawai.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="p-0">
                                            <EmptyState
                                                title="Tidak ada pegawai ditemukan"
                                                description="Coba ubah kata kunci pencarian atau pastikan ada pegawai aktif di SIMRS."
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    pegawai.data.map((p) => (
                                        <tr
                                            key={p.nik}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {p.nik}
                                            </td>
                                            <td className="px-4 py-3">{p.nama}</td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {p.jbtn ?? '–'}
                                            </td>
                                            <td className="px-4 py-3">{p.berkas_count}</td>
                                            <td className="px-4 py-3">
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link
                                                        href={`/berkas-kepegawaian/${encodeURIComponent(p.nik)}`}
                                                    >
                                                        Lihat berkas
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {pegawai.last_page > 1 && (
                        <div className="flex flex-wrap items-center justify-center gap-2 border-t px-4 py-3">
                            {pegawai.links.map((link, i) => (
                                <span key={i}>
                                    {link.url ? (
                                        <Button
                                            size="sm"
                                            variant={link.active ? 'default' : 'outline'}
                                            asChild
                                        >
                                            <Link href={link.url} preserveState>
                                                <span
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <span
                                            className="inline-flex size-8 items-center justify-center text-muted-foreground"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <p className="text-sm text-muted-foreground">
                    Total: {pegawai.total} pegawai aktif
                </p>
            </div>
        </AppLayout>
    );
}
