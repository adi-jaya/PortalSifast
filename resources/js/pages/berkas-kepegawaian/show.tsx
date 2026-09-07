import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Trash2, Upload } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { RiwayatEditor, type Riwayat } from '@/pages/berkas-kepegawaian/partials/riwayat-editor';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Pegawai = {
    nik: string;
    nama: string;
    jk: string | null;
    jbtn: string | null;
    bidang: string | null;
    departemen: string | null;
    departemen_nama: string | null;
    stts_kerja: string | null;
    stts_kerja_label: string | null;
    stts_wp: string | null;
    stts_wp_label: string | null;
    pendidikan: string | null;
    jnj_jabatan: string | null;
    jnj_jabatan_nama: string | null;
    kode_kelompok: string | null;
    kelompok_jabatan_nama: string | null;
    mulai_kerja: string | null;
    stts_aktif: string | null;
    no_ktp: string | null;
    tmp_lahir: string | null;
    tgl_lahir: string | null;
    alamat: string | null;
    kota: string | null;
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
    riwayat: Riwayat;
    sections: Section[];
    masterOptions: MasterOption[];
    masterOptionsAll: MasterOption[];
    berkasKategori: string;
    uploadedKodes: string[];
    referensiOptions: {
        bidang: SearchSelectOption[];
        departemen: SearchSelectOption[];
        stts_kerja: SearchSelectOption[];
        stts_wp: SearchSelectOption[];
        pendidikan: SearchSelectOption[];
        jnj_jabatan: SearchSelectOption[];
        kelompok_jabatan: SearchSelectOption[];
    };
};

type Flash = { success?: string; error?: string };
type TabKey = 'berkas' | 'riwayat';

function dash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

function dateInputValue(value: string | null | undefined): string {
    return (value ?? '').toString().slice(0, 10);
}

function profilFormDefaults(pegawai: Pegawai) {
    return {
        nama: pegawai.nama ?? '',
        jk: pegawai.jk ?? 'Pria',
        jbtn: pegawai.jbtn ?? '',
        bidang: pegawai.bidang ?? '',
        departemen: pegawai.departemen ?? '',
        stts_kerja: pegawai.stts_kerja ?? '',
        stts_wp: pegawai.stts_wp ?? '',
        pendidikan: pegawai.pendidikan ?? '',
        jnj_jabatan: pegawai.jnj_jabatan ?? '',
        kode_kelompok: pegawai.kode_kelompok ?? '',
        mulai_kerja: dateInputValue(pegawai.mulai_kerja),
        stts_aktif: pegawai.stts_aktif ?? 'AKTIF',
        alamat: pegawai.alamat ?? '',
        kota: pegawai.kota ?? '',
        tmp_lahir: pegawai.tmp_lahir ?? '',
        tgl_lahir: dateInputValue(pegawai.tgl_lahir),
        no_ktp: pegawai.no_ktp ?? '',
    };
}

const JK_OPTIONS: SearchSelectOption[] = [
    { value: 'Pria', label: 'Pria' },
    { value: 'Wanita', label: 'Wanita' },
];

const STTS_AKTIF_OPTIONS: SearchSelectOption[] = [
    { value: 'AKTIF', label: 'AKTIF' },
    { value: 'CUTI', label: 'CUTI' },
    { value: 'KELUAR', label: 'KELUAR' },
    { value: 'TENAGA LUAR', label: 'TENAGA LUAR' },
    { value: 'NON AKTIF', label: 'NON AKTIF' },
];

export default function BerkasKepegawaianShow({
    pegawai,
    riwayat,
    sections,
    masterOptions,
    masterOptionsAll,
    berkasKategori,
    uploadedKodes,
    referensiOptions,
}: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [tab, setTab] = useState<TabKey>('berkas');
    const [editingProfil, setEditingProfil] = useState(false);
    const [replacingKode, setReplacingKode] = useState<string | null>(null);
    const [showAllJenis, setShowAllJenis] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
        {
            title: pegawai.nama,
            href: `/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}`,
        },
    ];

    const sourceMasters = showAllJenis ? masterOptionsAll : masterOptions;

    const availableMasters = useMemo(
        () => sourceMasters.filter((m) => !uploadedKodes.includes(m.kode)),
        [sourceMasters, uploadedKodes],
    );

    const masterSelectOptions = useMemo<SearchSelectOption[]>(
        () =>
            availableMasters.map((m) => ({
                value: m.kode,
                label: m.nama_berkas,
                description: `${m.kategori} · ${m.kode}`,
            })),
        [availableMasters],
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

    const profilForm = useForm(profilFormDefaults(pegawai));

    const openEditProfil = () => {
        profilForm.setData(profilFormDefaults(pegawai));
        profilForm.clearErrors();
        setEditingProfil(true);
    };

    const submitProfil = (e: FormEvent) => {
        e.preventDefault();
        profilForm.put(
            `/berkas-kepegawaian/${encodeURIComponent(pegawai.nik)}/profil`,
            {
                preserveScroll: true,
                onSuccess: () => setEditingProfil(false),
            },
        );
    };

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

    const profilFields: { label: string; value: string }[] = [
        { label: 'NIK', value: dash(pegawai.nik) },
        { label: 'JK', value: dash(pegawai.jk) },
        { label: 'Jabatan', value: dash(pegawai.jbtn) },
        { label: 'Bidang', value: dash(pegawai.bidang) },
        { label: 'Departemen', value: dash(pegawai.departemen_nama) },
        { label: 'Status kerja', value: dash(pegawai.stts_kerja_label) },
        { label: 'Status WP', value: dash(pegawai.stts_wp_label) },
        { label: 'Pendidikan', value: dash(pegawai.pendidikan) },
        { label: 'Jenjang', value: dash(pegawai.jnj_jabatan_nama) },
        { label: 'Kelompok', value: dash(pegawai.kelompok_jabatan_nama) },
        { label: 'Mulai kerja', value: dash(pegawai.mulai_kerja) },
        { label: 'Status aktif', value: dash(pegawai.stts_aktif) },
        { label: 'No. KTP', value: dash(pegawai.no_ktp) },
        {
            label: 'TTL',
            value: `${dash(pegawai.tmp_lahir)}, ${dash(pegawai.tgl_lahir)}`,
        },
        { label: 'Alamat', value: dash(pegawai.alamat) },
        { label: 'Kota', value: dash(pegawai.kota) },
    ];

    const allBerkas = useMemo(
        () =>
            sections.flatMap((section) =>
                section.items.map((item) => ({
                    ...item,
                    kategori: section.kategori,
                })),
            ),
        [sections],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kepegawaian — ${pegawai.nama}`} />

            <div className="flex flex-col gap-3">
                {flash.success ? (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-3 py-2 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash.success}
                    </div>
                ) : null}
                {flash.error ? (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                        {flash.error}
                    </div>
                ) : null}

                <div className="rounded-xl border bg-card">
                    <div className="flex flex-wrap items-start justify-between gap-2 border-b px-3 py-2.5">
                        <div className="flex min-w-0 items-start gap-2">
                            <Button variant="ghost" size="icon" className="mt-0.5 h-8 w-8" asChild>
                                <Link href="/berkas-kepegawaian">
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <div className="min-w-0">
                                <h1 className="truncate text-base font-semibold">{pegawai.nama}</h1>
                                <p className="text-xs text-muted-foreground">
                                    NIK {pegawai.nik}
                                    {pegawai.jbtn ? ` · ${pegawai.jbtn}` : ''}
                                    {pegawai.bidang ? ` · ${pegawai.bidang}` : ''}
                                    {pegawai.departemen_nama
                                        ? ` · ${pegawai.departemen_nama}`
                                        : ''}
                                </p>
                            </div>
                        </div>
                        {!editingProfil ? (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={openEditProfil}
                            >
                                Edit profil
                            </Button>
                        ) : null}
                    </div>

                    {editingProfil ? (
                        <form onSubmit={submitProfil} className="space-y-3 p-3">
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div className="space-y-1">
                                    <Label htmlFor="nama" className="text-xs">
                                        Nama
                                    </Label>
                                    <Input
                                        id="nama"
                                        value={profilForm.data.nama}
                                        onChange={(e) =>
                                            profilForm.setData('nama', e.target.value)
                                        }
                                        maxLength={50}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.nama} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="jk" className="text-xs">
                                        Jenis kelamin
                                    </Label>
                                    <SearchSelect
                                        inputId="jk"
                                        options={JK_OPTIONS}
                                        value={profilForm.data.jk}
                                        onChange={(v) => profilForm.setData('jk', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.jk)}
                                    />
                                    <InputError message={profilForm.errors.jk} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="jbtn" className="text-xs">
                                        Jabatan
                                    </Label>
                                    <Input
                                        id="jbtn"
                                        value={profilForm.data.jbtn}
                                        onChange={(e) =>
                                            profilForm.setData('jbtn', e.target.value)
                                        }
                                        maxLength={25}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.jbtn} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="bidang" className="text-xs">
                                        Bidang
                                    </Label>
                                    <SearchSelect
                                        inputId="bidang"
                                        options={referensiOptions.bidang}
                                        value={profilForm.data.bidang}
                                        onChange={(v) => profilForm.setData('bidang', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.bidang)}
                                    />
                                    <InputError message={profilForm.errors.bidang} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="departemen" className="text-xs">
                                        Departemen
                                    </Label>
                                    <SearchSelect
                                        inputId="departemen"
                                        options={referensiOptions.departemen}
                                        value={profilForm.data.departemen}
                                        onChange={(v) => profilForm.setData('departemen', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.departemen)}
                                    />
                                    <InputError message={profilForm.errors.departemen} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="stts_kerja" className="text-xs">
                                        Status kerja
                                    </Label>
                                    <SearchSelect
                                        inputId="stts_kerja"
                                        options={referensiOptions.stts_kerja}
                                        value={profilForm.data.stts_kerja}
                                        onChange={(v) => profilForm.setData('stts_kerja', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.stts_kerja)}
                                    />
                                    <InputError message={profilForm.errors.stts_kerja} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="stts_wp" className="text-xs">
                                        Status WP
                                    </Label>
                                    <SearchSelect
                                        inputId="stts_wp"
                                        options={referensiOptions.stts_wp}
                                        value={profilForm.data.stts_wp}
                                        onChange={(v) => profilForm.setData('stts_wp', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.stts_wp)}
                                    />
                                    <InputError message={profilForm.errors.stts_wp} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="pendidikan" className="text-xs">
                                        Pendidikan
                                    </Label>
                                    <SearchSelect
                                        inputId="pendidikan"
                                        options={referensiOptions.pendidikan}
                                        value={profilForm.data.pendidikan}
                                        onChange={(v) => profilForm.setData('pendidikan', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.pendidikan)}
                                    />
                                    <InputError message={profilForm.errors.pendidikan} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="jnj_jabatan" className="text-xs">
                                        Jenjang jabatan
                                    </Label>
                                    <SearchSelect
                                        inputId="jnj_jabatan"
                                        options={referensiOptions.jnj_jabatan}
                                        value={profilForm.data.jnj_jabatan}
                                        onChange={(v) => profilForm.setData('jnj_jabatan', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.jnj_jabatan)}
                                    />
                                    <InputError message={profilForm.errors.jnj_jabatan} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="kode_kelompok" className="text-xs">
                                        Kelompok jabatan
                                    </Label>
                                    <SearchSelect
                                        inputId="kode_kelompok"
                                        options={referensiOptions.kelompok_jabatan}
                                        value={profilForm.data.kode_kelompok}
                                        onChange={(v) =>
                                            profilForm.setData('kode_kelompok', v)
                                        }
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.kode_kelompok)}
                                    />
                                    <InputError message={profilForm.errors.kode_kelompok} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="stts_aktif" className="text-xs">
                                        Status aktif
                                    </Label>
                                    <SearchSelect
                                        inputId="stts_aktif"
                                        options={STTS_AKTIF_OPTIONS}
                                        value={profilForm.data.stts_aktif}
                                        onChange={(v) => profilForm.setData('stts_aktif', v)}
                                        isClearable={false}
                                        hasError={Boolean(profilForm.errors.stts_aktif)}
                                    />
                                    <InputError message={profilForm.errors.stts_aktif} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="mulai_kerja" className="text-xs">
                                        Mulai kerja
                                    </Label>
                                    <Input
                                        id="mulai_kerja"
                                        type="date"
                                        value={profilForm.data.mulai_kerja}
                                        onChange={(e) =>
                                            profilForm.setData('mulai_kerja', e.target.value)
                                        }
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.mulai_kerja} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="tmp_lahir" className="text-xs">
                                        Tempat lahir
                                    </Label>
                                    <Input
                                        id="tmp_lahir"
                                        value={profilForm.data.tmp_lahir}
                                        onChange={(e) =>
                                            profilForm.setData('tmp_lahir', e.target.value)
                                        }
                                        maxLength={20}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.tmp_lahir} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="tgl_lahir" className="text-xs">
                                        Tanggal lahir
                                    </Label>
                                    <Input
                                        id="tgl_lahir"
                                        type="date"
                                        value={profilForm.data.tgl_lahir}
                                        onChange={(e) =>
                                            profilForm.setData('tgl_lahir', e.target.value)
                                        }
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.tgl_lahir} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="no_ktp" className="text-xs">
                                        No. KTP
                                    </Label>
                                    <Input
                                        id="no_ktp"
                                        value={profilForm.data.no_ktp}
                                        onChange={(e) =>
                                            profilForm.setData('no_ktp', e.target.value)
                                        }
                                        maxLength={20}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.no_ktp} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="kota" className="text-xs">
                                        Kota
                                    </Label>
                                    <Input
                                        id="kota"
                                        value={profilForm.data.kota}
                                        onChange={(e) =>
                                            profilForm.setData('kota', e.target.value)
                                        }
                                        maxLength={20}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.kota} />
                                </div>
                                <div className="space-y-1 sm:col-span-2 lg:col-span-3">
                                    <Label htmlFor="alamat" className="text-xs">
                                        Alamat
                                    </Label>
                                    <Input
                                        id="alamat"
                                        value={profilForm.data.alamat}
                                        onChange={(e) =>
                                            profilForm.setData('alamat', e.target.value)
                                        }
                                        maxLength={60}
                                        required
                                        className="h-9"
                                    />
                                    <InputError message={profilForm.errors.alamat} />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={profilForm.processing}
                                >
                                    {profilForm.processing ? 'Menyimpan...' : 'Simpan'}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setEditingProfil(false)}
                                >
                                    Batal
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="grid gap-x-3 gap-y-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
                            {profilFields.map((field) => (
                                <div key={field.label} className="min-w-0 space-y-0.5">
                                    <div className="text-[11px] text-muted-foreground">
                                        {field.label}
                                    </div>
                                    <div className="truncate text-sm font-medium">
                                        {field.value}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="flex flex-wrap items-center gap-2 border-b px-3 py-2">
                        <Button
                            type="button"
                            size="sm"
                            variant={tab === 'berkas' ? 'default' : 'outline'}
                            onClick={() => setTab('berkas')}
                        >
                            Berkas
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant={tab === 'riwayat' ? 'default' : 'outline'}
                            onClick={() => setTab('riwayat')}
                        >
                            Riwayat
                        </Button>
                    </div>

                    {tab === 'berkas' ? (
                        <div className="space-y-3 p-3">
                            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                <span>
                                    Jenis berkas difilter ke:{' '}
                                    <span className="font-medium text-foreground">
                                        {berkasKategori}
                                    </span>
                                </span>
                                <label className="inline-flex cursor-pointer items-center gap-1.5">
                                    <input
                                        type="checkbox"
                                        checked={showAllJenis}
                                        onChange={(e) => setShowAllJenis(e.target.checked)}
                                        className="rounded border"
                                    />
                                    Tampilkan semua kategori
                                </label>
                            </div>
                            <form
                                onSubmit={submitUpload}
                                className="grid gap-2 rounded-lg border bg-muted/20 p-2.5 sm:grid-cols-4 sm:items-end"
                            >
                                <div className="space-y-1 sm:col-span-1">
                                    <Label htmlFor="kode_berkas" className="text-xs">
                                        Jenis
                                    </Label>
                                    <SearchSelect
                                        inputId="kode_berkas"
                                        options={masterSelectOptions}
                                        value={uploadForm.data.kode_berkas}
                                        onChange={(value) =>
                                            uploadForm.setData('kode_berkas', value)
                                        }
                                        placeholder="Jenis berkas..."
                                        isClearable={false}
                                        disabled={availableMasters.length === 0}
                                        hasError={Boolean(uploadForm.errors.kode_berkas)}
                                        noOptionsMessage={
                                            availableMasters.length === 0
                                                ? 'Semua jenis sudah diunggah'
                                                : 'Tidak ditemukan'
                                        }
                                    />
                                    <InputError message={uploadForm.errors.kode_berkas} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="tgl_uploud" className="text-xs">
                                        Tanggal
                                    </Label>
                                    <Input
                                        id="tgl_uploud"
                                        type="date"
                                        value={uploadForm.data.tgl_uploud}
                                        onChange={(e) =>
                                            uploadForm.setData('tgl_uploud', e.target.value)
                                        }
                                        className="h-9"
                                    />
                                    <InputError message={uploadForm.errors.tgl_uploud} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="dokumen" className="text-xs">
                                        File
                                    </Label>
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
                                        className="h-9 cursor-pointer"
                                    />
                                    <InputError message={uploadForm.errors.dokumen} />
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    className="h-9"
                                    disabled={
                                        uploadForm.processing ||
                                        availableMasters.length === 0 ||
                                        !uploadForm.data.dokumen
                                    }
                                >
                                    <Upload className="mr-1.5 h-3.5 w-3.5" />
                                    {uploadForm.processing ? '...' : 'Unggah'}
                                </Button>
                            </form>

                            {replacingKode ? (
                                <form
                                    onSubmit={submitReplace}
                                    className="grid gap-2 rounded-lg border border-amber-500/30 bg-amber-50/40 p-2.5 sm:grid-cols-3 sm:items-end dark:bg-amber-950/20"
                                >
                                    <div className="space-y-1 sm:col-span-3">
                                        <p className="text-sm font-medium">
                                            Ganti berkas ({replacingKode})
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="replace_tgl" className="text-xs">
                                            Tanggal
                                        </Label>
                                        <Input
                                            id="replace_tgl"
                                            type="date"
                                            value={replaceForm.data.tgl_uploud}
                                            onChange={(e) =>
                                                replaceForm.setData(
                                                    'tgl_uploud',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-9"
                                        />
                                        <InputError message={replaceForm.errors.tgl_uploud} />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="replace_dokumen" className="text-xs">
                                            File baru
                                        </Label>
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
                                            className="h-9 cursor-pointer"
                                        />
                                        <InputError message={replaceForm.errors.dokumen} />
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="submit"
                                            size="sm"
                                            className="h-9"
                                            disabled={
                                                replaceForm.processing ||
                                                !replaceForm.data.dokumen
                                            }
                                        >
                                            {replaceForm.processing ? '...' : 'Simpan'}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="h-9"
                                            onClick={() => setReplacingKode(null)}
                                        >
                                            Batal
                                        </Button>
                                    </div>
                                </form>
                            ) : null}

                            {allBerkas.length === 0 ? (
                                <p className="px-1 py-4 text-sm text-muted-foreground">
                                    Belum ada berkas untuk pegawai ini.
                                </p>
                            ) : (
                                <div className="overflow-x-auto rounded-lg border">
                                    <table className="w-full text-left text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/40">
                                                <th className="px-3 py-2 font-medium">Kategori</th>
                                                <th className="px-3 py-2 font-medium">Jenis</th>
                                                <th className="px-3 py-2 font-medium">Tanggal</th>
                                                <th className="px-3 py-2 font-medium">File</th>
                                                <th className="px-3 py-2 font-medium">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {allBerkas.map((item) => (
                                                <tr
                                                    key={item.kode_berkas}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-3 py-2 text-muted-foreground">
                                                        {item.kategori}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <div className="font-medium">
                                                            {item.nama_berkas}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.kode_berkas}
                                                        </div>
                                                    </td>
                                                    <td className="px-3 py-2 text-muted-foreground">
                                                        {item.tgl_uploud ?? '–'}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        {item.public_url ? (
                                                            <a
                                                                href={item.public_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-teal-700 hover:underline dark:text-teal-300"
                                                            >
                                                                Buka
                                                                <ExternalLink className="h-3.5 w-3.5" />
                                                            </a>
                                                        ) : (
                                                            '–'
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <div className="flex flex-wrap gap-1.5">
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                className="h-7"
                                                                onClick={() => openReplace(item)}
                                                            >
                                                                Ganti
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="destructive"
                                                                className="h-7"
                                                                onClick={() => destroyBerkas(item)}
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    ) : (
                        <RiwayatEditor nik={pegawai.nik} riwayat={riwayat} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
