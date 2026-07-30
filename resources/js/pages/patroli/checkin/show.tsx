import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    checkin: {
        id: number;
        checked_at: string | null;
        catatan: string | null;
        kode: string | null;
        nama_ruang: string | null;
        nama_area: string | null;
        petugas: string | null;
        template: string | null;
        items: { id: number; nama_item: string; status: string }[];
    };
};

export default function PatroliCheckinShow({ checkin }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Check-in', href: '/patroli/checkin' },
        { title: `#${checkin.id}`, href: `/patroli/checkin/${checkin.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Check-in #${checkin.id}`} />
            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">{checkin.nama_ruang}</h1>
                        <p className="text-sm text-muted-foreground">
                            {checkin.nama_area}
                            {checkin.kode ? ` · ${checkin.kode}` : ''} · {checkin.checked_at} ·{' '}
                            {checkin.petugas} · {checkin.template}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/patroli/checkin">Kembali</Link>
                    </Button>
                </div>

                {checkin.catatan && (
                    <div className="rounded-md border p-3 text-sm">
                        <div className="mb-1 font-medium">Catatan</div>
                        <p className="text-muted-foreground">{checkin.catatan}</p>
                    </div>
                )}

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Item</th>
                                <th className="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {checkin.items.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3">{item.nama_item}</td>
                                    <td className="p-3">
                                        <Badge
                                            variant={
                                                item.status === 'tidak_berfungsi' ? 'destructive' : 'secondary'
                                            }
                                        >
                                            {item.status.replaceAll('_', ' ')}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
