import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
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
    bidang: string | null;
    departemen: string | null;
    departemen_nama: string | null;
    berkas_count: number;
};

type PegawaiOption = {
    nik: string;
    nama: string;
    jbtn: string | null;
};

type FilterOption = {
    value: string;
    label: string;
    description?: string;
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
    pegawaiOptions: PegawaiOption[];
    filterOptions: {
        departemen: FilterOption[];
        bidang: FilterOption[];
    };
    filters: {
        q?: string;
        departemen?: string;
        bidang?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
];

export default function BerkasKepegawaianIndex({
    pegawai,
    pegawaiOptions,
    filterOptions,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [departemen, setDepartemen] = useState(filters.departemen ?? '');
    const [bidang, setBidang] = useState(filters.bidang ?? '');
    const [jumpNik, setJumpNik] = useState('');

    const pegawaiSelectOptions = useMemo<SearchSelectOption[]>(
        () =>
            pegawaiOptions.map((p) => ({
                value: p.nik,
                label: `${p.nama} (${p.nik})`,
                description: p.jbtn ?? undefined,
            })),
        [pegawaiOptions],
    );

    const departemenOptions = useMemo<SearchSelectOption[]>(
        () =>
            filterOptions.departemen.map((o) => ({
                value: o.value,
                label: o.label,
                description: o.description,
            })),
        [filterOptions.departemen],
    );

    const bidangOptions = useMemo<SearchSelectOption[]>(
        () =>
            filterOptions.bidang.map((o) => ({
                value: o.value,
                label: o.label,
            })),
        [filterOptions.bidang],
    );

    const applyFilters = (next: {
        q?: string;
        departemen?: string;
        bidang?: string;
    }) => {
        router.get(
            '/berkas-kepegawaian',
            {
                q: next.q || undefined,
                departemen: next.departemen || undefined,
                bidang: next.bidang || undefined,
            },
            { preserveState: true },
        );
    };

    const applySearch = (e?: FormEvent) => {
        e?.preventDefault();
        applyFilters({ q: search, departemen, bidang });
    };

    const onDepartemenChange = (value: string) => {
        setDepartemen(value);
        applyFilters({ q: search, departemen: value, bidang });
    };

    const onBidangChange = (value: string) => {
        setBidang(value);
        applyFilters({ q: search, departemen, bidang: value });
    };

    const jumpToPegawai = (nik: string) => {
        setJumpNik(nik);
        if (!nik) {
            return;
        }

        router.visit(`/berkas-kepegawaian/${encodeURIComponent(nik)}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berkas Kepegawaian" />

            <div className="flex flex-col gap-3">
                <Heading
                    title="Berkas Kepegawaian"
                    description="Pegawai aktif · filter departemen & bidang"
                    variant="small"
                />

                {/* Card 1 — filter */}
                <div className="rounded-xl border bg-card p-3">
                    <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <div className="space-y-1">
                            <Label htmlFor="jump-pegawai" className="text-xs">
                                Loncat pegawai
                            </Label>
                            <SearchSelect
                                inputId="jump-pegawai"
                                options={pegawaiSelectOptions}
                                value={jumpNik}
                                onChange={jumpToPegawai}
                                placeholder="NIK / nama..."
                                isClearable
                                noOptionsMessage="Tidak ditemukan"
                            />
                        </div>

                        <form onSubmit={applySearch} className="flex items-end gap-2">
                            <div className="relative min-w-0 flex-1 space-y-1">
                                <Label htmlFor="q" className="text-xs">
                                    Cari daftar
                                </Label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="q"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="NIK / nama..."
                                        className="h-9 pl-8"
                                    />
                                </div>
                            </div>
                            <Button type="submit" size="sm" className="h-9">
                                Cari
                            </Button>
                        </form>

                        <div className="space-y-1">
                            <Label htmlFor="filter-departemen" className="text-xs">
                                Departemen
                            </Label>
                            <SearchSelect
                                inputId="filter-departemen"
                                options={departemenOptions}
                                value={departemen}
                                onChange={onDepartemenChange}
                                placeholder="Semua"
                                isClearable
                                noOptionsMessage="Tidak ditemukan"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="filter-bidang" className="text-xs">
                                Bidang
                            </Label>
                            <SearchSelect
                                inputId="filter-bidang"
                                options={bidangOptions}
                                value={bidang}
                                onChange={onBidangChange}
                                placeholder="Semua"
                                isClearable
                                noOptionsMessage="Tidak ditemukan"
                            />
                        </div>
                    </div>
                </div>

                {/* Card 2 — daftar */}
                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="data-table">
                        <div className="data-table-scroll">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40">
                                        <th className="px-3 py-2 font-medium">NIK</th>
                                        <th className="px-3 py-2 font-medium">Nama</th>
                                        <th className="px-3 py-2 font-medium">Jabatan</th>
                                        <th className="px-3 py-2 font-medium">Bidang</th>
                                        <th className="px-3 py-2 font-medium">Departemen</th>
                                        <th className="px-3 py-2 font-medium">Berkas</th>
                                        <th className="px-3 py-2 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pegawai.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="p-0">
                                                <EmptyState
                                                    title="Tidak ada pegawai ditemukan"
                                                    description="Ubah filter atau kata kunci."
                                                />
                                            </td>
                                        </tr>
                                    ) : (
                                        pegawai.data.map((p) => (
                                            <tr
                                                key={p.nik}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-3 py-2 font-medium">{p.nik}</td>
                                                <td className="px-3 py-2">{p.nama}</td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {p.jbtn ?? '–'}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {p.bidang ?? '–'}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {p.departemen_nama ?? p.departemen ?? '–'}
                                                </td>
                                                <td className="px-3 py-2">{p.berkas_count}</td>
                                                <td className="px-3 py-2">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link
                                                            href={`/berkas-kepegawaian/${encodeURIComponent(p.nik)}`}
                                                        >
                                                            Buka
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

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t px-3 py-2 text-xs text-muted-foreground">
                        <span>Total {pegawai.total} pegawai aktif</span>
                        {pegawai.last_page > 1 ? (
                            <div className="flex flex-wrap items-center gap-1">
                                {pegawai.links.map((link, i) => (
                                    <span key={i}>
                                        {link.url ? (
                                            <Button
                                                size="sm"
                                                variant={link.active ? 'default' : 'outline'}
                                                className="h-7 min-w-7 px-2"
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
                                                className="inline-flex h-7 min-w-7 items-center justify-center px-2"
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        )}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
