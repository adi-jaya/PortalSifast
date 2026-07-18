import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowLeftRight, HandCoins, QrCode, Ticket } from 'lucide-react';
import { FormEvent, useRef } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

type Props = {
    aset: {
        id: number;
        kode_aset: string;
        no_simrs: string | null;
        kode_ruang_registrasi: string | null;
        tahun_registrasi: number | null;
        asal_barang: string | null;
        tanggal_pengadaan: string | null;
        harga: number | null;
        status_sumber: string | null;
        kondisi: string | null;
        siklus_hidup: string;
        status_ketersediaan?: string;
        photo_url: string | null;
        foto: { id: number; path: string; utama: boolean }[];
        barang: {
            id: number;
            kode_barang: string;
            nama_barang: string;
            kelas_aset: string | null;
            wajib_kalibrasi: boolean;
            umur_ekonomis_bulan: number | null;
        } | null;
        ruang: { id: number; kode_ruang: string; nama_ruang: string } | null;
    };
    tickets: { id: number; ticket_number: string; title: string; status: string | null; created_at: string | null }[];
    peminjamanRiwayat?: {
        id: number;
        nomor: string;
        status: string;
        is_terlambat: boolean;
        tanggal_pinjam: string | null;
    }[];
    mutasiRiwayat?: {
        id: number;
        nomor: string;
        tanggal_mutasi: string | null;
        ruang_asal: string | null;
        ruang_tujuan: string | null;
    }[];
    ruangOptions: { id: number; kode_ruang: string; nama_ruang: string }[];
};

export default function AsetShow({
    aset,
    tickets,
    peminjamanRiwayat = [],
    mutasiRiwayat = [],
    ruangOptions,
}: Props) {
    const tersedia = (aset.status_ketersediaan ?? 'tersedia') === 'tersedia';
    const fileRef = useRef<HTMLInputElement>(null);
    const fotoForm = useForm<{ foto: File | null }>({ foto: null });
    const verifikasi = useForm({
        aset_ruang_id: aset.ruang?.id ? String(aset.ruang.id) : '',
        tahun_registrasi: String(aset.tahun_registrasi ?? new Date().getFullYear()),
        kondisi: aset.kondisi ?? 'Ada',
        kelas_aset: aset.barang?.kelas_aset ?? '',
        wajib_kalibrasi: Boolean(aset.barang?.wajib_kalibrasi),
        umur_ekonomis_bulan: aset.barang?.umur_ekonomis_bulan
            ? String(aset.barang.umur_ekonomis_bulan)
            : '',
        regenerate_kode: false,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Aset', href: '/aset' },
        { title: aset.kode_aset, href: `/aset/${aset.kode_aset}` },
    ];

    const submitFoto = (e: FormEvent) => {
        e.preventDefault();
        fotoForm.post(`/aset/${aset.kode_aset}/foto`, {
            forceFormData: true,
            onSuccess: () => {
                fotoForm.reset();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    const submitVerifikasi = (e: FormEvent) => {
        e.preventDefault();
        verifikasi.transform((data) => ({
            ...data,
            aset_ruang_id: Number(data.aset_ruang_id),
            tahun_registrasi: Number(data.tahun_registrasi),
            umur_ekonomis_bulan: data.umur_ekonomis_bulan
                ? Number(data.umur_ekonomis_bulan)
                : null,
            kelas_aset: data.kelas_aset || null,
        }));
        verifikasi.post(`/aset/${aset.kode_aset}/verifikasi`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={aset.kode_aset} />
            <div className="flex flex-col gap-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/aset"><ArrowLeft className="h-4 w-4" /></Link>
                        </Button>
                        <div>
                            <h1 className="font-mono text-xl font-semibold">{aset.kode_aset}</h1>
                            <p className="text-sm text-muted-foreground">{aset.barang?.nama_barang}</p>
                            <div className="mt-1 flex flex-wrap gap-2">
                                <Badge variant="outline" className="capitalize">{aset.siklus_hidup}</Badge>
                                <Badge variant={tersedia ? 'secondary' : 'destructive'} className="capitalize">
                                    {aset.status_ketersediaan ?? 'tersedia'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a href={`/aset/${aset.kode_aset}/label-print`} target="_blank" rel="noreferrer">
                                <QrCode className="mr-2 h-4 w-4" /> Label QR
                            </a>
                        </Button>
                        {tersedia ? (
                            <Button variant="outline" asChild>
                                <Link href={`/aset-peminjaman/create?aset_id=${aset.id}`}>
                                    <HandCoins className="mr-2 h-4 w-4" /> Pinjamkan
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" disabled>
                                <HandCoins className="mr-2 h-4 w-4" /> Pinjamkan
                            </Button>
                        )}
                        {tersedia ? (
                            <Button variant="outline" asChild>
                                <Link href={`/aset-mutasi-lokasi/create?aset_id=${aset.id}`}>
                                    <ArrowLeftRight className="mr-2 h-4 w-4" /> Mutasi
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" disabled>
                                <ArrowLeftRight className="mr-2 h-4 w-4" /> Mutasi
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={`/tickets/create?asset_id=${aset.id}`}>
                                <Ticket className="mr-2 h-4 w-4" /> Buat tiket
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/aset/${aset.kode_aset}/edit`}>Edit</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Detail</CardTitle></CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2 text-sm">
                            <div><p className="text-muted-foreground">No SIMRS</p><p>{aset.no_simrs ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Siklus</p><p>{aset.siklus_hidup}</p></div>
                            <div><p className="text-muted-foreground">Ketersediaan</p><p className="capitalize">{aset.status_ketersediaan ?? 'tersedia'}</p></div>
                            <div><p className="text-muted-foreground">Ruang</p><p>{aset.ruang?.nama_ruang ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Tahun registrasi</p><p>{aset.tahun_registrasi ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Kelas</p><p>{aset.barang?.kelas_aset ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Wajib kalibrasi</p><p>{aset.barang?.wajib_kalibrasi ? 'Ya' : 'Tidak'}</p></div>
                            <div><p className="text-muted-foreground">Umur ekonomis (bulan)</p><p>{aset.barang?.umur_ekonomis_bulan ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Kondisi</p><p>{aset.kondisi ?? '–'}</p></div>
                            <div><p className="text-muted-foreground">Status sumber SIMRS</p><p>{aset.status_sumber ?? '–'}</p></div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Foto</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {aset.photo_url ? (
                                <img src={aset.photo_url} alt="" className="max-h-48 w-full rounded-lg border object-contain" />
                            ) : (
                                <p className="text-sm text-muted-foreground">Belum ada foto.</p>
                            )}
                            <form onSubmit={submitFoto} className="space-y-2">
                                <Input
                                    ref={fileRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => fotoForm.setData('foto', e.target.files?.[0] ?? null)}
                                />
                                <InputError message={fotoForm.errors.foto} />
                                <Button type="submit" disabled={fotoForm.processing || !fotoForm.data.foto} className="w-full">
                                    Unggah foto
                                </Button>
                            </form>
                            {aset.foto.map((f) => (
                                <Button
                                    key={f.id}
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    onClick={() => {
                                        if (confirm('Hapus foto ini?')) {
                                            router.delete(`/aset/${aset.kode_aset}/foto/${f.id}`);
                                        }
                                    }}
                                >
                                    Hapus foto #{f.id}
                                </Button>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                {aset.siklus_hidup === 'draf' && (
                    <Card>
                        <CardHeader><CardTitle>Verifikasi inventarisasi ulang</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={submitVerifikasi} className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label>Ruang</Label>
                                    <Select
                                        value={verifikasi.data.aset_ruang_id}
                                        onValueChange={(v) => verifikasi.setData('aset_ruang_id', v)}
                                    >
                                        <SelectTrigger><SelectValue placeholder="Pilih ruang" /></SelectTrigger>
                                        <SelectContent>
                                            {ruangOptions.map((r) => (
                                                <SelectItem key={r.id} value={String(r.id)}>{r.nama_ruang} ({r.kode_ruang})</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Tahun registrasi</Label>
                                    <Input
                                        value={verifikasi.data.tahun_registrasi}
                                        onChange={(e) => verifikasi.setData('tahun_registrasi', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label>Kelas aset</Label>
                                    <Select
                                        value={verifikasi.data.kelas_aset || '__none__'}
                                        onValueChange={(v) => verifikasi.setData('kelas_aset', v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger><SelectValue placeholder="Pilih" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">Belum diisi</SelectItem>
                                            <SelectItem value="medis">Medis</SelectItem>
                                            <SelectItem value="non_medis">Non-medis</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Umur ekonomis (bulan)</Label>
                                    <Input
                                        value={verifikasi.data.umur_ekonomis_bulan}
                                        onChange={(e) => verifikasi.setData('umur_ekonomis_bulan', e.target.value)}
                                    />
                                </div>
                                <div className="flex items-center gap-2 sm:col-span-2">
                                    <Checkbox
                                        checked={verifikasi.data.wajib_kalibrasi}
                                        onCheckedChange={(c) => verifikasi.setData('wajib_kalibrasi', Boolean(c))}
                                        id="wajib_kalibrasi"
                                    />
                                    <Label htmlFor="wajib_kalibrasi">Wajib kalibrasi (alat medis)</Label>
                                </div>
                                <div className="sm:col-span-2">
                                    <Button type="submit" disabled={verifikasi.processing}>
                                        Finalisasi & aktifkan aset
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Riwayat peminjaman</CardTitle></CardHeader>
                        <CardContent>
                            {peminjamanRiwayat.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada peminjaman.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {peminjamanRiwayat.map((p) => (
                                        <li key={p.id} className="flex items-center justify-between gap-2">
                                            <Link href={`/aset-peminjaman/${p.id}`} className="font-mono text-primary hover:underline">
                                                {p.nomor}
                                            </Link>
                                            <Badge variant={p.is_terlambat ? 'destructive' : 'secondary'}>
                                                {p.is_terlambat ? 'terlambat' : p.status}
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Riwayat mutasi</CardTitle></CardHeader>
                        <CardContent>
                            {mutasiRiwayat.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada mutasi.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {mutasiRiwayat.map((m) => (
                                        <li key={m.id}>
                                            <Link href={`/aset-mutasi-lokasi/${m.id}`} className="font-mono text-primary hover:underline">
                                                {m.nomor}
                                            </Link>
                                            <span className="text-muted-foreground">
                                                {' '}{m.ruang_asal} → {m.ruang_tujuan}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>Tiket terkait</CardTitle></CardHeader>
                    <CardContent>
                        {tickets.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Belum ada tiket.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {tickets.map((t) => (
                                    <li key={t.id}>
                                        <Link href={`/tickets/${t.id}`} className="font-mono text-primary hover:underline">
                                            {t.ticket_number}
                                        </Link>
                                        {' — '}{t.title}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
