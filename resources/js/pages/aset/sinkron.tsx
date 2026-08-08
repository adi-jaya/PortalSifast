import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Run = {
    id: number;
    status: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    jumlah_baru: number;
    jumlah_berubah: number;
    jumlah_sama: number;
    jumlah_hilang: number;
    jumlah_gagal: number;
    pemicu?: { id: number; name: string } | null;
};

type Props = {
    runs: Run[];
    terakhir: Run | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Sinkron', href: '/aset/sinkron' },
];

export default function AsetSinkronPage({ runs, terakhir }: Props) {
    const page = usePage().props as {
        flash?: { success?: string; sinkron_preview?: Record<string, number> };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sinkron Aset SIMRS" />
            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/aset"><ArrowLeft className="h-4 w-4" /></Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Sinkron dari SIMRS</h1>
                        <p className="text-sm text-muted-foreground">
                            Read-only dari SIMRS. Nomor custom, foto, dan klasifikasi portal tidak ditimpa.
                        </p>
                    </div>
                </div>

                {page.flash?.success && (
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm">
                        {page.flash.success}
                    </div>
                )}

                {page.flash?.sinkron_preview && (
                    <Card>
                        <CardHeader><CardTitle>Hasil preview</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-5">
                            {Object.entries(page.flash.sinkron_preview)
                                .filter(([k]) => k.startsWith('jumlah_'))
                                .map(([k, v]) => (
                                    <div key={k} className="rounded-lg border p-2">
                                        <p className="text-xs text-muted-foreground">{k.replace('jumlah_', '')}</p>
                                        <p className="text-lg font-semibold">{String(v)}</p>
                                    </div>
                                ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        onClick={() => router.post('/aset/sinkron/preview')}
                    >
                        Preview
                    </Button>
                    <Button
                        onClick={() => {
                            if (confirm('Terapkan sinkron sekarang? Ini menulis ke database portal.')) {
                                router.post('/aset/sinkron/apply');
                            }
                        }}
                    >
                        Terapkan sinkron
                    </Button>
                </div>

                {terakhir && (
                    <p className="text-sm text-muted-foreground">
                        Terakhir: #{terakhir.id} — {terakhir.status}
                        {terakhir.selesai_pada ? ` (${terakhir.selesai_pada})` : ''}
                    </p>
                )}

                <Card>
                    <CardHeader><CardTitle>Riwayat sinkron</CardTitle></CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="py-2">ID</th>
                                    <th>Status</th>
                                    <th>Baru</th>
                                    <th>Berubah</th>
                                    <th>Sama</th>
                                    <th>Hilang</th>
                                    <th>Pemacu</th>
                                </tr>
                            </thead>
                            <tbody>
                                {runs.map((run) => (
                                    <tr key={run.id} className="border-b last:border-0">
                                        <td className="py-2">{run.id}</td>
                                        <td>{run.status}</td>
                                        <td>{run.jumlah_baru}</td>
                                        <td>{run.jumlah_berubah}</td>
                                        <td>{run.jumlah_sama}</td>
                                        <td>{run.jumlah_hilang}</td>
                                        <td>{run.pemicu?.name ?? '–'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
