import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, QrCode } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    kode_aset: string;
    no_seri: string | null;
    nama_barang: string | null;
    nama_ruang: string | null;
};

type Props = {
    asets: Row[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Berhasil dibuat', href: '/aset/created' },
];

export default function AsetCreated({ asets }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aset berhasil dibuat" />
            <div className="mx-auto max-w-2xl space-y-6 px-4 sm:px-0">
                <header className="border-b border-border/70 pb-5">
                    <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-teal-700/10 text-teal-700 dark:text-teal-400">
                        <CheckCircle2 className="h-5 w-5" />
                    </div>
                    <h1 className="text-[1.75rem] font-semibold tracking-tight">
                        {asets.length} aset berhasil dibuat
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Tiap baris = satu unit fisik dengan kode & QR sendiri.
                    </p>
                </header>

                <ul className="divide-y overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    {asets.map((a) => (
                        <li key={a.id} className="flex items-center justify-between gap-3 px-4 py-3.5">
                            <div className="min-w-0">
                                <div className="font-mono text-sm font-semibold tracking-tight">{a.kode_aset}</div>
                                <div className="mt-0.5 truncate text-sm text-muted-foreground">
                                    {a.nama_barang ?? '-'} · {a.nama_ruang ?? '-'}
                                    {a.no_seri ? ` · SN ${a.no_seri}` : ' · serial belum diisi'}
                                </div>
                            </div>
                            <div className="flex shrink-0 gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/aset/${a.kode_aset}`}>Detail</Link>
                                </Button>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={`/aset/${a.kode_aset}/label-print`} target="_blank">
                                        <QrCode className="mr-1 h-3.5 w-3.5" />
                                        QR
                                    </Link>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>

                <div className="flex gap-2">
                    <Button asChild className="bg-teal-700 hover:bg-teal-800">
                        <Link href="/aset/create">Tambah lagi</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/aset">Kembali ke daftar</Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
