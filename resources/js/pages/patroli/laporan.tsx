import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
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

type Report = {
    from: string;
    to: string;
    total_checkin: number;
    per_item: {
        nama_item: string;
        berfungsi: number;
        tidak_berfungsi: number;
        tidak_dicek: number;
        persen_berfungsi: number | null;
    }[];
    per_area: {
        patroli_area_id: number;
        nama_area: string | null;
        total_checkin: number;
        tidak_berfungsi: number;
    }[];
    per_ruang: {
        patroli_ruang_id: number;
        kode: string | null;
        nama_ruang: string | null;
        nama_area: string | null;
        total_checkin: number;
        tidak_berfungsi: number;
    }[];
    per_template: {
        patroli_template_id: number;
        nama: string | null;
        total_checkin: number;
    }[];
    temuan: {
        checkin_id: number;
        checked_at: string | null;
        kode: string | null;
        nama_ruang: string | null;
        nama_area: string | null;
        nama_item: string;
        petugas: string | null;
    }[];
};

type Props = {
    filters: { periode?: string; from?: string; to?: string };
    report: Report;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Laporan Patroli', href: '/patroli/laporan' },
];

export default function PatroliLaporan({ filters, report }: Props) {
    const [periode, setPeriode] = useState(filters.periode ?? 'bulanan');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/patroli/laporan',
            {
                periode,
                from: periode === 'custom' ? from || undefined : undefined,
                to: periode === 'custom' ? to || undefined : undefined,
            },
            { preserveState: true },
        );
    };

    const exportUrl = (() => {
        const params = new URLSearchParams({ periode });
        if (periode === 'custom') {
            if (from) params.set('from', from);
            if (to) params.set('to', to);
        }
        return `/patroli/laporan/export?${params.toString()}`;
    })();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Patroli" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Laporan Patroli</h1>
                        <p className="text-sm text-muted-foreground">
                            Agregasi mutu security · {report.from} s/d {report.to}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <a href={exportUrl}>Export CSV</a>
                    </Button>
                </div>

                <form onSubmit={apply} className="flex flex-wrap gap-2">
                    <Select value={periode} onValueChange={setPeriode}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="mingguan">Mingguan</SelectItem>
                            <SelectItem value="bulanan">Bulanan</SelectItem>
                            <SelectItem value="3bulanan">3 Bulanan</SelectItem>
                            <SelectItem value="tahunan">Tahunan</SelectItem>
                            <SelectItem value="custom">Custom</SelectItem>
                        </SelectContent>
                    </Select>
                    {periode === 'custom' && (
                        <>
                            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                        </>
                    )}
                    <Button type="submit" variant="secondary">
                        Tampilkan
                    </Button>
                </form>

                <div className="rounded-md border p-4 text-sm">
                    Total check-in: <strong>{report.total_checkin}</strong>
                </div>

                <section className="grid gap-2">
                    <h2 className="font-medium">Per item</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3">Item</th>
                                    <th className="p-3">Berfungsi</th>
                                    <th className="p-3">Tidak</th>
                                    <th className="p-3">Tidak dicek</th>
                                    <th className="p-3">% Berfungsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.per_item.map((row) => (
                                    <tr key={row.nama_item} className="border-t">
                                        <td className="p-3">{row.nama_item}</td>
                                        <td className="p-3">{row.berfungsi}</td>
                                        <td className="p-3">{row.tidak_berfungsi}</td>
                                        <td className="p-3">{row.tidak_dicek}</td>
                                        <td className="p-3">
                                            {row.persen_berfungsi === null ? '–' : `${row.persen_berfungsi}%`}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-2">
                    <h2 className="font-medium">Per area</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3">Area</th>
                                    <th className="p-3">Check-in</th>
                                    <th className="p-3">Temuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.per_area.map((row) => (
                                    <tr key={row.patroli_area_id} className="border-t">
                                        <td className="p-3">{row.nama_area}</td>
                                        <td className="p-3">{row.total_checkin}</td>
                                        <td className="p-3">{row.tidak_berfungsi}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-2">
                    <h2 className="font-medium">Per ruang</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3">Lokasi</th>
                                    <th className="p-3">Check-in</th>
                                    <th className="p-3">Temuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.per_ruang.map((row) => (
                                    <tr key={row.patroli_ruang_id} className="border-t">
                                        <td className="p-3">
                                            <div className="font-medium">{row.nama_ruang}</div>
                                            <div className="text-muted-foreground">
                                                {row.nama_area}
                                                {row.kode ? ` · ${row.kode}` : ''}
                                            </div>
                                        </td>
                                        <td className="p-3">{row.total_checkin}</td>
                                        <td className="p-3">{row.tidak_berfungsi}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-2">
                    <h2 className="font-medium">Per template</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3">Template</th>
                                    <th className="p-3">Check-in</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.per_template.map((row) => (
                                    <tr key={row.patroli_template_id} className="border-t">
                                        <td className="p-3">{row.nama}</td>
                                        <td className="p-3">{row.total_checkin}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-2">
                    <h2 className="font-medium">Temuan tidak berfungsi</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="p-3">Waktu</th>
                                    <th className="p-3">Lokasi</th>
                                    <th className="p-3">Item</th>
                                    <th className="p-3">Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.temuan.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="p-6 text-center text-muted-foreground">
                                            Tidak ada temuan.
                                        </td>
                                    </tr>
                                ) : (
                                    report.temuan.map((row, index) => (
                                        <tr key={`${row.checkin_id}-${row.nama_item}-${index}`} className="border-t">
                                            <td className="p-3 whitespace-nowrap">{row.checked_at}</td>
                                            <td className="p-3">
                                                {row.nama_area} / {row.nama_ruang}
                                                {row.kode ? ` (${row.kode})` : ''}
                                            </td>
                                            <td className="p-3">{row.nama_item}</td>
                                            <td className="p-3">{row.petugas}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
