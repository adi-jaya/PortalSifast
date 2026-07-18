import { Head, Link, useForm } from '@inertiajs/react';
import {
    Box,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Layers,
    MapPin,
    Package,
    Plus,
    Settings2,
} from 'lucide-react';
import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { CreatableSearchSelect } from '@/components/creatable-search-select';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import { cn } from '@/lib/utils';
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
    kode_kategori?: string;
    kode_jenis?: string;
    kode_merk?: string;
    kode_produsen?: string;
    kode_distributor?: string;
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
    barang: BarangOpt[];
    ruang: { id: number; kode_ruang: string; nama_ruang: string }[];
    kategori: MasterOpt[];
    jenis: MasterOpt[];
    merk: MasterOpt[];
    produsen: MasterOpt[];
    distributor: MasterOpt[];
    aspak: MasterOpt[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Tambah', href: '/aset/create' },
];

const STEPS = [
    { id: 1, label: 'Barang', hint: 'Apa yang ditambah', icon: Package },
    { id: 2, label: 'Lokasi', hint: 'Di mana & berapa', icon: MapPin },
    { id: 3, label: 'Detail', hint: 'Opsional', icon: Settings2 },
] as const;

function toOptions(items: { id: number; label: string; description?: string }[]): SearchSelectOption[] {
    return items.map((item) => ({
        value: String(item.id),
        label: item.label,
        description: item.description,
    }));
}

export default function AsetCreate({
    barang,
    ruang,
    kategori,
    jenis,
    merk,
    produsen,
    distributor,
    aspak,
}: Props) {
    const [step, setStep] = useState(1);
    const [mode, setMode] = useState<'existing' | 'new'>('existing');
    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [kategoriList, setKategoriList] = useState(kategori);
    const [jenisList, setJenisList] = useState(jenis);
    const [merkList, setMerkList] = useState(merk);
    const [produsenList, setProdusenList] = useState(produsen);
    const [distributorList, setDistributorList] = useState(distributor);
    const [creatingMaster, setCreatingMaster] = useState<string | null>(null);
    const [masterError, setMasterError] = useState<string | null>(null);

    const { data, setData, post, processing, errors, transform } = useForm({
        aset_barang_id: '',
        nama_barang: '',
        aset_kategori_id: '',
        aset_jenis_id: '',
        aset_merk_id: '',
        aset_produsen_id: '',
        aset_aspak_alat_id: '',
        aset_ruang_id: '',
        aset_distributor_id: '',
        tahun_registrasi: String(new Date().getFullYear()),
        tahun_produksi: '',
        tahun_mulai_operasi: '',
        jumlah_unit: '1',
        no_seri_list: [''] as string[],
        asal_barang: '',
        tanggal_pengadaan: '',
        harga: '',
        status_fungsi: 'berfungsi',
        tingkat_kerusakan: 'baik',
        kelas_aset: '',
        wajib_kalibrasi: false,
        umur_ekonomis_bulan: '',
        no_akl_akd: '',
        daya_watt: '',
        level_teknologi: '',
        nilai_residu: '',
        foto: null as File | null,
    });

    const jumlah = Math.max(1, Math.min(50, Number(data.jumlah_unit) || 1));
    const noSeriList = useMemo(
        () => (Array.isArray(data.no_seri_list) ? data.no_seri_list : ['']),
        [data.no_seri_list],
    );

    useEffect(() => {
        setData((d) => {
            const current = Array.isArray(d.no_seri_list) ? d.no_seri_list : [''];
            const next = [...current];
            while (next.length < jumlah) next.push('');
            while (next.length > jumlah) next.pop();
            if (next.length === current.length && next.every((v, i) => v === current[i])) {
                return d;
            }

            return { ...d, no_seri_list: next };
        });
    }, [jumlah, setData]);

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

    const selectedBarang = useMemo(
        () => barang.find((b) => String(b.id) === data.aset_barang_id) ?? null,
        [barang, data.aset_barang_id],
    );

    const createMaster = useCallback(async (tipe: AsetMasterTipe, nama: string): Promise<SearchSelectOption | null> => {
        setCreatingMaster(tipe);
        setMasterError(null);
        try {
            const result = await quickCreateAsetMaster(tipe, nama);
            if (!result) {
                setMasterError(`Gagal menambah ${tipe}. Nama minimal 2 karakter.`);

                return null;
            }
            const option: SearchSelectOption = {
                value: String(result.item.id),
                label: result.item.nama,
                description: result.item.kode,
            };
            const id = result.item.id;
            const namaVal = result.item.nama;
            const kode = result.item.kode;
            if (tipe === 'kategori') {
                setKategoriList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_kategori: namaVal, kode_kategori: kode }]));
            } else if (tipe === 'jenis') {
                setJenisList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_jenis: namaVal, kode_jenis: kode }]));
            } else if (tipe === 'merk') {
                setMerkList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_merk: namaVal, kode_merk: kode }]));
            } else if (tipe === 'produsen') {
                setProdusenList((prev) => (prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_produsen: namaVal, kode_produsen: kode }]));
            } else {
                setDistributorList((prev) =>
                    prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_distributor: namaVal, kode_distributor: kode }],
                );
            }

            return option;
        } finally {
            setCreatingMaster(null);
        }
    }, []);

    const switchMode = (next: 'existing' | 'new') => {
        setMode(next);
        if (next === 'new') setData('aset_barang_id', '');
        else setData('nama_barang', '');
    };

    const onBarangSelect = (id: string) => {
        const selected = barang.find((b) => String(b.id) === id);
        setData((d) => ({
            ...d,
            aset_barang_id: id,
            nama_barang: selected?.nama_barang ?? d.nama_barang,
            kelas_aset: selected?.kelas_aset ?? d.kelas_aset,
            aset_merk_id: selected?.aset_merk_id ? String(selected.aset_merk_id) : d.aset_merk_id,
            aset_jenis_id: selected?.aset_jenis_id ? String(selected.aset_jenis_id) : d.aset_jenis_id,
            aset_kategori_id: selected?.aset_kategori_id ? String(selected.aset_kategori_id) : d.aset_kategori_id,
            aset_produsen_id: selected?.aset_produsen_id ? String(selected.aset_produsen_id) : d.aset_produsen_id,
        }));
    };

    const canProceedStep1 = mode === 'existing' ? Boolean(data.aset_barang_id) : Boolean(data.nama_barang.trim());
    const canProceedStep2 = Boolean(data.aset_ruang_id);
    const canSubmit = canProceedStep1 && canProceedStep2 && !processing;

    const goNext = () => {
        if (step === 1 && canProceedStep1) setStep(2);
        else if (step === 2 && canProceedStep2) setStep(3);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((d) => ({
            ...d,
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
            jumlah_unit: Number(d.jumlah_unit) || 1,
            harga: d.harga !== '' ? Number(d.harga) : null,
            umur_ekonomis_bulan: d.umur_ekonomis_bulan ? Number(d.umur_ekonomis_bulan) : null,
            daya_watt: d.daya_watt ? Number(d.daya_watt) : null,
            nilai_residu: d.nilai_residu !== '' ? Number(d.nilai_residu) : null,
            kelas_aset: d.kelas_aset || null,
            level_teknologi: d.level_teknologi || null,
            no_seri_list: (Array.isArray(d.no_seri_list) ? d.no_seri_list : [])
                .slice(0, Number(d.jumlah_unit) || 1)
                .map((s) => String(s).trim()),
        }));
        post('/aset', { forceFormData: Boolean(data.foto) });
    };

    const serialInputs = useMemo(() => noSeriList.slice(0, jumlah), [noSeriList, jumlah]);
    const summaryLabel = mode === 'existing' ? selectedBarang?.nama_barang : data.nama_barang || 'Barang baru';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Aset" />

            <div className="relative mx-auto max-w-2xl px-4 pb-28 pt-2 sm:px-0">
                {/* Header */}
                <header className="mb-8 border-b border-border/70 pb-6">
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris portal
                    </p>
                    <h1 className="text-[1.75rem] leading-tight font-semibold tracking-tight text-foreground">
                        Tambah aset
                    </h1>
                    <p className="mt-1.5 max-w-lg text-sm leading-relaxed text-muted-foreground">
                        Tiga langkah ringkas. Data wajib di awal — detail regulasi bisa dilengkapi nanti.
                    </p>
                </header>

                {/* Step rail */}
                <nav aria-label="Langkah" className="mb-8">
                    <ol className="flex items-stretch gap-0">
                        {STEPS.map(({ id, label, hint, icon: Icon }, index) => {
                            const active = step === id;
                            const done = step > id;

                            return (
                                <li key={id} className="relative flex flex-1 flex-col">
                                    {index > 0 && (
                                        <span
                                            className={cn(
                                                'absolute top-4 right-1/2 left-[-50%] z-0 h-px',
                                                done || active ? 'bg-teal-600/50' : 'bg-border',
                                            )}
                                            aria-hidden
                                        />
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (id < step) setStep(id);
                                            else if (id === 2 && canProceedStep1) setStep(2);
                                            else if (id === 3 && canProceedStep1 && canProceedStep2) setStep(3);
                                        }}
                                        className="relative z-10 flex flex-col items-center gap-2 px-1 text-center"
                                    >
                                        <span
                                            className={cn(
                                                'flex h-8 w-8 items-center justify-center rounded-full border text-xs font-semibold transition-colors',
                                                active && 'border-teal-700 bg-teal-700 text-white shadow-sm',
                                                done && !active && 'border-teal-600 bg-teal-600 text-white',
                                                !active && !done && 'border-border bg-background text-muted-foreground',
                                            )}
                                        >
                                            {done && !active ? <Check className="h-3.5 w-3.5" /> : <Icon className="h-3.5 w-3.5" />}
                                        </span>
                                        <span className="min-w-0">
                                            <span
                                                className={cn(
                                                    'block text-xs font-semibold sm:text-sm',
                                                    active ? 'text-teal-800 dark:text-teal-300' : 'text-foreground',
                                                )}
                                            >
                                                {label}
                                            </span>
                                            <span className="mt-0.5 hidden text-[11px] text-muted-foreground sm:block">{hint}</span>
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ol>
                </nav>

                <form onSubmit={submit} className="space-y-5">
                    {step === 1 && (
                        <section className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                            <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                <h2 className="text-base font-semibold tracking-tight">Apa yang ditambahkan?</h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    Pilih master yang sudah ada, atau daftarkan tipe barang baru.
                                </p>
                            </div>
                            <div className="space-y-5 p-5">
                                <div className="grid grid-cols-2 gap-3">
                                    <ModeCard
                                        active={mode === 'existing'}
                                        icon={Layers}
                                        title="Barang sudah ada"
                                        desc="Tambah unit baru dari master"
                                        onClick={() => switchMode('existing')}
                                    />
                                    <ModeCard
                                        active={mode === 'new'}
                                        icon={Plus}
                                        title="Barang baru"
                                        desc="Buat master + unit sekaligus"
                                        onClick={() => switchMode('new')}
                                    />
                                </div>

                                {mode === 'existing' ? (
                                    <div className="space-y-2">
                                        <Label htmlFor="aset_barang_id">
                                            Cari barang <span className="text-destructive">*</span>
                                        </Label>
                                        <SearchSelect
                                            inputId="aset_barang_id"
                                            options={barangOptions}
                                            value={data.aset_barang_id}
                                            onChange={onBarangSelect}
                                            placeholder="Ketik nama barang..."
                                            noOptionsMessage="Barang tidak ditemukan"
                                            hasError={Boolean(errors.aset_barang_id)}
                                        />
                                        <InputError message={errors.aset_barang_id} />
                                        {selectedBarang && (
                                            <div className="flex flex-wrap items-center gap-2 rounded-lg border border-dashed border-teal-700/25 bg-teal-50/50 px-3 py-2.5 dark:bg-teal-950/20">
                                                <Badge variant="secondary" className="font-mono text-[11px]">
                                                    {selectedBarang.kode_barang}
                                                </Badge>
                                                {selectedBarang.kelas_aset && (
                                                    <Badge variant="outline">{selectedBarang.kelas_aset}</Badge>
                                                )}
                                                <span className="text-xs text-muted-foreground">
                                                    Merk & kategori mengikuti master ini
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="nama_barang">
                                                Nama barang <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="nama_barang"
                                                value={data.nama_barang}
                                                onChange={(e) => setData('nama_barang', e.target.value)}
                                                placeholder="Contoh: Laptop Dell Latitude 5420"
                                                className="h-10"
                                            />
                                            <InputError message={errors.nama_barang} />
                                        </div>

                                        <p className="text-xs leading-relaxed text-muted-foreground">
                                            Kategori, tipe, merk, produsen — ketik lalu pilih{' '}
                                            <span className="font-medium text-foreground">Tambah &quot;…&quot;</span> jika belum ada.
                                        </p>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <CreatableField
                                                label="Kategori"
                                                inputId="aset_kategori_id"
                                                options={kategoriOptions}
                                                value={data.aset_kategori_id}
                                                onChange={(v) => setData('aset_kategori_id', v)}
                                                onCreate={(n) => createMaster('kategori', n)}
                                                isCreating={creatingMaster === 'kategori'}
                                            />
                                            <CreatableField
                                                label="Tipe / jenis"
                                                inputId="aset_jenis_id"
                                                options={jenisOptions}
                                                value={data.aset_jenis_id}
                                                onChange={(v) => setData('aset_jenis_id', v)}
                                                onCreate={(n) => createMaster('jenis', n)}
                                                isCreating={creatingMaster === 'jenis'}
                                            />
                                            <CreatableField
                                                label="Merk"
                                                inputId="aset_merk_id"
                                                options={merkOptions}
                                                value={data.aset_merk_id}
                                                onChange={(v) => setData('aset_merk_id', v)}
                                                onCreate={(n) => createMaster('merk', n)}
                                                isCreating={creatingMaster === 'merk'}
                                            />
                                            <CreatableField
                                                label="Produsen"
                                                inputId="aset_produsen_id"
                                                options={produsenOptions}
                                                value={data.aset_produsen_id}
                                                onChange={(v) => setData('aset_produsen_id', v)}
                                                onCreate={(n) => createMaster('produsen', n)}
                                                isCreating={creatingMaster === 'produsen'}
                                            />
                                        </div>
                                        {masterError && <p className="text-sm text-destructive">{masterError}</p>}

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Kelas aset</Label>
                                                <Select
                                                    value={data.kelas_aset || '__none__'}
                                                    onValueChange={(v) => setData('kelas_aset', v === '__none__' ? '' : v)}
                                                >
                                                    <SelectTrigger className="h-10"><SelectValue placeholder="Pilih kelas" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="__none__">Belum diisi</SelectItem>
                                                        <SelectItem value="medis">Medis</SelectItem>
                                                        <SelectItem value="non_medis">Non-medis</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="flex items-end gap-2 pb-2">
                                                <Checkbox
                                                    checked={data.wajib_kalibrasi}
                                                    onCheckedChange={(c) => setData('wajib_kalibrasi', Boolean(c))}
                                                    id="wk"
                                                />
                                                <Label htmlFor="wk" className="cursor-pointer font-normal">
                                                    Wajib kalibrasi
                                                </Label>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </section>
                    )}

                    {step === 2 && (
                        <section className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                            <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                <h2 className="text-base font-semibold tracking-tight">Di mana & berapa unit?</h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    Ruang wajib. Serial boleh kosong — dilengkapi nanti di edit.
                                </p>
                            </div>
                            <div className="space-y-5 p-5">
                                {summaryLabel && (
                                    <div className="flex items-center gap-2.5 rounded-lg bg-muted/40 px-3 py-2.5 text-sm">
                                        <Box className="h-4 w-4 shrink-0 text-teal-700 dark:text-teal-400" />
                                        <span className="font-medium">{summaryLabel}</span>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="aset_ruang_id">
                                        Ruang / lokasi <span className="text-destructive">*</span>
                                    </Label>
                                    <SearchSelect
                                        inputId="aset_ruang_id"
                                        options={ruangOptions}
                                        value={data.aset_ruang_id}
                                        onChange={(v) => setData('aset_ruang_id', v)}
                                        placeholder="Ketik nama ruang..."
                                        isClearable={false}
                                        hasError={Boolean(errors.aset_ruang_id)}
                                    />
                                    <InputError message={errors.aset_ruang_id} />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field>
                                        <Label htmlFor="jumlah_unit">Jumlah unit</Label>
                                        <Input
                                            id="jumlah_unit"
                                            type="number"
                                            min={1}
                                            max={50}
                                            value={data.jumlah_unit}
                                            onChange={(e) => setData('jumlah_unit', e.target.value)}
                                            className="h-10"
                                        />
                                        <p className="text-[11px] text-muted-foreground">Maks. 50 · tiap unit dapat kode & QR sendiri</p>
                                    </Field>
                                    <Field>
                                        <Label htmlFor="tahun_registrasi">Tahun registrasi</Label>
                                        <Input
                                            id="tahun_registrasi"
                                            value={data.tahun_registrasi}
                                            onChange={(e) => setData('tahun_registrasi', e.target.value)}
                                            className="h-10"
                                        />
                                    </Field>
                                    <Field>
                                        <Label htmlFor="harga">Harga satuan (Rp)</Label>
                                        <Input
                                            id="harga"
                                            value={data.harga}
                                            onChange={(e) => setData('harga', e.target.value)}
                                            placeholder="Opsional"
                                            className="h-10"
                                        />
                                    </Field>
                                    <Field>
                                        <Label>Asal barang</Label>
                                        <Select
                                            value={data.asal_barang || '__none__'}
                                            onValueChange={(v) => setData('asal_barang', v === '__none__' ? '' : v)}
                                        >
                                            <SelectTrigger className="h-10"><SelectValue placeholder="Pilih asal" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__none__">Belum diisi</SelectItem>
                                                <SelectItem value="Beli">Beli</SelectItem>
                                                <SelectItem value="Bantuan">Bantuan</SelectItem>
                                                <SelectItem value="Hibah">Hibah</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field>
                                        <Label htmlFor="tanggal_pengadaan">Tanggal pengadaan</Label>
                                        <Input
                                            id="tanggal_pengadaan"
                                            type="date"
                                            value={data.tanggal_pengadaan}
                                            onChange={(e) => setData('tanggal_pengadaan', e.target.value)}
                                            className="h-10"
                                        />
                                    </Field>
                                    <Field>
                                        <Label>Status fungsi</Label>
                                        <Select value={data.status_fungsi} onValueChange={(v) => setData('status_fungsi', v)}>
                                            <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="berfungsi">Berfungsi</SelectItem>
                                                <SelectItem value="tidak_berfungsi">Tidak berfungsi</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field>
                                        <Label>Kondisi fisik</Label>
                                        <Select value={data.tingkat_kerusakan} onValueChange={(v) => setData('tingkat_kerusakan', v)}>
                                            <SelectTrigger className="h-10"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="baik">Baik</SelectItem>
                                                <SelectItem value="rusak_ringan">Rusak ringan</SelectItem>
                                                <SelectItem value="rusak_berat">Rusak berat</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                </div>

                                {jumlah === 1 ? (
                                    <Field>
                                        <Label htmlFor="no_seri_0">Nomor seri (opsional)</Label>
                                        <Input
                                            id="no_seri_0"
                                            value={serialInputs[0] ?? ''}
                                            placeholder="Nomor seri pabrik"
                                            onChange={(e) => setData('no_seri_list', [e.target.value])}
                                            className="h-10"
                                        />
                                        <InputError message={errors.no_seri_list} />
                                    </Field>
                                ) : (
                                    <Collapsible defaultOpen>
                                        <CollapsibleTrigger className="flex w-full items-center justify-between rounded-lg border bg-muted/25 px-4 py-3 text-sm font-medium hover:bg-muted/40">
                                            <span>Nomor seri · {jumlah} unit</span>
                                            <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                        </CollapsibleTrigger>
                                        <CollapsibleContent className="space-y-3 pt-3">
                                            <p className="text-xs text-muted-foreground">Boleh dikosongkan sebagian.</p>
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                {serialInputs.map((serial, i) => (
                                                    <Field key={i}>
                                                        <Label>Unit {i + 1}</Label>
                                                        <Input
                                                            value={serial}
                                                            placeholder="SN pabrik"
                                                            onChange={(e) => {
                                                                const next = [...noSeriList];
                                                                next[i] = e.target.value;
                                                                setData('no_seri_list', next);
                                                            }}
                                                            className="h-10"
                                                        />
                                                    </Field>
                                                ))}
                                            </div>
                                            <InputError message={errors.no_seri_list} />
                                        </CollapsibleContent>
                                    </Collapsible>
                                )}
                            </div>
                        </section>
                    )}

                    {step === 3 && (
                        <section className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                            <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                <h2 className="text-base font-semibold tracking-tight">Detail tambahan</h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">Semua opsional — boleh dilewati.</p>
                            </div>
                            <div className="space-y-5 p-5">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label htmlFor="aset_aspak_alat_id">Kode ASPAK</Label>
                                        <SearchSelect
                                            inputId="aset_aspak_alat_id"
                                            options={aspakOptions}
                                            value={data.aset_aspak_alat_id}
                                            onChange={(v) => setData('aset_aspak_alat_id', v)}
                                            placeholder="Cari nomenklatur ASPAK..."
                                            noOptionsMessage="ASPAK tidak ditemukan"
                                        />
                                    </div>
                                    <CreatableField
                                        label="Distributor"
                                        inputId="aset_distributor_id"
                                        options={distributorOptions}
                                        value={data.aset_distributor_id}
                                        onChange={(v) => setData('aset_distributor_id', v)}
                                        onCreate={(n) => createMaster('distributor', n)}
                                        isCreating={creatingMaster === 'distributor'}
                                    />
                                    <Field>
                                        <Label htmlFor="no_akl_akd">No. AKL/AKD</Label>
                                        <Input id="no_akl_akd" value={data.no_akl_akd} onChange={(e) => setData('no_akl_akd', e.target.value)} className="h-10" />
                                    </Field>
                                    <Field>
                                        <Label htmlFor="daya_watt">Daya (Watt)</Label>
                                        <Input id="daya_watt" value={data.daya_watt} onChange={(e) => setData('daya_watt', e.target.value)} className="h-10" />
                                    </Field>
                                    <Field>
                                        <Label>Level teknologi</Label>
                                        <Select
                                            value={data.level_teknologi || '__none__'}
                                            onValueChange={(v) => setData('level_teknologi', v === '__none__' ? '' : v)}
                                        >
                                            <SelectTrigger className="h-10"><SelectValue placeholder="Pilih level" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__none__">Belum diisi</SelectItem>
                                                <SelectItem value="low">Low</SelectItem>
                                                <SelectItem value="medium">Medium</SelectItem>
                                                <SelectItem value="high">High</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field>
                                        <Label htmlFor="foto">Foto referensi</Label>
                                        <Input
                                            id="foto"
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) => setData('foto', e.target.files?.[0] ?? null)}
                                            className="h-10"
                                        />
                                    </Field>
                                </div>

                                <Collapsible open={advancedOpen} onOpenChange={setAdvancedOpen}>
                                    <CollapsibleTrigger className="flex w-full items-center justify-between rounded-lg border px-4 py-3 text-sm text-muted-foreground hover:bg-muted/30">
                                        <span>Regulasi lanjutan</span>
                                        <ChevronDown className={cn('h-4 w-4 transition-transform', advancedOpen && 'rotate-180')} />
                                    </CollapsibleTrigger>
                                    <CollapsibleContent className="grid gap-4 pt-4 sm:grid-cols-2">
                                        <Field>
                                            <Label htmlFor="tahun_produksi">Tahun produksi</Label>
                                            <Input id="tahun_produksi" value={data.tahun_produksi} onChange={(e) => setData('tahun_produksi', e.target.value)} className="h-10" />
                                        </Field>
                                        <Field>
                                            <Label htmlFor="tahun_mulai_operasi">Tahun mulai operasi</Label>
                                            <Input id="tahun_mulai_operasi" value={data.tahun_mulai_operasi} onChange={(e) => setData('tahun_mulai_operasi', e.target.value)} className="h-10" />
                                        </Field>
                                        <Field>
                                            <Label htmlFor="umur_ekonomis_bulan">Umur ekonomis (bulan)</Label>
                                            <Input id="umur_ekonomis_bulan" value={data.umur_ekonomis_bulan} onChange={(e) => setData('umur_ekonomis_bulan', e.target.value)} className="h-10" />
                                        </Field>
                                        <Field>
                                            <Label htmlFor="nilai_residu">Nilai residu (Rp)</Label>
                                            <Input id="nilai_residu" value={data.nilai_residu} onChange={(e) => setData('nilai_residu', e.target.value)} className="h-10" />
                                        </Field>
                                    </CollapsibleContent>
                                </Collapsible>
                            </div>
                        </section>
                    )}

                    {/* Sticky footer */}
                    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-border/80 bg-background/90 backdrop-blur-md">
                        <div className="mx-auto flex max-w-2xl items-center justify-between gap-3 px-4 py-3">
                            <div className="min-w-0 truncate text-xs text-muted-foreground sm:text-sm">
                                <span className="font-medium text-foreground">{step}/3</span>
                                {summaryLabel ? ` · ${summaryLabel}` : ''}
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                {step > 1 && (
                                    <Button type="button" variant="outline" size="sm" onClick={() => setStep((s) => s - 1)}>
                                        <ChevronLeft className="mr-0.5 h-4 w-4" />
                                        Kembali
                                    </Button>
                                )}
                                {step < 3 ? (
                                    <>
                                        {step === 2 && (
                                            <Button type="submit" variant="secondary" size="sm" disabled={!canSubmit}>
                                                Simpan cepat
                                            </Button>
                                        )}
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={goNext}
                                            disabled={(step === 1 && !canProceedStep1) || (step === 2 && !canProceedStep2)}
                                            className="bg-teal-700 hover:bg-teal-800"
                                        >
                                            Lanjut
                                            <ChevronRight className="ml-0.5 h-4 w-4" />
                                        </Button>
                                    </>
                                ) : (
                                    <Button type="submit" size="sm" disabled={!canSubmit} className="bg-teal-700 hover:bg-teal-800">
                                        {processing ? 'Menyimpan...' : 'Simpan aset'}
                                    </Button>
                                )}
                                <Button type="button" variant="ghost" size="sm" asChild className="hidden sm:inline-flex">
                                    <Link href="/aset">Batal</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

function ModeCard({
    active,
    icon: Icon,
    title,
    desc,
    onClick,
}: {
    active: boolean;
    icon: typeof Layers;
    title: string;
    desc: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex flex-col items-start gap-2 rounded-lg border-2 p-4 text-left transition-all',
                active
                    ? 'border-teal-700 bg-teal-50/60 ring-1 ring-teal-700/15 dark:border-teal-500 dark:bg-teal-950/30'
                    : 'border-transparent bg-muted/40 hover:bg-muted/65',
            )}
        >
            <Icon className={cn('h-5 w-5', active ? 'text-teal-700 dark:text-teal-400' : 'text-muted-foreground')} />
            <span className="text-sm font-semibold">{title}</span>
            <span className="text-[11px] leading-snug text-muted-foreground">{desc}</span>
        </button>
    );
}

function Field({ children }: { children: React.ReactNode }) {
    return <div className="space-y-2">{children}</div>;
}

function CreatableField({
    label,
    inputId,
    options,
    value,
    onChange,
    onCreate,
    isCreating = false,
}: {
    label: string;
    inputId: string;
    options: SearchSelectOption[];
    value: string;
    onChange: (v: string) => void;
    onCreate: (nama: string) => Promise<SearchSelectOption | null>;
    isCreating?: boolean;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={inputId}>{label}</Label>
            <CreatableSearchSelect
                inputId={inputId}
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
