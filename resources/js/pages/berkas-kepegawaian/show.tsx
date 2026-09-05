import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Trash2, Upload } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Pegawai = {
    nik: string;
    nama: string;
    jbtn: string | null;
    departemen: string | null;
};

type BerkasItem = {
    kode_berkas: string;
    nama_berkas: string;
    kategori: string;
    no_urut: number;
    tgl_uploud: string | null;
    berkas: string | null;
    public_url: string | null;
};

type Section = {
    kategori: string;
    items: BerkasItem[];
};

type MasterOption = {
    kode: string;
    nama_berkas: string;
    kategori: string;
};

type Props = {
    pegawai: Pegawai;
    sections: Section[];
    masterOptions: MasterOption[];
    uploadedKodes: string[];
};

type Flash = { success?: string; error?: string };

export default function BerkasKepegawaianShow({
    pegawai,
    sections,
    masterOptions,
    uploadedKodes,
}: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [replacingKode, setReplacingKode] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
        {
            title: pegawai.nama,
            href: `/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}`,
        },
    ];

    const availableMasters = useMemo(
        () => masterOptions.filter((m) => !uploadedKodes.includes(m.kode)),
        [masterOptions, uploadedKodes],
    );

    const uploadForm = useForm({
        kode_berkas: availableMasters[0]?.kode ?? '',
        tgl_uploud: new Date().toISOString().slice(0, 10),
        dokumen: null as File | null,
    });

    const replaceForm = useForm({
        tgl_uploud: new Date().toISOString().slice(0, 10),
        dokumen: null as File | null,
    });

    const submitUpload = (e: FormEvent) => {
        e.preventDefault();
        uploadForm.post(`/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadForm.setData('dokumen', null);
                uploadForm.clearErrors();
            },
        });
    };

    const openReplace = (item: BerkasItem) => {
        setReplacingKode(item.kode_berkas);
        replaceForm.setData({
            tgl_uploud: new Date().toISOString().slice(0, 10),
            dokumen: null,
        });
        replaceForm.clearErrors();
    };

    const submitReplace = (e: FormEvent) => {
        e.preventDefault();
        if (!replacingKode) {
            return;
        }

        replaceForm.post(
            `/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}/${encodeURIComponent(replacingKode)}/replace`,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setReplacingKode(null);
                    replaceForm.reset();
                },
            },
        );
    };

    const destroyBerkas = (item: BerkasItem) => {
        if (!confirm(`Hapus berkas "${item.nama_berkas}"?`)) {
            return;
        }

        router.delete(
            `/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}/${encodeURIComponent(item.kode_berkas)}`,
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Berkas — ${pegawai.nama}`} />

            <div className="flex flex-col gap-4">
                <div className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/berkas-kepegawaian">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Heading
                        title={pegawai.nama}
                        description={`NIK ${pegawai.nik}${pegawai.jbtn ? ` · ${pegawai.jbtn}` : ''}${pegawai.departemen ? ` · ${pegawai.departemen}` : ''}`}
                        variant="small"
                    />
                </div>

                {flash.success ? (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash.success}
                    </div>
                ) : null}

                {flash.error ? (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {flash.error}
                    </div>
                ) : null}

                <form
                    onSubmit={submitUpload}
                    className="space-y-4 rounded-xl border bg-card p-4"
                >
                    <div>
                        <h2 className="text-base font-semibold">Unggah berkas baru</h2>
                        <p className="text-sm text-muted-foreground">
                            PDF / JPG / JPEG, maksimal 10 MB.
                        </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="kode_berkas">Jenis berkas</Label>
                            <select
                                id="kode_berkas"
                                value={uploadForm.data.kode_berkas}
                                onChange={(e) =>
                                    uploadForm.setData('kode_berkas', e.target.value)
                                }
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                disabled={availableMasters.length === 0}
                            >
                                {availableMasters.length === 0 ? (
                                    <option value="">Semua jenis sudah diunggah</option>
                                ) : (
                                    availableMasters.map((m) => (
                                        <option key={m.kode} value={m.kode}>
                                            {m.nama_berkas} ({m.kategori})
                                        </option>
                                    ))
                                )}
                            </select>
                            <InputError message={uploadForm.errors.kode_berkas} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="tgl_uploud">Tanggal upload</Label>
                            <Input
                                id="tgl_uploud"
                                type="date"
                                value={uploadForm.data.tgl_uploud}
                                onChange={(e) =>
                                    uploadForm.setData('tgl_uploud', e.target.value)
                                }
                            />
                            <InputError message={uploadForm.errors.tgl_uploud} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="dokumen">File</Label>
                            <Input
                                id="dokumen"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg"
                                onChange={(e) =>
                                    uploadForm.setData(
                                        'dokumen',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="cursor-pointer"
                            />
                            <InputError message={uploadForm.errors.dokumen} />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        disabled={
                            uploadForm.processing ||
                            availableMasters.length === 0 ||
                            !uploadForm.data.dokumen
                        }
                    >
                        <Upload className="mr-2 h-4 w-4" />
                        {uploadForm.processing ? 'Mengunggah...' : 'Unggah'}
                    </Button>
                </form>

                {sections.length === 0 ? (
                    <div className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
                        Belum ada berkas untuk pegawai ini.
                    </div>
                ) : (
                    sections.map((section) => (
                        <div
                            key={section.kategori}
                            className="space-y-3 rounded-xl border bg-card p-4"
                        >
                            <h2 className="text-base font-semibold">{section.kategori}</h2>
                            <div className="data-table">
                                <div className="data-table-scroll">
                                    <table className="w-full text-left text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="px-4 py-3 font-medium">
                                                    Jenis
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Tanggal
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Preview
                                                </th>
                                                <th className="px-4 py-3 font-medium">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {section.items.map((item) => (
                                                <tr
                                                    key={item.kode_berkas}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-4 py-3 font-medium">
                                                        {item.nama_berkas}
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.kode_berkas}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {item.tgl_uploud ?? '–'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {item.public_url ? (
                                                            <a
                                                                href={item.public_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-sm text-teal-700 hover:underline dark:text-teal-300"
                                                            >
                                                                Buka
                                                                <ExternalLink className="h-3.5 w-3.5" />
                                                            </a>
                                                        ) : (
                                                            <span className="text-muted-foreground">
                                                                –
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => openReplace(item)}
                                                            >
                                                                Ganti
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="destructive"
                                                                onClick={() => destroyBerkas(item)}
                                                            >
                                                                <Trash2 className="mr-1 h-3.5 w-3.5" />
                                                                Hapus
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    ))
                )}

                {replacingKode ? (
                    <form
                        onSubmit={submitReplace}
                        className="space-y-4 rounded-xl border border-amber-500/30 bg-card p-4"
                    >
                        <div>
                            <h2 className="text-base font-semibold">
                                Ganti berkas ({replacingKode})
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                File lama akan diganti di webapps setelah unggah berhasil.
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="replace_tgl">Tanggal upload</Label>
                                <Input
                                    id="replace_tgl"
                                    type="date"
                                    value={replaceForm.data.tgl_uploud}
                                    onChange={(e) =>
                                        replaceForm.setData('tgl_uploud', e.target.value)
                                    }
                                />
                                <InputError message={replaceForm.errors.tgl_uploud} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="replace_dokumen">File baru</Label>
                                <Input
                                    id="replace_dokumen"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg"
                                    onChange={(e) =>
                                        replaceForm.setData(
                                            'dokumen',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="cursor-pointer"
                                />
                                <InputError message={replaceForm.errors.dokumen} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={
                                    replaceForm.processing || !replaceForm.data.dokumen
                                }
                            >
                                {replaceForm.processing ? 'Mengganti...' : 'Simpan ganti'}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setReplacingKode(null)}
                            >
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : null}
            </div>
        </AppLayout>
    );
}
