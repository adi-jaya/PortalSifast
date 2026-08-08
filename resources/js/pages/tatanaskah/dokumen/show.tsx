import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AuditItem = {
    id: number;
    aksi: string;
    status_lama: string | null;
    status_baru: string | null;
    catatan: string | null;
    created_at: string;
    user?: { name: string };
};

type DokumenDetail = {
    id: number;
    judul: string;
    nomor_dokumen: string | null;
    kode_jenis: string;
    status: string;
    tanggal_hijriyah: string | null;
    tanggal_ditetapkan: string | null;
    jenis?: { kode: string; nama: string };
    unit_klasifikasi?: { kode: string; nama: string };
    sifat?: { kode: string; nama: string };
    pembuat?: { name: string };
    penandatangan_nama: string | null;
    penandatangan_jabatan: string | null;
    penandatangan_nik: string | null;
    penyetuju?: { name: string } | null;
    meta_regulasi?: { nomor_revisi: string | null };
    audit?: AuditItem[];
};

type Props = {
    dokumen: DokumenDetail;
    pdfUrl: string | null;
    nextStatuses: { value: string; label: string }[];
    can: { update: boolean; transition: boolean; delete: boolean };
    isPenandatangan: boolean;
    signingCapabilities: { cert: boolean; image: boolean; timestamp: boolean };
    certSigningEnabled: boolean;
    stirlingHealthy: boolean;
};

export default function DokumenShow({
    dokumen,
    pdfUrl,
    nextStatuses,
    can,
    isPenandatangan,
    signingCapabilities,
    certSigningEnabled,
    stirlingHealthy,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Tata Naskah', href: '/tatanaskah/dokumen' },
        { title: dokumen.judul, href: `/tatanaskah/dokumen/${dokumen.id}` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        status: nextStatuses[0]?.value ?? '',
        catatan: '',
    });

    const handleTransition = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/tatanaskah/dokumen/${dokumen.id}/transition`, { preserveScroll: true });
    };

    const handleDelete = () => {
        if (confirm('Hapus draft dokumen ini?')) {
            router.delete(`/tatanaskah/dokumen/${dokumen.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={dokumen.judul} />

            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/tatanaskah/dokumen">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <Heading title={dokumen.judul} variant="small" />
                        <div className="mt-2 flex flex-wrap gap-2">
                            <Badge>{dokumen.kode_jenis}</Badge>
                            <Badge variant="outline">{dokumen.status}</Badge>
                            {dokumen.nomor_dokumen && (
                                <Badge variant="secondary" className="font-mono text-xs">
                                    {dokumen.nomor_dokumen}
                                </Badge>
                            )}
                        </div>
                    </div>
                    {can.delete && (
                        <Button variant="destructive" size="sm" onClick={handleDelete}>
                            Hapus Draft
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border bg-card p-4">
                        <h3 className="font-medium">Informasi</h3>
                        <dl className="grid gap-2 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Unit</dt>
                                <dd>{dokumen.unit_klasifikasi?.kode}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Sifat</dt>
                                <dd>{dokumen.sifat?.kode}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Pembuat</dt>
                                <dd>{dokumen.pembuat?.name}</dd>
                            </div>
                            {dokumen.penandatangan_nama && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Penandatangan</dt>
                                    <dd className="text-right">
                                        <span className="block">{dokumen.penandatangan_nama}</span>
                                        {dokumen.penandatangan_jabatan && (
                                            <span className="text-xs text-muted-foreground">
                                                {dokumen.penandatangan_jabatan}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            )}
                            {dokumen.penyetuju?.name && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Disetujui oleh</dt>
                                    <dd>{dokumen.penyetuju.name}</dd>
                                </div>
                            )}
                            {dokumen.meta_regulasi?.nomor_revisi && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">No. Revisi</dt>
                                    <dd>{dokumen.meta_regulasi.nomor_revisi}</dd>
                                </div>
                            )}
                            {dokumen.tanggal_hijriyah && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Tanggal Hijriyah</dt>
                                    <dd>{dokumen.tanggal_hijriyah}</dd>
                                </div>
                            )}
                        </dl>

                        {can.transition && nextStatuses.length > 0 && (
                            <form
                                onSubmit={handleTransition}
                                className={`space-y-3 border-t pt-4 ${
                                    dokumen.status === 'menunggu_tte' && isPenandatangan
                                        ? 'rounded-lg border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20'
                                        : ''
                                }`}
                            >
                                <h3 className="font-medium">
                                    {dokumen.status === 'draft'
                                        ? 'Ajukan ke Direktur'
                                        : dokumen.status === 'menunggu_tte' && isPenandatangan
                                          ? 'Tanda Tangan & Persetujuan'
                                          : dokumen.status === 'menunggu_tte'
                                            ? 'Persetujuan Penandatangan'
                                            : 'Ubah Status'}
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    {dokumen.status === 'draft' &&
                                        'Setelah diajukan, nomor resmi akan digenerate otomatis.'}
                                    {dokumen.status === 'menunggu_tte' && isPenandatangan &&
                                        (certSigningEnabled
                                            ? 'Tinjau PDF. Setujui akan: tempel TTD (jika ada) → tanda tangan sertifikat digital → timestamp RFC 3161 → simpan file final.'
                                            : 'Tinjau PDF di sebelah kanan. Setujui akan menyimpan salinan final (sertifikat digital belum diaktifkan di server).')}
                                    {dokumen.status === 'menunggu_tte' && !isPenandatangan &&
                                        `Menunggu persetujuan ${dokumen.penandatangan_nama ?? 'penandatangan'}.`}
                                </p>
                                <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {nextStatuses.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <div className="grid gap-2">
                                    <Label htmlFor="catatan">Catatan (opsional)</Label>
                                    <Textarea
                                        id="catatan"
                                        value={data.catatan}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        rows={2}
                                    />
                                </div>
                                <InputError message={errors.status} />
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    variant={
                                        dokumen.status === 'menunggu_tte' && isPenandatangan
                                            ? 'default'
                                            : 'default'
                                    }
                                    className={
                                        dokumen.status === 'menunggu_tte' && isPenandatangan
                                            ? 'w-full sm:w-auto'
                                            : undefined
                                    }
                                >
                                    {dokumen.status === 'draft'
                                        ? 'Ajukan ke Direktur'
                                        : dokumen.status === 'menunggu_tte'
                                          ? 'Setujui & Aktifkan'
                                          : 'Proses'}
                                </Button>
                            </form>
                        )}

                        {!stirlingHealthy && (
                            <p className="text-xs text-amber-600">
                                Stirling-PDF tidak terdeteksi — proses PDF otomatis dilewati.
                            </p>
                        )}
                        {dokumen.status === 'menunggu_tte' && isPenandatangan && (
                            <ul className="list-inside list-disc text-xs text-muted-foreground">
                                <li>
                                    Sertifikat digital:{' '}
                                    {signingCapabilities.cert ? 'aktif' : 'belum dikonfigurasi'}
                                </li>
                                <li>
                                    Gambar TTD:{' '}
                                    {signingCapabilities.image ? 'aktif' : 'belum ada file PNG'}
                                </li>
                                <li>
                                    Timestamp RFC 3161:{' '}
                                    {signingCapabilities.timestamp ? 'aktif' : 'nonaktif'}
                                </li>
                            </ul>
                        )}
                    </div>

                    <div className="rounded-xl border bg-card p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <h3 className="font-medium">PDF</h3>
                            {pdfUrl && (
                                <Button variant="outline" size="sm" asChild>
                                    <a href={pdfUrl} target="_blank" rel="noreferrer">
                                        Buka / Unduh
                                    </a>
                                </Button>
                            )}
                        </div>
                        {pdfUrl ? (
                            <iframe
                                src={pdfUrl}
                                title="Preview PDF"
                                className="h-[480px] w-full rounded-md border"
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">Belum ada file PDF.</p>
                        )}
                    </div>
                </div>

                {dokumen.audit && dokumen.audit.length > 0 && (
                    <div className="rounded-xl border bg-card p-4">
                        <h3 className="mb-3 font-medium">Riwayat</h3>
                        <ul className="space-y-2 text-sm">
                            {dokumen.audit.map((a) => (
                                <li key={a.id} className="border-b pb-2 last:border-0">
                                    <span className="font-medium">{a.user?.name}</span> — {a.aksi}
                                    {a.status_baru && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            → {a.status_baru}
                                        </span>
                                    )}
                                    {a.catatan && (
                                        <p className="text-muted-foreground">{a.catatan}</p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
