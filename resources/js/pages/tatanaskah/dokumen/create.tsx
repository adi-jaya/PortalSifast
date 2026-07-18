import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    PegawaiSearchSelect,
    type PegawaiOption,
} from '@/components/tatanaskah/pegawai-search-select';
import { Button } from '@/components/ui/button';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Tata Naskah', href: '/tatanaskah/dokumen' },
    { title: 'Buat Dokumen', href: '/tatanaskah/dokumen/create' },
];

type Option = { kode: string; nama: string; varian_kop?: string };
type UnitOption = { id: number; kode: string; nama: string; dep_id: string | null };
type SifatOption = { kode: string; nama: string };

type Props = {
    jenisOptions: Option[];
    unitOptions: UnitOption[];
    sifatOptions: SifatOption[];
};

export default function DokumenCreate({ jenisOptions, unitOptions, sifatOptions }: Props) {
    const [penandatangan, setPenandatangan] = useState<PegawaiOption | null>(null);

    const { data, setData, post, processing, errors } = useForm<{
        judul: string;
        kode_jenis: string;
        kode_unit_klasifikasi_id: string;
        kode_sifat: string;
        penandatangan_nik: string;
        penandatangan_nama: string;
        penandatangan_jabatan: string;
        tanggal_review: string;
        nomor_revisi: string;
        menimbang: string;
        mengingat: string;
        diktum: string;
        file: File | null;
    }>({
        judul: '',
        kode_jenis: 'SPO',
        kode_unit_klasifikasi_id: unitOptions[0] ? String(unitOptions[0].id) : '',
        kode_sifat: 'I',
        penandatangan_nik: '',
        penandatangan_nama: '',
        penandatangan_jabatan: '',
        tanggal_review: '',
        nomor_revisi: '00',
        menimbang: '',
        mengingat: '',
        diktum: '',
        file: null,
    });

    const handlePenandatanganChange = (option: PegawaiOption | null) => {
        setPenandatangan(option);
        setData({
            ...data,
            penandatangan_nik: option?.nik ?? '',
            penandatangan_nama: option?.nama ?? '',
            penandatangan_jabatan: option?.jbtn ?? '',
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/tatanaskah/dokumen', { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Dokumen — Tata Naskah" />

            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/tatanaskah/dokumen">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading
                        title="Buat Dokumen Baru"
                        description="Tentukan penandatangan — dokumen akan diajukan ke pejabat tersebut untuk disetujui"
                        variant="small"
                    />
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="max-w-2xl space-y-6 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="judul">
                            Judul <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="judul"
                            value={data.judul}
                            onChange={(e) => setData('judul', e.target.value)}
                        />
                        <InputError message={errors.judul} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="penandatangan_nik">
                            Yang menyetujui (penandatangan){' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <PegawaiSearchSelect
                            value={penandatangan}
                            onChange={handlePenandatanganChange}
                            placeholder="Cari nama pegawai — mis. Direktur..."
                        />
                        <p className="text-xs text-muted-foreground">
                            Dokumen ini akan diajukan ke pegawai yang dipilih. Sekretaris dapat
                            menyetujui atas nama penandatangan jika memegang akun Direktur.
                        </p>
                        <InputError message={errors.penandatangan_nik || errors.penandatangan_nama} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Jenis naskah</Label>
                            <Select value={data.kode_jenis} onValueChange={(v) => setData('kode_jenis', v)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {jenisOptions.map((j) => (
                                        <SelectItem key={j.kode} value={j.kode}>
                                            {j.kode} — {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.kode_jenis} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Unit klasifikasi</Label>
                            <Select
                                value={data.kode_unit_klasifikasi_id}
                                onValueChange={(v) => setData('kode_unit_klasifikasi_id', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {unitOptions.map((u) => (
                                        <SelectItem key={u.id} value={String(u.id)}>
                                            {u.kode} — {u.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.kode_unit_klasifikasi_id} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Sifat naskah</Label>
                            <Select value={data.kode_sifat} onValueChange={(v) => setData('kode_sifat', v)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sifatOptions.map((s) => (
                                        <SelectItem key={s.kode} value={s.kode}>
                                            {s.kode} — {s.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.kode_sifat} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="nomor_revisi">No. revisi (SPO)</Label>
                            <Input
                                id="nomor_revisi"
                                value={data.nomor_revisi}
                                onChange={(e) => setData('nomor_revisi', e.target.value)}
                                placeholder="00"
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="file">
                            File PDF <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="file"
                            type="file"
                            accept="application/pdf"
                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                        />
                        <p className="text-xs text-muted-foreground">
                            Export dari Word — belum perlu nomor resmi (DRAFT boleh).
                        </p>
                        <InputError message={errors.file} />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing || !data.penandatangan_nik}>
                            Simpan Draft
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/tatanaskah/dokumen">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
