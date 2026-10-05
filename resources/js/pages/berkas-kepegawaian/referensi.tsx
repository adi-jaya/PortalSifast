import { Head, router } from '@inertiajs/react';
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

type TypeOption = { value: string; label: string };

type Paginated = {
    data: Record<string, string | number | null>[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    type: string;
    typeLabel: string;
    columns: string[];
    types: TypeOption[];
    items: Paginated;
    filters: { q?: string; type?: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
    { title: 'Master Referensi', href: '/berkas-kepegawaian/referensi' },
];

const COLUMN_LABELS: Record<string, string> = {
    nama: 'Nama',
    dep_id: 'Kode',
    stts: 'Kode',
    ktg: 'Keterangan',
    indek: 'Indeks',
    hakcuti: 'Hak cuti',
    tingkat: 'Tingkat',
    gapok1: 'Gapok',
    kenaikan: 'Kenaikan',
    maksimal: 'Maksimal',
    kode: 'Kode',
    tnj: 'Tunjangan',
    kode_kelompok: 'Kode',
    nama_kelompok: 'Nama',
};

function cellValue(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

export default function ReferensiKepegawaianPage({
    type,
    typeLabel,
    columns,
    types,
    items,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [selectedType, setSelectedType] = useState(type);

    const typeOptions = useMemo<SearchSelectOption[]>(
        () => types.map((t) => ({ value: t.value, label: t.label })),
        [types],
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/berkas-kepegawaian/referensi',
            {
                type: selectedType || undefined,
                q: search || undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Master Referensi — ${typeLabel}`} />

            <div className="flex flex-col gap-4">
                <Heading
                    title="Master Referensi"
                    description="Data referensi kepegawaian dari SIMRS (baca saja)"
                />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-4 lg:flex-row lg:items-end"
                >
                    <div className="w-full space-y-1.5 lg:w-64">
                        <Label htmlFor="type">Jenis referensi</Label>
                        <SearchSelect
                            inputId="type"
                            options={typeOptions}
                            value={selectedType}
                            onChange={setSelectedType}
                            placeholder="Pilih jenis..."
                            isClearable={false}
                        />
                    </div>
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Filter teks..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                <div className="data-table">
                    <div className="data-table-scroll">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b">
                                    {columns.map((column) => (
                                        <th key={column} className="px-4 py-3 font-medium">
                                            {COLUMN_LABELS[column] ?? column}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={columns.length} className="p-0">
                                            <EmptyState
                                                title="Tidak ada data"
                                                description="Coba ubah jenis referensi atau kata kunci."
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row, index) => (
                                        <tr
                                            key={`${type}-${index}`}
                                            className="border-b last:border-0"
                                        >
                                            {columns.map((column) => (
                                                <td key={column} className="px-4 py-3">
                                                    {cellValue(row[column])}
                                                </td>
                                            ))}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {items.last_page > 1 && (
                        <div className="flex flex-wrap items-center justify-center gap-2 border-t px-4 py-3">
                            {items.links.map((link, i) => (
                                <Button
                                    key={`${link.label}-${i}`}
                                    size="sm"
                                    variant={link.active ? 'default' : 'outline'}
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url &&
                                        router.get(link.url, {}, { preserveState: true })
                                    }
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>

                <p className="text-sm text-muted-foreground">
                    Total: {items.total} baris · {typeLabel}
                </p>
            </div>
        </AppLayout>
    );
}
