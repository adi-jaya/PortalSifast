import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mode: 'harian' | 'mingguan';
    filters: { tanggal: string; mulai: string; selesai: string };
    data: {
        tanggal?: string;
        periode?: { mulai: string; selesai: string };
        rows: Array<Record<string, string | number | boolean>>;
        summary: Record<string, number>;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Driver', href: '/driver' },
    { title: 'Laporan', href: '/driver/laporan' },
];

function labelSummary(key: string): string {
    const map: Record<string, string> = {
        total_kendaraan: 'Total kendaraan',
        belum: 'Belum',
        sudah: 'Sudah',
        ada_temuan: 'Ada temuan',
        target_minimal: 'Target minimal',
        pemeriksaan_aktual: 'Pemeriksaan aktual',
        hari_terpenuhi: 'Hari terpenuhi',
        kepatuhan: 'Kepatuhan',
        pemeriksaan_ke_2: 'Pemeriksaan ke-2',
        hari_layak: 'Hari baik',
        temuan_tidak_layak: 'Temuan tidak baik',
    };

    return map[key] ?? key.replaceAll('_', ' ');
}

export default function DriverLaporan({ mode, filters, data }: Props) {
    const [tanggal, setTanggal] = useState(filters.tanggal);
    const [mulai, setMulai] = useState(filters.mulai);
    const [selesai, setSelesai] = useState(filters.selesai);
    const [currentMode, setCurrentMode] = useState(mode);

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/driver/laporan',
            currentMode === 'mingguan'
                ? { mode: 'mingguan', mulai, selesai }
                : { mode: 'harian', tanggal },
            { preserveState: true },
        );
    };

    const printUrl =
        currentMode === 'mingguan'
            ? `/driver/laporan/print?mode=mingguan&mulai=${mulai}&selesai=${selesai}`
            : `/driver/laporan/print?mode=harian&tanggal=${tanggal}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Checklist Kendaraan" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold">Laporan Checklist</h1>
                    <Button asChild variant="outline">
                        <a href={printUrl} target="_blank" rel="noreferrer">
                            Print
                        </a>
                    </Button>
                </div>

                <form onSubmit={apply} className="grid gap-3 rounded-lg border p-4 sm:grid-cols-5">
                    <div className="grid gap-1">
                        <Label>Mode</Label>
                        <select
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                            value={currentMode}
                            onChange={(e) => setCurrentMode(e.target.value as 'harian' | 'mingguan')}
                        >
                            <option value="harian">Harian</option>
                            <option value="mingguan">Mingguan</option>
                        </select>
                    </div>
                    {currentMode === 'harian' ? (
                        <div className="grid gap-1 sm:col-span-2">
                            <Label>Tanggal</Label>
                            <Input type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
                        </div>
                    ) : (
                        <>
                            <div className="grid gap-1">
                                <Label>Mulai</Label>
                                <Input type="date" value={mulai} onChange={(e) => setMulai(e.target.value)} />
                            </div>
                            <div className="grid gap-1">
                                <Label>Selesai</Label>
                                <Input type="date" value={selesai} onChange={(e) => setSelesai(e.target.value)} />
                            </div>
                        </>
                    )}
                    <div className="flex items-end">
                        <Button type="submit" className="w-full">
                            Tampilkan
                        </Button>
                    </div>
                </form>

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {Object.entries(data.summary).map(([key, value]) => (
                        <div key={key} className="rounded-md border p-3 text-sm">
                            <div className="text-muted-foreground">{labelSummary(key)}</div>
                            <div className="text-lg font-semibold">{value}</div>
                        </div>
                    ))}
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="min-w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                {mode === 'harian' ? (
                                    <>
                                        <th className="px-3 py-2 text-left">Kendaraan</th>
                                        <th className="px-3 py-2 text-left">Check 1</th>
                                        <th className="px-3 py-2 text-left">Check 2</th>
                                        <th className="px-3 py-2 text-left">Temuan</th>
                                        <th className="px-3 py-2 text-left">Status</th>
                                    </>
                                ) : (
                                    <>
                                        <th className="px-3 py-2 text-left">Kendaraan</th>
                                        <th className="px-3 py-2 text-left">Target</th>
                                        <th className="px-3 py-2 text-left">Aktual</th>
                                        <th className="px-3 py-2 text-left">Hari terpenuhi</th>
                                        <th className="px-3 py-2 text-left">Ke-2</th>
                                        <th className="px-3 py-2 text-left">Kepatuhan</th>
                                    </>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {data.rows.map((row) => (
                                <tr key={String(row.kendaraan_id)} className="border-t">
                                    {mode === 'harian' ? (
                                        <>
                                            <td className="px-3 py-2">{String(row.nama)}</td>
                                            <td className="px-3 py-2">{row.check_1 ? '✓' : '-'}</td>
                                            <td className="px-3 py-2">{row.check_2 ? '✓' : '-'}</td>
                                            <td className="px-3 py-2">{String(row.temuan)}</td>
                                            <td className="px-3 py-2">{String(row.status)}</td>
                                        </>
                                    ) : (
                                        <>
                                            <td className="px-3 py-2">{String(row.nama)}</td>
                                            <td className="px-3 py-2">{String(row.target)}</td>
                                            <td className="px-3 py-2">{String(row.aktual)}</td>
                                            <td className="px-3 py-2">{String(row.hari_terpenuhi)}</td>
                                            <td className="px-3 py-2">{String(row.pemeriksaan_ke_2)}</td>
                                            <td className="px-3 py-2">{String(row.kepatuhan)}%</td>
                                        </>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
