import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useCallback, useMemo, useState } from 'react';
import { CreatableSearchSelect } from '@/components/creatable-search-select';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Button } from '@/components/ui/button';
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
import { quickCreateAsetMaster, type AsetMasterTipe } from '@/lib/aset-master-api';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type MasterOpt = {
    id: number;
    nama_kategori?: string;
    nama_jenis?: string;
    nama_merk?: string;
    nama_produsen?: string;
    nama_distributor?: string;
    nama_alat?: string;
    kode?: string;
};

type BarangOpt = {
    id: number;
    kode_barang: string;
    nama_barang: string;
    kelas_aset: string | null;
    aset_merk_id: number | null;
    aset_jenis_id: number | null;
    aset_kategori_id: number | null;
    aset_produsen_id: number | null;
};

type Props = {
    aset: {
        kode_aset: string;
        no_seri: string | null;
        aset_barang_id: number | null;
        aset_ruang_id: number | null;
        aset_distributor_id: number | null;
        tahun_registrasi: number | null;
        asal_barang: string | null;
        tanggal_pengadaan: string | null;
        harga: number | null;
        status_fungsi: string;
        tingkat_kerusakan: string;
        kelas_aset: string | null;
        wajib_kalibrasi: boolean;
        umur_ekonomis_bulan: number | null;
        aset_kategori_id: number | null;
        aset_jenis_id: number | null;
        aset_merk_id: number | null;
        aset_produsen_id: number | null;
        aset_aspak_alat_id: number | null;
        no_akl_akd: string | null;
        daya_watt: number | null;
        level_teknologi: string | null;
        tahun_produksi: number | null;
        tahun_mulai_operasi: number | null;
        nilai_residu: number | null;
        nama_barang: string | null;
    };
    barang: BarangOpt[];
    ruang: { id: number; kode_ruang: string; nama_ruang: string }[];
    kategori: MasterOpt[];
    jenis: MasterOpt[];
    merk: MasterOpt[];
    produsen: MasterOpt[];
    distributor: MasterOpt[];
    aspak: MasterOpt[];
    penyusutanDefaults?: {
        residu_persen_default: number;
        umur_bulan_medis: number;
        umur_bulan_non_medis: number;
        umur_bulan_default: number;
    };
};

function toOptions(items: { id: number; label: string; description?: string }[]): SearchSelectOption[] {
    return items.map((item) => ({
        value: String(item.id),
        label: item.label,
        description: item.description,
    }));
}

export default function AsetEdit({
    aset,
    barang,
    ruang,
    kategori,
    jenis,
    merk,
    produsen,
    distributor,
    aspak,
    penyusutanDefaults,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Aset', href: '/aset' },
        { title: aset.kode_aset, href: `/aset/${aset.kode_aset}` },
        { title: 'Edit', href: `/aset/${aset.kode_aset}/edit` },
    ];

    const [kategoriList, setKategoriList] = useState(kategori);
    const [jenisList, setJenisList] = useState(jenis);
    const [merkList, setMerkList] = useState(merk);
    const [produsenList, setProdusenList] = useState(produsen);
    const [distributorList, setDistributorList] = useState(distributor);
    const [creatingMaster, setCreatingMaster] = useState<string | null>(null);

    const { data, setData, put, processing, errors, transform } = useForm({
        aset_barang_id: aset.aset_barang_id ? String(aset.aset_barang_id) : '',
        aset_ruang_id: aset.aset_ruang_id ? String(aset.aset_ruang_id) : '',
        aset_distributor_id: aset.aset_distributor_id ? String(aset.aset_distributor_id) : '',
        aset_kategori_id: aset.aset_kategori_id ? String(aset.aset_kategori_id) : '',
        aset_jenis_id: aset.aset_jenis_id ? String(aset.aset_jenis_id) : '',
        aset_merk_id: aset.aset_merk_id ? String(aset.aset_merk_id) : '',
        aset_produsen_id: aset.aset_produsen_id ? String(aset.aset_produsen_id) : '',
        aset_aspak_alat_id: aset.aset_aspak_alat_id ? String(aset.aset_aspak_alat_id) : '',
        tahun_registrasi: String(aset.tahun_registrasi ?? new Date().getFullYear()),
        tahun_produksi: aset.tahun_produksi != null ? String(aset.tahun_produksi) : '',
        tahun_mulai_operasi: aset.tahun_mulai_operasi != null ? String(aset.tahun_mulai_operasi) : '',
        no_seri: aset.no_seri ?? '',
        asal_barang: aset.asal_barang ?? '',
        tanggal_pengadaan: aset.tanggal_pengadaan ?? '',
        harga: aset.harga != null ? String(aset.harga) : '',
        status_fungsi: aset.status_fungsi || 'berfungsi',
        tingkat_kerusakan: aset.tingkat_kerusakan || 'baik',
        kelas_aset: aset.kelas_aset ?? '',
        wajib_kalibrasi: Boolean(aset.wajib_kalibrasi),
        umur_ekonomis_bulan: aset.umur_ekonomis_bulan != null ? String(aset.umur_ekonomis_bulan) : '',
        no_akl_akd: aset.no_akl_akd ?? '',
        daya_watt: aset.daya_watt != null ? String(aset.daya_watt) : '',
        level_teknologi: aset.level_teknologi ?? '',
        nilai_residu: aset.nilai_residu != null ? String(aset.nilai_residu) : '',
    });

    const barangOptions = useMemo(
        () => barang.map((b) => ({ value: String(b.id), label: b.nama_barang, description: b.kode_barang })),
        [barang],
    );
    const ruangOptions = useMemo(
        () => ruang.map((r) => ({ value: String(r.id), label: r.nama_ruang, description: r.kode_ruang })),
        [ruang],
    );
    const kategoriOptions = useMemo(
        () => toOptions(kategoriList.map((k) => ({ id: k.id, label: k.nama_kategori ?? '' }))),
        [kategoriList],
    );
    const jenisOptions = useMemo(
        () => toOptions(jenisList.map((j) => ({ id: j.id, label: j.nama_jenis ?? '' }))),
        [jenisList],
    );
    const merkOptions = useMemo(
        () => toOptions(merkList.map((m) => ({ id: m.id, label: m.nama_merk ?? '' }))),
        [merkList],
    );
    const produsenOptions = useMemo(
        () => toOptions(produsenList.map((p) => ({ id: p.id, label: p.nama_produsen ?? '' }))),
        [produsenList],
    );
    const distributorOptions = useMemo(
        () => toOptions(distributorList.map((d) => ({ id: d.id, label: d.nama_distributor ?? '' }))),
        [distributorList],
    );
    const aspakOptions = useMemo(
        () => aspak.map((a) => ({ value: String(a.id), label: a.nama_alat ?? '', description: a.kode ?? undefined })),
        [aspak],
    );

    const createMaster = useCallback(async (tipe: AsetMasterTipe, nama: string): Promise<SearchSelectOption | null> => {
        setCreatingMaster(tipe);
        try {
            const result = await quickCreateAsetMaster(tipe, nama);
            if (!result) return null;
            const option = { value: String(result.item.id), label: result.item.nama, description: result.item.kode };
            const { id, nama: namaVal, kode } = result.item;
            if (tipe === 'kategori') {
                setKategoriList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_kategori: namaVal }]));
            } else if (tipe === 'jenis') {
                setJenisList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_jenis: namaVal }]));
            } else if (tipe === 'merk') {
                setMerkList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_merk: namaVal }]));
            } else if (tipe === 'produsen') {
                setProdusenList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_produsen: namaVal }]));
            } else {
                setDistributorList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_distributor: namaVal }]));
            }
            void kode;

            return option;
        } finally {
            setCreatingMaster(null);
        }
    }, []);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((d) => ({
            aset_barang_id: d.aset_barang_id ? Number(d.aset_barang_id) : null,
            aset_ruang_id: Number(d.aset_ruang_id),
            aset_distributor_id: d.aset_distributor_id ? Number(d.aset_distributor_id) : null,
            aset_kategori_id: d.aset_kategori_id ? Number(d.aset_kategori_id) : null,
            aset_jenis_id: d.aset_jenis_id ? Number(d.aset_jenis_id) : null,
            aset_merk_id: d.aset_merk_id ? Number(d.aset_merk_id) : null,
            aset_produsen_id: d.aset_produsen_id ? Number(d.aset_produsen_id) : null,
            aset_aspak_alat_id: d.aset_aspak_alat_id ? Number(d.aset_aspak_alat_id) : null,
            tahun_registrasi: Number(d.tahun_registrasi),
            tahun_produksi: d.tahun_produksi ? Number(d.tahun_produksi) : null,
            tahun_mulai_operasi: d.tahun_mulai_operasi ? Number(d.tahun_mulai_operasi) : null,
            no_seri: d.no_seri.trim() || null,
            asal_barang: d.asal_barang || null,
            tanggal_pengadaan: d.tanggal_pengadaan || null,
            harga: d.harga !== '' ? Number(d.harga) : null,
            status_fungsi: d.status_fungsi,
            tingkat_kerusakan: d.tingkat_kerusakan,
            kelas_aset: d.kelas_aset || null,
            wajib_kalibrasi: d.wajib_kalibrasi,
            umur_ekonomis_bulan: d.umur_ekonomis_bulan ? Number(d.umur_ekonomis_bulan) : null,
            no_akl_akd: d.no_akl_akd || null,
            daya_watt: d.daya_watt ? Number(d.daya_watt) : null,
            level_teknologi: d.level_teknologi || null,
            nilai_residu: d.nilai_residu !== '' ? Number(d.nilai_residu) : null,
            jumlah_unit: 1,
        }));
        put(`/aset/${aset.kode_aset}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${aset.kode_aset}`} />

            <form onSubmit={submit} className="mx-auto max-w-2xl space-y-5 px-4 pb-10 sm:px-0">
                <header className="border-b border-border/70 pb-5">
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris portal
                    </p>
                    <h1 className="text-[1.75rem] font-semibold tracking-tight">{aset.kode_aset}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {aset.nama_barang ?? 'Edit unit aset'} · serial & klasifikasi per unit
                    </p>
                </header>

                <section className="space-y-4 rounded-xl border border-border/80 bg-card p-5 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <h2 className="text-sm font-semibold tracking-tight">Identitas unit</h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2 sm:col-span-2">
                            <Label>Barang</Label>
                            <SearchSelect
                                options={barangOptions}
                                value={data.aset_barang_id}
                                onChange={(v) => setData('aset_barang_id', v)}
                                placeholder="Cari barang..."
                                isClearable={false}
                            />
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label>
                                Ruang <span className="text-destructive">*</span>
                            </Label>
                            <SearchSelect
                                options={ruangOptions}
                                value={data.aset_ruang_id}
                                onChange={(v) => setData('aset_ruang_id', v)}
                                placeholder="Cari ruang..."
                                isClearable={false}
                                hasError={Boolean(errors.aset_ruang_id)}
                            />
                            <InputError message={errors.aset_ruang_id} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="no_seri">Nomor seri</Label>
                            <Input
                                id="no_seri"
                                value={data.no_seri}
                                onChange={(e) => setData('no_seri', e.target.value)}
                                placeholder="Opsional"
                                className="h-10"
                            />
                            <InputError message={errors.no_seri} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tahun_registrasi">Tahun registrasi</Label>
                            <Input
                                id="tahun_registrasi"
                                value={data.tahun_registrasi}
                                onChange={(e) => setData('tahun_registrasi', e.target.value)}
                                className="h-10"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Status fungsi</Label>
                            <Select value={data.status_fungsi} onValueChange={(v) => setData('status_fungsi', v)}>
                                <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="berfungsi">Berfungsi</SelectItem>
                                    <SelectItem value="tidak_berfungsi">Tidak berfungsi</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Kondisi fisik</Label>
                            <Select value={data.tingkat_kerusakan} onValueChange={(v) => setData('tingkat_kerusakan', v)}>
                                <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="baik">Baik</SelectItem>
                                    <SelectItem value="rusak_ringan">Rusak ringan</SelectItem>
                                    <SelectItem value="rusak_berat">Rusak berat</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="harga">Harga (Rp)</Label>
                            <Input id="harga" value={data.harga} onChange={(e) => setData('harga', e.target.value)} className="h-10" />
                        </div>
                        <div className="space-y-2">
                            <Label>Asal barang</Label>
                            <Select
                                value={data.asal_barang || '__none__'}
                                onValueChange={(v) => setData('asal_barang', v === '__none__' ? '' : v)}
                            >
                                <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">Belum diisi</SelectItem>
                                    <SelectItem value="Beli">Beli</SelectItem>
                                    <SelectItem value="Bantuan">Bantuan</SelectItem>
                                    <SelectItem value="Hibah">Hibah</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tanggal_pengadaan">Tanggal pengadaan</Label>
                            <Input
                                id="tanggal_pengadaan"
                                type="date"
                                value={data.tanggal_pengadaan}
                                onChange={(e) => setData('tanggal_pengadaan', e.target.value)}
                                className="h-10"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Distributor</Label>
                            <CreatableSearchSelect
                                options={distributorOptions}
                                value={data.aset_distributor_id}
                                onChange={(v) => setData('aset_distributor_id', v)}
                                onCreateOption={(n) => createMaster('distributor', n)}
                                isCreating={creatingMaster === 'distributor'}
                                placeholder="Cari atau tambah distributor..."
                            />
                        </div>
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border border-border/80 bg-card p-5 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <h2 className="text-sm font-semibold tracking-tight">Klasifikasi barang</h2>
                    <p className="text-xs text-muted-foreground">
                        Perubahan di sini berlaku untuk semua unit dengan barang yang sama.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <CreatableField label="Kategori" options={kategoriOptions} value={data.aset_kategori_id} onChange={(v) => setData('aset_kategori_id', v)} onCreate={(n) => createMaster('kategori', n)} isCreating={creatingMaster === 'kategori'} />
                        <CreatableField label="Tipe / jenis" options={jenisOptions} value={data.aset_jenis_id} onChange={(v) => setData('aset_jenis_id', v)} onCreate={(n) => createMaster('jenis', n)} isCreating={creatingMaster === 'jenis'} />
                        <CreatableField label="Merk" options={merkOptions} value={data.aset_merk_id} onChange={(v) => setData('aset_merk_id', v)} onCreate={(n) => createMaster('merk', n)} isCreating={creatingMaster === 'merk'} />
                        <CreatableField label="Produsen" options={produsenOptions} value={data.aset_produsen_id} onChange={(v) => setData('aset_produsen_id', v)} onCreate={(n) => createMaster('produsen', n)} isCreating={creatingMaster === 'produsen'} />
                        <div className="space-y-2">
                            <Label>Kelas</Label>
                            <Select value={data.kelas_aset || '__none__'} onValueChange={(v) => setData('kelas_aset', v === '__none__' ? '' : v)}>
                                <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">Belum diisi</SelectItem>
                                    <SelectItem value="medis">Medis</SelectItem>
                                    <SelectItem value="non_medis">Non-medis</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-end gap-2 pb-2">
                            <Checkbox checked={data.wajib_kalibrasi} onCheckedChange={(c) => setData('wajib_kalibrasi', Boolean(c))} id="wk" />
                            <Label htmlFor="wk" className="cursor-pointer font-normal">Wajib kalibrasi</Label>
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                            <Label>ASPAK</Label>
                            <SearchSelect
                                options={aspakOptions}
                                value={data.aset_aspak_alat_id}
                                onChange={(v) => setData('aset_aspak_alat_id', v)}
                                placeholder="Cari ASPAK..."
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="no_akl_akd">No. AKL/AKD</Label>
                            <Input id="no_akl_akd" value={data.no_akl_akd} onChange={(e) => setData('no_akl_akd', e.target.value)} className="h-10" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="daya_watt">Daya (Watt)</Label>
                            <Input id="daya_watt" value={data.daya_watt} onChange={(e) => setData('daya_watt', e.target.value)} className="h-10" />
                        </div>
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border border-border/80 bg-card p-5 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 className="text-sm font-semibold tracking-tight">Penyusutan (override)</h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Kosongkan untuk memakai default global. Atur default di{' '}
                                <Link href="/aset/pengaturan-penyusutan" className="text-teal-700 underline dark:text-teal-400">
                                    Pengaturan Penyusutan
                                </Link>
                                .
                            </p>
                        </div>
                    </div>
                    {penyusutanDefaults && (
                        <p className="rounded-lg bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                            Default saat ini: residu {penyusutanDefaults.residu_persen_default}% · umur medis{' '}
                            {penyusutanDefaults.umur_bulan_medis} bln · non-medis {penyusutanDefaults.umur_bulan_non_medis} bln ·
                            umum {penyusutanDefaults.umur_bulan_default} bln
                        </p>
                    )}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="umur_ekonomis_bulan">Umur manfaat (bulan)</Label>
                            <Input
                                id="umur_ekonomis_bulan"
                                value={data.umur_ekonomis_bulan}
                                onChange={(e) => setData('umur_ekonomis_bulan', e.target.value)}
                                placeholder="Kosong = pakai default"
                                className="h-10"
                            />
                            <InputError message={errors.umur_ekonomis_bulan} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="nilai_residu">Nilai residu (Rp)</Label>
                            <Input
                                id="nilai_residu"
                                value={data.nilai_residu}
                                onChange={(e) => setData('nilai_residu', e.target.value)}
                                placeholder="Kosong = % default × harga"
                                className="h-10"
                            />
                            <InputError message={errors.nilai_residu} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tahun_produksi">Tahun produksi</Label>
                            <Input
                                id="tahun_produksi"
                                value={data.tahun_produksi}
                                onChange={(e) => setData('tahun_produksi', e.target.value)}
                                className="h-10"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Level teknologi</Label>
                            <Select
                                value={data.level_teknologi || '__none__'}
                                onValueChange={(v) => setData('level_teknologi', v === '__none__' ? '' : v)}
                            >
                                <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">Belum diisi</SelectItem>
                                    <SelectItem value="low">Rendah</SelectItem>
                                    <SelectItem value="medium">Sedang</SelectItem>
                                    <SelectItem value="high">Tinggi</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </section>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing || !data.aset_ruang_id} className="bg-teal-700 hover:bg-teal-800">
                        {processing ? 'Menyimpan...' : 'Simpan perubahan'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={`/aset/${aset.kode_aset}`}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}

function CreatableField({
    label,
    options,
    value,
    onChange,
    onCreate,
    isCreating,
}: {
    label: string;
    options: SearchSelectOption[];
    value: string;
    onChange: (v: string) => void;
    onCreate: (n: string) => Promise<SearchSelectOption | null>;
    isCreating: boolean;
}) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <CreatableSearchSelect
                options={options}
                value={value}
                onChange={onChange}
                onCreateOption={onCreate}
                isCreating={isCreating}
                placeholder={`Cari atau tambah ${label.toLowerCase()}...`}
            />
        </div>
    );
}
