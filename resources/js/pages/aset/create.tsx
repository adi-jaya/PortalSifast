import { Head, Link, useForm } from '@inertiajs/react';
import { Box, Check, ChevronDown, HeartPulse, Monitor } from 'lucide-react';
import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { AspakLeafSearchSelect, type AspakOption } from '@/components/aset/aspak-leaf-search-select';
import { NonAlkesLeafSearchSelect, type NonAlkesOption } from '@/components/aset/non-alkes-leaf-search-select';
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
    aset_merk_id?: number | null;
};

type Props = {
    ruang: { id: number; kode_ruang: string; nama_ruang: string }[];
    kategori: MasterOpt[];
    jenis: MasterOpt[];
    merk: MasterOpt[];
    produsen: MasterOpt[];
    distributor: MasterOpt[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Tambah', href: '/aset/create' },
];

function toOptions(items: { id: number; label: string; description?: string }[]): SearchSelectOption[] {
    return items.map((item) => ({
        value: String(item.id),
        label: item.label,
        description: item.description,
    }));
}

export default function AsetCreate({
    ruang,
    kategori,
    jenis,
    merk,
    produsen,
    distributor,
}: Props) {
    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [kategoriList, setKategoriList] = useState(kategori);
    const [jenisList, setJenisList] = useState(jenis);
    const [merkList, setMerkList] = useState(merk);
    const [produsenList, setProdusenList] = useState(produsen);
    const [distributorList, setDistributorList] = useState(distributor);
    const [creatingMaster, setCreatingMaster] = useState<string | null>(null);
    const [masterError, setMasterError] = useState<string | null>(null);
    const [selectedNonAlkes, setSelectedNonAlkes] = useState<NonAlkesOption | null>(null);
    const [selectedAspak, setSelectedAspak] = useState<AspakOption | null>(null);

    const { data, setData, post, processing, errors, transform } = useForm({
        nama_barang: '',
        aset_kategori_id: '',
        aset_jenis_id: '',
        aset_merk_id: '',
        aset_produsen_id: '',
        aset_aspak_alat_id: '',
        aset_non_alkes_id: '',
        aset_ruang_id: '',
        aset_ruang_id_list: [''] as string[],
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
    const ruangIdList = useMemo(
        () => (Array.isArray(data.aset_ruang_id_list) ? data.aset_ruang_id_list : ['']),
        [data.aset_ruang_id_list],
    );

    useEffect(() => {
        setData((d) => {
            const currentSeri = Array.isArray(d.no_seri_list) ? d.no_seri_list : [''];
            const currentRuang = Array.isArray(d.aset_ruang_id_list) ? d.aset_ruang_id_list : [''];
            const nextSeri = [...currentSeri];
            const nextRuang = [...currentRuang];
            while (nextSeri.length < jumlah) nextSeri.push('');
            while (nextSeri.length > jumlah) nextSeri.pop();
            while (nextRuang.length < jumlah) nextRuang.push('');
            while (nextRuang.length > jumlah) nextRuang.pop();

            const seriSame =
                nextSeri.length === currentSeri.length && nextSeri.every((v, i) => v === currentSeri[i]);
            const ruangSame =
                nextRuang.length === currentRuang.length && nextRuang.every((v, i) => v === currentRuang[i]);
            if (seriSame && ruangSame) {
                return d;
            }

            return {
                ...d,
                no_seri_list: nextSeri,
                aset_ruang_id_list: nextRuang,
                aset_ruang_id: nextRuang[0] ?? '',
            };
        });
    }, [jumlah, setData]);

    const ruangOptions = useMemo(
        () => ruang.map((r) => ({ value: String(r.id), label: r.nama_ruang, description: r.kode_ruang })),
        [ruang],
    );
    const kategoriOptions = useMemo(
        () => toOptions(kategoriList.map((k) => ({ id: k.id, label: k.nama_kategori ?? '' }))),
        [kategoriList],
    );
    const jenisOptions = useMemo(() => {
        const merkId = data.aset_merk_id ? Number(data.aset_merk_id) : null;
        const filtered = jenisList.filter((j) => {
            if (!merkId) {
                return true;
            }

            return j.aset_merk_id == null || j.aset_merk_id === merkId;
        });

        return toOptions(filtered.map((j) => ({ id: j.id, label: j.nama_jenis ?? '' })));
    }, [jenisList, data.aset_merk_id]);
    const merkOptions = useMemo(
        () =>
            toOptions(
                merkList.map((m) => ({
                    id: m.id,
                    label: m.nama_merk?.trim() || m.kode_merk || `Merk #${m.id}`,
                    description: m.kode_merk && m.nama_merk?.trim() !== m.kode_merk ? m.kode_merk : undefined,
                })),
            ),
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

    const createMaster = useCallback(
        async (tipe: AsetMasterTipe, nama: string): Promise<SearchSelectOption | null> => {
            setCreatingMaster(tipe);
            setMasterError(null);
            try {
                const merkId = data.aset_merk_id ? Number(data.aset_merk_id) : null;
                const result = await quickCreateAsetMaster(
                    tipe,
                    nama,
                    tipe === 'jenis' ? { aset_merk_id: merkId } : {},
                );
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
                    setKategoriList((prev) =>
                        prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_kategori: namaVal, kode_kategori: kode }],
                    );
                } else if (tipe === 'jenis') {
                    setJenisList((prev) =>
                        prev.some((x) => x.id === id)
                            ? prev
                            : [
                                  ...prev,
                                  {
                                      id,
                                      nama_jenis: namaVal,
                                      kode_jenis: kode,
                                      aset_merk_id: result.item.aset_merk_id ?? merkId,
                                  },
                              ],
                    );
                } else if (tipe === 'merk') {
                    setMerkList((prev) =>
                        prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_merk: namaVal, kode_merk: kode }],
                    );
                } else if (tipe === 'produsen') {
                    setProdusenList((prev) =>
                        prev.some((x) => x.id === id) ? prev : [...prev, { id, nama_produsen: namaVal, kode_produsen: kode }],
                    );
                } else {
                    setDistributorList((prev) =>
                        prev.some((x) => x.id === id)
                            ? prev
                            : [...prev, { id, nama_distributor: namaVal, kode_distributor: kode }],
                    );
                }

                return option;
            } finally {
                setCreatingMaster(null);
            }
        },
        [data.aset_merk_id],
    );

    const onMerkChange = (value: string) => {
        setData((d) => {
            const nextMerk = value;
            const currentJenis = jenisList.find((j) => String(j.id) === d.aset_jenis_id);
            const merkNum = nextMerk ? Number(nextMerk) : null;
            const jenisOk =
                !d.aset_jenis_id ||
                !merkNum ||
                !currentJenis ||
                currentJenis.aset_merk_id == null ||
                currentJenis.aset_merk_id === merkNum;

            return {
                ...d,
                aset_merk_id: nextMerk,
                aset_jenis_id: jenisOk ? d.aset_jenis_id : '',
            };
        });
    };

    const switchKelas = (kelas: 'medis' | 'non_medis') => {
        setSelectedAspak(null);
        setSelectedNonAlkes(null);
        setData((d) => ({
            ...d,
            kelas_aset: kelas,
            aset_aspak_alat_id: '',
            aset_non_alkes_id: '',
            nama_barang: '',
            wajib_kalibrasi: false,
            aset_kategori_id: '',
            aset_merk_id: '',
            aset_jenis_id: '',
            aset_produsen_id: '',
        }));
    };

    const onAspakSelect = (option: AspakOption | null) => {
        setSelectedAspak(option);
        setSelectedNonAlkes(null);
        setData((d) => ({
            ...d,
            aset_aspak_alat_id: option ? String(option.id) : '',
            nama_barang: option?.nama_alat ?? '',
            wajib_kalibrasi: option ? option.wajib_kalibrasi : false,
            kelas_aset: 'medis',
            aset_non_alkes_id: '',
        }));
    };

    const onNonAlkesSelect = (option: NonAlkesOption | null) => {
        setSelectedNonAlkes(option);
        setSelectedAspak(null);
        setData((d) => ({
            ...d,
            aset_non_alkes_id: option ? String(option.id) : '',
            nama_barang: option?.nama_alat ?? '',
            kelas_aset: 'non_medis',
            aset_aspak_alat_id: '',
            wajib_kalibrasi: false,
            aset_kategori_id: option?.aset_kategori_id ? String(option.aset_kategori_id) : '',
        }));
    };

    const hasKatalog =
        data.kelas_aset === 'medis'
            ? Boolean(data.aset_aspak_alat_id)
            : data.kelas_aset === 'non_medis'
              ? Boolean(data.aset_non_alkes_id)
              : false;
    const unitSlots = useMemo(
        () =>
            Array.from({ length: jumlah }, (_, i) => ({
                ruangId: ruangIdList[i] ?? '',
                noSeri: noSeriList[i] ?? '',
            })),
        [jumlah, ruangIdList, noSeriList],
    );
    const allRuangFilled = unitSlots.every((slot) => Boolean(slot.ruangId));
    const canSubmit = hasKatalog && allRuangFilled && !processing;

    const setUnitRuang = (index: number, value: string) => {
        setData((d) => {
            const next = [...(Array.isArray(d.aset_ruang_id_list) ? d.aset_ruang_id_list : [''])];
            while (next.length <= index) {
                next.push('');
            }
            next[index] = value;

            return {
                ...d,
                aset_ruang_id_list: next,
                aset_ruang_id: next[0] ?? '',
            };
        });
    };

    const setUnitSeri = (index: number, value: string) => {
        setData((d) => {
            const next = [...(Array.isArray(d.no_seri_list) ? d.no_seri_list : [''])];
            while (next.length <= index) {
                next.push('');
            }
            next[index] = value;

            return { ...d, no_seri_list: next };
        });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((d) => {
            const unitCount = Number(d.jumlah_unit) || 1;
            const ruangList = (Array.isArray(d.aset_ruang_id_list) ? d.aset_ruang_id_list : [])
                .slice(0, unitCount)
                .map((id) => (id ? Number(id) : null));

            return {
                ...d,
                aset_ruang_id: ruangList[0] ?? (d.aset_ruang_id ? Number(d.aset_ruang_id) : null),
                aset_ruang_id_list: ruangList,
                aset_distributor_id: d.aset_distributor_id ? Number(d.aset_distributor_id) : null,
                aset_kategori_id: d.aset_kategori_id ? Number(d.aset_kategori_id) : null,
                aset_jenis_id: d.aset_jenis_id ? Number(d.aset_jenis_id) : null,
                aset_merk_id: d.aset_merk_id ? Number(d.aset_merk_id) : null,
                aset_produsen_id: d.aset_produsen_id ? Number(d.aset_produsen_id) : null,
                aset_aspak_alat_id: d.aset_aspak_alat_id ? Number(d.aset_aspak_alat_id) : null,
                aset_non_alkes_id: d.aset_non_alkes_id ? Number(d.aset_non_alkes_id) : null,
                tahun_registrasi: Number(d.tahun_registrasi),
                tahun_produksi: d.tahun_produksi ? Number(d.tahun_produksi) : null,
                tahun_mulai_operasi: d.tahun_mulai_operasi ? Number(d.tahun_mulai_operasi) : null,
                jumlah_unit: unitCount,
                harga: d.harga !== '' ? Number(d.harga) : null,
                umur_ekonomis_bulan: d.umur_ekonomis_bulan ? Number(d.umur_ekonomis_bulan) : null,
                daya_watt: d.daya_watt ? Number(d.daya_watt) : null,
                nilai_residu: d.nilai_residu !== '' ? Number(d.nilai_residu) : null,
                kelas_aset: d.kelas_aset || null,
                level_teknologi: d.level_teknologi || null,
                no_seri_list: (Array.isArray(d.no_seri_list) ? d.no_seri_list : [])
                    .slice(0, unitCount)
                    .map((s) => String(s).trim()),
            };
        });
        post('/aset', { forceFormData: Boolean(data.foto) });
    };

    const summaryLabel =
        data.nama_barang ||
        (data.kelas_aset === 'medis' ? 'Aset medis' : data.kelas_aset === 'non_medis' ? 'Aset non-medis' : 'Barang');
    const selectedKode =
        data.kelas_aset === 'medis'
            ? (selectedAspak?.kode ?? selectedAspak?.id_alat_aspak)
            : (selectedNonAlkes?.kode ?? selectedNonAlkes?.id_alat);
    const filledRuangCount = unitSlots.filter((slot) => Boolean(slot.ruangId)).length;
    const uniqueRuangNames = [
        ...new Set(
            unitSlots
                .map((slot) => ruang.find((r) => String(r.id) === slot.ruangId)?.nama_ruang)
                .filter((name): name is string => Boolean(name)),
        ),
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Aset" />

            <div className="relative mx-auto max-w-2xl px-4 pb-28 pt-2 sm:px-0">
                <header className="mb-6 border-b border-border/70 pb-5">
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris portal
                    </p>
                    <h1 className="text-[1.75rem] leading-tight font-semibold tracking-tight text-foreground">
                        Tambah aset
                    </h1>
                    <p className="mt-1.5 max-w-lg text-sm leading-relaxed text-muted-foreground">
                        Katalog → detail barang → jumlah → ruang & seri per unit.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-5">
                    <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                        <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                            <h2 className="text-base font-semibold tracking-tight">1. Apa yang ditambah?</h2>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                Medis pakai ASPAK · non-medis pakai katalog non-alkes.
                            </p>
                        </div>
                        <div className="space-y-5 p-5">
                            <div className="grid grid-cols-2 gap-3">
                                <ModeCard
                                    active={data.kelas_aset === 'medis'}
                                    icon={HeartPulse}
                                    title="Aset medis"
                                    desc="Katalog ASPAK"
                                    onClick={() => switchKelas('medis')}
                                />
                                <ModeCard
                                    active={data.kelas_aset === 'non_medis'}
                                    icon={Monitor}
                                    title="Aset non-medis"
                                    desc="Katalog non-alkes"
                                    onClick={() => switchKelas('non_medis')}
                                />
                            </div>

                            {data.kelas_aset === 'medis' && (
                                <div className="space-y-2">
                                    <Label htmlFor="aset_aspak_alat_id">
                                        Katalog ASPAK <span className="text-destructive">*</span>
                                    </Label>
                                    <AspakLeafSearchSelect
                                        inputId="aset_aspak_alat_id"
                                        value={selectedAspak}
                                        onChange={onAspakSelect}
                                        hasError={Boolean(errors.aset_aspak_alat_id)}
                                    />
                                    <InputError message={errors.aset_aspak_alat_id} />
                                </div>
                            )}

                            {data.kelas_aset === 'non_medis' && (
                                <div className="space-y-2">
                                    <Label htmlFor="aset_non_alkes_id">
                                        Katalog non-alkes <span className="text-destructive">*</span>
                                    </Label>
                                    <NonAlkesLeafSearchSelect
                                        inputId="aset_non_alkes_id"
                                        value={selectedNonAlkes}
                                        onChange={onNonAlkesSelect}
                                        hasError={Boolean(errors.aset_non_alkes_id)}
                                    />
                                    <InputError message={errors.aset_non_alkes_id} />
                                </div>
                            )}

                            {hasKatalog && (
                                <div className="flex flex-wrap items-center gap-2 rounded-lg border border-dashed border-teal-700/25 bg-teal-50/50 px-3 py-2.5 dark:bg-teal-950/20">
                                    <Box className="h-4 w-4 shrink-0 text-teal-700 dark:text-teal-400" />
                                    <span className="text-sm font-medium">{summaryLabel}</span>
                                    {selectedKode ? (
                                        <Badge variant="secondary" className="font-mono text-[11px]">
                                            {selectedKode}
                                        </Badge>
                                    ) : null}
                                    <Badge variant="outline">
                                        {data.kelas_aset === 'medis' ? 'medis' : 'non-medis'}
                                    </Badge>
                                </div>
                            )}
                        </div>
                    </section>

                    {hasKatalog && (
                        <>
                            <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                                <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                    <h2 className="text-base font-semibold tracking-tight">2. Detail barang</h2>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        Sama untuk semua unit — merk, tipe, harga, distributor.
                                    </p>
                                </div>
                                <div className="space-y-5 p-5">
                                    <div className="space-y-2">
                                        <Label htmlFor="nama_barang">Nama barang</Label>
                                        <Input
                                            id="nama_barang"
                                            value={data.nama_barang}
                                            onChange={(e) => setData('nama_barang', e.target.value)}
                                            placeholder="Otomatis dari katalog"
                                            className="h-10"
                                        />
                                        <InputError message={errors.nama_barang} />
                                    </div>

                                    {data.kelas_aset === 'medis' && (
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                checked={data.wajib_kalibrasi}
                                                onCheckedChange={(c) => setData('wajib_kalibrasi', Boolean(c))}
                                                id="wk"
                                            />
                                            <Label htmlFor="wk" className="cursor-pointer font-normal">
                                                Wajib kalibrasi
                                            </Label>
                                        </div>
                                    )}

                                    <p className="text-xs text-muted-foreground">
                                        Pilih merk dulu agar tipe terfilter. Field di bawah boleh kosong.
                                    </p>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <CreatableField
                                            label="Merk"
                                            inputId="aset_merk_id"
                                            options={merkOptions}
                                            value={data.aset_merk_id}
                                            onChange={onMerkChange}
                                            onCreate={(n) => createMaster('merk', n)}
                                            isCreating={creatingMaster === 'merk'}
                                        />
                                        <CreatableField
                                            label="Tipe"
                                            inputId="aset_jenis_id"
                                            options={jenisOptions}
                                            value={data.aset_jenis_id}
                                            onChange={(v) => setData('aset_jenis_id', v)}
                                            onCreate={(n) => createMaster('jenis', n)}
                                            isCreating={creatingMaster === 'jenis'}
                                        />
                                        {data.kelas_aset === 'non_medis' && (
                                            <>
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
                                                    label="Produsen"
                                                    inputId="aset_produsen_id"
                                                    options={produsenOptions}
                                                    value={data.aset_produsen_id}
                                                    onChange={(v) => setData('aset_produsen_id', v)}
                                                    onCreate={(n) => createMaster('produsen', n)}
                                                    isCreating={creatingMaster === 'produsen'}
                                                />
                                            </>
                                        )}
                                        {data.kelas_aset === 'medis' && (
                                            <CreatableField
                                                label="Produsen"
                                                inputId="aset_produsen_id_medis"
                                                options={produsenOptions}
                                                value={data.aset_produsen_id}
                                                onChange={(v) => setData('aset_produsen_id', v)}
                                                onCreate={(n) => createMaster('produsen', n)}
                                                isCreating={creatingMaster === 'produsen'}
                                            />
                                        )}
                                    </div>
                                    <InputError message={errors.aset_merk_id} />
                                    <InputError message={errors.aset_jenis_id} />
                                    {masterError && <p className="text-sm text-destructive">{masterError}</p>}

                                    <div className="grid gap-4 sm:grid-cols-2">
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
                                                <SelectTrigger className="h-10">
                                                    <SelectValue placeholder="Pilih asal" />
                                                </SelectTrigger>
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
                                            <Select
                                                value={data.status_fungsi}
                                                onValueChange={(v) => setData('status_fungsi', v)}
                                            >
                                                <SelectTrigger className="h-10">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="berfungsi">Berfungsi</SelectItem>
                                                    <SelectItem value="tidak_berfungsi">Tidak berfungsi</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </Field>
                                        <Field>
                                            <Label>Kondisi fisik</Label>
                                            <Select
                                                value={data.tingkat_kerusakan}
                                                onValueChange={(v) => setData('tingkat_kerusakan', v)}
                                            >
                                                <SelectTrigger className="h-10">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="baik">Baik</SelectItem>
                                                    <SelectItem value="rusak_ringan">Rusak ringan</SelectItem>
                                                    <SelectItem value="rusak_berat">Rusak berat</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
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
                                            <Input
                                                id="no_akl_akd"
                                                value={data.no_akl_akd}
                                                onChange={(e) => setData('no_akl_akd', e.target.value)}
                                                className="h-10"
                                            />
                                        </Field>
                                        <Field>
                                            <Label htmlFor="daya_watt">Daya (Watt)</Label>
                                            <Input
                                                id="daya_watt"
                                                value={data.daya_watt}
                                                onChange={(e) => setData('daya_watt', e.target.value)}
                                                className="h-10"
                                            />
                                        </Field>
                                        <Field>
                                            <Label>Level teknologi</Label>
                                            <Select
                                                value={data.level_teknologi || '__none__'}
                                                onValueChange={(v) =>
                                                    setData('level_teknologi', v === '__none__' ? '' : v)
                                                }
                                            >
                                                <SelectTrigger className="h-10">
                                                    <SelectValue placeholder="Pilih level" />
                                                </SelectTrigger>
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
                                            <ChevronDown
                                                className={cn(
                                                    'h-4 w-4 transition-transform',
                                                    advancedOpen && 'rotate-180',
                                                )}
                                            />
                                        </CollapsibleTrigger>
                                        <CollapsibleContent className="grid gap-4 pt-4 sm:grid-cols-2">
                                            <Field>
                                                <Label htmlFor="tahun_produksi">Tahun produksi</Label>
                                                <Input
                                                    id="tahun_produksi"
                                                    value={data.tahun_produksi}
                                                    onChange={(e) => setData('tahun_produksi', e.target.value)}
                                                    className="h-10"
                                                />
                                            </Field>
                                            <Field>
                                                <Label htmlFor="tahun_mulai_operasi">Tahun mulai operasi</Label>
                                                <Input
                                                    id="tahun_mulai_operasi"
                                                    value={data.tahun_mulai_operasi}
                                                    onChange={(e) => setData('tahun_mulai_operasi', e.target.value)}
                                                    className="h-10"
                                                />
                                            </Field>
                                            <Field>
                                                <Label htmlFor="umur_ekonomis_bulan">Umur ekonomis (bulan)</Label>
                                                <Input
                                                    id="umur_ekonomis_bulan"
                                                    value={data.umur_ekonomis_bulan}
                                                    onChange={(e) => setData('umur_ekonomis_bulan', e.target.value)}
                                                    placeholder="Kosong = default pengaturan"
                                                    className="h-10"
                                                />
                                                <p className="text-[11px] text-muted-foreground">
                                                    Default global di{' '}
                                                    <Link href="/aset/pengaturan-penyusutan" className="underline">
                                                        Pengaturan Penyusutan
                                                    </Link>
                                                </p>
                                            </Field>
                                            <Field>
                                                <Label htmlFor="nilai_residu">Nilai residu (Rp)</Label>
                                                <Input
                                                    id="nilai_residu"
                                                    value={data.nilai_residu}
                                                    onChange={(e) => setData('nilai_residu', e.target.value)}
                                                    placeholder="Kosong = % default × harga"
                                                    className="h-10"
                                                />
                                            </Field>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                                <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                    <h2 className="text-base font-semibold tracking-tight">3. Jumlah unit</h2>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        Satu barang master, beberapa unit fisik.
                                    </p>
                                </div>
                                <div className="grid gap-4 p-5 sm:grid-cols-2">
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
                                        <p className="text-[11px] text-muted-foreground">Maks. 50</p>
                                    </Field>
                                    <Field>
                                        <Label htmlFor="tahun_registrasi">Tahun registrasi</Label>
                                        <Input
                                            id="tahun_registrasi"
                                            value={data.tahun_registrasi}
                                            onChange={(e) => setData('tahun_registrasi', e.target.value)}
                                            className="h-10"
                                        />
                                        <InputError message={errors.tahun_registrasi} />
                                    </Field>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                                <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                                    <h2 className="text-base font-semibold tracking-tight">
                                        4. Penempatan · {jumlah} unit
                                    </h2>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        Ruang wajib per unit. Nomor seri opsional. Tiap unit dapat kode & QR sendiri.
                                    </p>
                                </div>
                                <div className="space-y-4 p-5">
                                    {unitSlots.map((slot, i) => (
                                        <div
                                            key={i}
                                            className="space-y-3 rounded-lg border border-border/70 bg-muted/20 p-4"
                                        >
                                            <p className="text-sm font-medium">Unit {i + 1}</p>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor={`aset_ruang_id_${i}`}>
                                                        Ruang / lokasi <span className="text-destructive">*</span>
                                                    </Label>
                                                    <SearchSelect
                                                        inputId={`aset_ruang_id_${i}`}
                                                        options={ruangOptions}
                                                        value={slot.ruangId}
                                                        onChange={(v) => setUnitRuang(i, v)}
                                                        placeholder="Ketik nama ruang..."
                                                        isClearable={false}
                                                        hasError={Boolean(
                                                            errors[`aset_ruang_id_list.${i}`] ||
                                                                (i === 0 && errors.aset_ruang_id) ||
                                                                errors.aset_ruang_id_list,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[`aset_ruang_id_list.${i}`] ||
                                                            (i === 0 ? errors.aset_ruang_id : undefined) ||
                                                            (i === 0 ? errors.aset_ruang_id_list : undefined)
                                                        }
                                                    />
                                                </div>
                                                <Field>
                                                    <Label htmlFor={`no_seri_${i}`}>Nomor seri (opsional)</Label>
                                                    <Input
                                                        id={`no_seri_${i}`}
                                                        value={slot.noSeri}
                                                        placeholder="SN pabrik"
                                                        onChange={(e) => setUnitSeri(i, e.target.value)}
                                                        className="h-10"
                                                    />
                                                    <InputError message={errors[`no_seri_list.${i}`]} />
                                                </Field>
                                            </div>
                                        </div>
                                    ))}
                                    <InputError message={errors.no_seri_list} />
                                </div>
                            </section>
                        </>
                    )}

                    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-border/80 bg-background/90 backdrop-blur-md">
                        <div className="mx-auto flex max-w-2xl items-center justify-between gap-3 px-4 py-3">
                            <div className="min-w-0 truncate text-xs text-muted-foreground sm:text-sm">
                                {hasKatalog ? (
                                    <>
                                        <span className="font-medium text-foreground">{summaryLabel}</span>
                                        {filledRuangCount > 0
                                            ? ` · ${uniqueRuangNames.slice(0, 2).join(', ')}${uniqueRuangNames.length > 2 ? '…' : ''}`
                                            : ' · pilih ruang per unit'}
                                        {` · ${jumlah} unit`}
                                    </>
                                ) : (
                                    'Pilih jenis aset & katalog'
                                )}
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <Button type="button" variant="ghost" size="sm" asChild>
                                    <Link href="/aset">Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={!canSubmit}
                                    className="bg-teal-700 hover:bg-teal-800"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
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
    icon: typeof HeartPulse;
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
            <div className="flex w-full items-start justify-between gap-2">
                <Icon className={cn('h-5 w-5', active ? 'text-teal-700 dark:text-teal-400' : 'text-muted-foreground')} />
                {active ? <Check className="h-4 w-4 text-teal-700 dark:text-teal-400" /> : null}
            </div>
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
