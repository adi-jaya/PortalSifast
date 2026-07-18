import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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

type AuditRow = {
    id: number;
    judul: string | null;
    status: string;
    dimulai_pada: string | null;
    selesai_pada: string | null;
    items_count: number;
    belum_dicek_count: number;
    ditemukan_count: number;
    tidak_ditemukan_count: number;
    salah_ruang_count: number;
    ruang?: { id: number; kode_ruang: string; nama_ruang: string } | null;
    pemula?: { id: number; name: string } | null;
};

type Props = {
    audits: {
        data: AuditRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    ruang: { id: number; kode_ruang: string; nama_ruang: string }[];
    filters: { status?: string; aset_ruang_id?: number | null };
    stats: {
        berjalan: number;
        selesai: number;
        disetujui: number;
        belum_pernah_diaudit: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Audit Fisik', href: '/aset/audit' },
];

function statusTone(status: string): string {
    switch (status) {
        case 'berjalan':
            return 'bg-sky-500/15 text-sky-900 ring-sky-500/25';
        case 'selesai':
            return 'bg-amber-500/15 text-amber-900 ring-amber-500/25';
        case 'disetujui':
            return 'bg-emerald-500/15 text-emerald-900 ring-emerald-500/25';
        default:
            return 'bg-muted text-muted-foreground ring-border';
    }
}

export default function AuditAsetIndex({ audits, ruang, filters, stats }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [status, setStatus] = useState(filters.status || '__all__');
    const [ruangId, setRuangId] = useState(
        filters.aset_ruang_id ? String(filters.aset_ruang_id) : '__all__',
    );

    const apply = () => {
        router.get(
            '/aset/audit',
            {
                status: status === '__all__' ? undefined : status,
                aset_ruang_id: ruangId === '__all__' ? undefined : ruangId,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Fisik Aset" />
            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Audit Fisik</h1>
                        <p className="text-sm text-muted-foreground">
                            Checklist multi-user persisten — hasil tidak pernah ditulis ke SIMRS.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/aset/audit/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Mulai audit
                        </Link>
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm">
                        {flash.success}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {[
                        ['Berjalan', stats.berjalan],
                        ['Selesai', stats.selesai],
                        ['Disetujui', stats.disetujui],
                        ['Belum pernah diaudit', stats.belum_pernah_diaudit],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg border px-3 py-2">
                            <div className="text-xs text-muted-foreground">{label}</div>
                            <div className="text-xl font-semibold">{value}</div>
                        </div>
                    ))}
                </div>

                <div className="flex flex-wrap gap-2">
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[160px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua status</SelectItem>
                            <SelectItem value="berjalan">Berjalan</SelectItem>
                            <SelectItem value="selesai">Selesai</SelectItem>
                            <SelectItem value="disetujui">Disetujui</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={ruangId} onValueChange={setRuangId}>
                        <SelectTrigger className="w-[220px]">
                            <SelectValue placeholder="Ruang" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua ruang</SelectItem>
                            {ruang.map((r) => (
                                <SelectItem key={r.id} value={String(r.id)}>
                                    {r.nama_ruang}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="secondary" onClick={apply}>
                        Filter
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2 font-medium">Judul</th>
                                <th className="px-3 py-2 font-medium">Ruang</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                                <th className="px-3 py-2 font-medium">Progress</th>
                                <th className="px-3 py-2 font-medium">Selisih</th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {audits.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-3 py-8 text-center text-muted-foreground">
                                        Belum ada sesi audit. Mulai audit per ruang.
                                    </td>
                                </tr>
                            )}
                            {audits.data.map((audit) => (
                                <tr key={audit.id} className="border-t">
                                    <td className="px-3 py-2">
                                        <div className="font-medium">{audit.judul}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {audit.pemula?.name ?? '—'}
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">{audit.ruang?.nama_ruang ?? '—'}</td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs ring-1 ${statusTone(audit.status)}`}
                                        >
                                            {audit.status}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        {audit.items_count - audit.belum_dicek_count}/{audit.items_count}
                                    </td>
                                    <td className="px-3 py-2 text-xs">
                                        Hilang {audit.tidak_ditemukan_count} · Salah ruang{' '}
                                        {audit.salah_ruang_count}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={`/aset/audit/${audit.id}`}>Buka</Link>
                                        </Button>
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
