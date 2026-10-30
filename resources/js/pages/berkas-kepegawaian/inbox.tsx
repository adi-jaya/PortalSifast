import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, ExternalLink, X } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type InboxItem = {
    id: number;
    original_filename: string;
    suggested_kode: string | null;
    suggested_label: string | null;
    confidence: number | null;
    ocr_excerpt: string | null;
    ocr_failed: boolean;
    status: string;
    agent_label: string | null;
    error_message: string | null;
    preview_url: string;
    created_at: string | null;
};

type MasterOption = {
    kode: string;
    nama_berkas: string;
    kategori: string;
};

type PegawaiOption = {
    nik: string;
    nama: string;
    jbtn: string | null;
    bidang: string | null;
    berkas_kategori: string;
};

type Props = {
    items: InboxItem[];
    masterOptions: MasterOption[];
    pegawaiOptions: PegawaiOption[];
};

type Flash = { success?: string; error?: string };

type RowState = {
    nik: string;
    kode_berkas: string;
    tgl_uploud: string;
    show_all_jenis: boolean;
};

const SUGGESTION_ALIASES: Record<string, string[]> = {
    STR: ['str', 'surat tanda registrasi'],
    SIP: ['sip', 'surat izin praktik', 'izin praktik'],
    IJAZAH: ['ijazah', 'ijazah legalisir'],
    KONSIL: ['konsil', 'konsil dokter'],
    KK: ['kk', 'kartu keluarga'],
    SERTIFIKAT: ['sertifikat'],
    LAMARAN: ['lamaran', 'lamaran kerja'],
    VERIFIKASI: ['verifikasi ijazah', 'verifikasi'],
    KTP: ['ktp', 'kartu tanda penduduk'],
    NPWP: ['npwp'],
    BPJS: ['bpjs'],
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Berkas Kepegawaian', href: '/berkas-kepegawaian' },
    { title: 'Inbox Scan', href: '/berkas-kepegawaian/inbox' },
];

function todayIsoDate(): string {
    return new Date().toISOString().slice(0, 10);
}

function normalize(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ');
}

function matchSuggestedKode(
    masters: MasterOption[],
    suggestedKode: string | null,
    suggestedLabel: string | null,
): string {
    if (masters.length === 0) {
        return '';
    }

    const kode = (suggestedKode ?? '').trim().toUpperCase();
    const haystack = normalize(`${suggestedLabel ?? ''} ${kode}`);

    for (const master of masters) {
        if (master.kode.toUpperCase() === kode) {
            return master.kode;
        }
    }

    const aliases = SUGGESTION_ALIASES[kode] ?? (haystack ? [haystack] : []);
    let bestKode = '';
    let bestScore = 0;

    for (const master of masters) {
        const nama = normalize(master.nama_berkas);
        let score = 0;

        for (const alias of aliases) {
            const aliasNorm = normalize(alias);
            if (!aliasNorm) {
                continue;
            }
            if (nama === aliasNorm) {
                score = Math.max(score, 100);
            } else if (nama.includes(aliasNorm)) {
                score = Math.max(score, 50 + aliasNorm.length);
            }
        }

        if (haystack && nama.includes(haystack)) {
            score = Math.max(score, 40);
        }

        if (score > bestScore) {
            bestScore = score;
            bestKode = master.kode;
        }
    }

    return bestScore > 0 ? bestKode : '';
}

function initialRowState(item: InboxItem): RowState {
    return {
        nik: '',
        kode_berkas: '',
        tgl_uploud: todayIsoDate(),
        show_all_jenis: false,
    };
}

export default function BerkasScanInboxPage({
    items,
    masterOptions,
    pegawaiOptions,
}: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const [rows, setRows] = useState<Record<number, RowState>>(() =>
        Object.fromEntries(items.map((item) => [item.id, initialRowState(item)])),
    );

    const pegawaiByNik = useMemo(
        () => Object.fromEntries(pegawaiOptions.map((p) => [p.nik, p])),
        [pegawaiOptions],
    );

    const pegawaiSelectOptions = useMemo<SearchSelectOption[]>(
        () =>
            pegawaiOptions.map((p) => ({
                value: p.nik,
                label: `${p.nama} (${p.nik})`,
                description: `${p.jbtn ?? '–'} · ${p.berkas_kategori}`,
            })),
        [pegawaiOptions],
    );

    const updateRow = (id: number, patch: Partial<RowState>) => {
        setRows((prev) => ({
            ...prev,
            [id]: { ...prev[id], ...patch },
        }));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inbox Scan Berkas" />

            <div className="flex flex-col gap-4">
                <Heading
                    title="Inbox Scan"
                    description="Konfirmasi hasil scan — jenis berkas menyesuaikan kategori pegawai"
                />

                {flash.success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                        {flash.error}
                    </div>
                )}

                {items.length === 0 ? (
                    <EmptyState
                        title="Inbox kosong"
                        description="Belum ada scan menunggu konfirmasi."
                    />
                ) : (
                    <div className="flex flex-col gap-4">
                        {items.map((item) => (
                            <InboxCard
                                key={item.id}
                                item={item}
                                pegawaiSelectOptions={pegawaiSelectOptions}
                                pegawaiByNik={pegawaiByNik}
                                masterOptions={masterOptions}
                                state={rows[item.id] ?? initialRowState(item)}
                                onChange={(patch) => updateRow(item.id, patch)}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function InboxCard({
    item,
    pegawaiSelectOptions,
    pegawaiByNik,
    masterOptions,
    state,
    onChange,
}: {
    item: InboxItem;
    pegawaiSelectOptions: SearchSelectOption[];
    pegawaiByNik: Record<string, PegawaiOption>;
    masterOptions: MasterOption[];
    state: RowState;
    onChange: (patch: Partial<RowState>) => void;
}) {
    const confirmForm = useForm({
        nik: state.nik,
        kode_berkas: state.kode_berkas,
        tgl_uploud: state.tgl_uploud,
    });

    const selectedPegawai = state.nik ? pegawaiByNik[state.nik] : undefined;
    const kategori = selectedPegawai?.berkas_kategori;

    const filteredMasters = useMemo(() => {
        if (state.show_all_jenis || !kategori) {
            return masterOptions;
        }

        return masterOptions.filter((m) => m.kategori === kategori);
    }, [masterOptions, kategori, state.show_all_jenis]);

    const masterSelectOptions = useMemo<SearchSelectOption[]>(
        () =>
            filteredMasters.map((m) => ({
                value: m.kode,
                label: `${m.nama_berkas} (${m.kode})`,
                description: m.kategori,
            })),
        [filteredMasters],
    );

    const confidencePct =
        item.confidence === null ? null : Math.round(item.confidence * 100);

    const onPegawaiChange = (nik: string) => {
        if (!nik) {
            onChange({ nik: '', kode_berkas: '' });
            return;
        }

        const pegawai = pegawaiByNik[nik];
        const masters = state.show_all_jenis
            ? masterOptions
            : masterOptions.filter((m) => m.kategori === pegawai?.berkas_kategori);

        const matched = matchSuggestedKode(
            masters,
            item.suggested_kode,
            item.suggested_label,
        );

        onChange({
            nik,
            kode_berkas: matched,
        });
    };

    const submitConfirm = (e: FormEvent) => {
        e.preventDefault();
        confirmForm
            .transform(() => ({
                nik: state.nik,
                kode_berkas: state.kode_berkas,
                tgl_uploud: state.tgl_uploud,
            }))
            .post(`/berkas-kepegawaian/inbox/${item.id}/confirm`, {
                preserveScroll: true,
            });
    };

    const reject = () => {
        if (!confirm(`Tolak scan "${item.original_filename}"?`)) {
            return;
        }

        router.post(
            `/berkas-kepegawaian/inbox/${item.id}/reject`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <form
            onSubmit={submitConfirm}
            className="rounded-xl border bg-card p-4"
        >
            <div className="flex flex-col gap-4 lg:flex-row">
                <div className="lg:w-64">
                    <a
                        href={item.preview_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        {item.original_filename}
                        <ExternalLink className="h-3.5 w-3.5" />
                    </a>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {item.status}
                        {item.agent_label ? ` · ${item.agent_label}` : ''}
                        {confidencePct !== null ? ` · ${confidencePct}%` : ''}
                    </p>
                    {item.suggested_label && (
                        <p className="mt-1 text-sm">
                            Saran OCR: {item.suggested_label}
                            {item.suggested_kode ? ` (${item.suggested_kode})` : ''}
                        </p>
                    )}
                    {kategori ? (
                        <p className="mt-1 text-xs text-muted-foreground">
                            Kategori pegawai: {kategori}
                        </p>
                    ) : null}
                    {item.ocr_failed && (
                        <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">
                            OCR gagal / kosong
                        </p>
                    )}
                    {item.error_message && (
                        <p className="mt-1 text-xs text-destructive">{item.error_message}</p>
                    )}
                    {item.ocr_excerpt && (
                        <p className="mt-2 line-clamp-4 text-xs text-muted-foreground">
                            {item.ocr_excerpt}
                        </p>
                    )}
                </div>

                <div className="grid flex-1 gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5 sm:col-span-2">
                        <Label htmlFor={`pegawai-${item.id}`}>Pegawai</Label>
                        <SearchSelect
                            inputId={`pegawai-${item.id}`}
                            options={pegawaiSelectOptions}
                            value={state.nik}
                            onChange={onPegawaiChange}
                            placeholder="Ketik NIK atau nama pegawai..."
                            isClearable
                            hasError={Boolean(confirmForm.errors.nik)}
                            noOptionsMessage="Pegawai tidak ditemukan"
                        />
                        <InputError message={confirmForm.errors.nik} />
                    </div>

                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between gap-2">
                            <Label htmlFor={`kode-${item.id}`}>Jenis berkas</Label>
                            <label className="inline-flex cursor-pointer items-center gap-1 text-[11px] text-muted-foreground">
                                <input
                                    type="checkbox"
                                    checked={state.show_all_jenis}
                                    onChange={(e) => {
                                        const showAll = e.target.checked;
                                        const pegawai = state.nik
                                            ? pegawaiByNik[state.nik]
                                            : undefined;
                                        const masters = showAll
                                            ? masterOptions
                                            : masterOptions.filter(
                                                  (m) =>
                                                      m.kategori ===
                                                      pegawai?.berkas_kategori,
                                              );
                                        const matched = matchSuggestedKode(
                                            masters,
                                            item.suggested_kode,
                                            item.suggested_label,
                                        );
                                        onChange({
                                            show_all_jenis: showAll,
                                            kode_berkas: matched || state.kode_berkas,
                                        });
                                    }}
                                    className="rounded border"
                                />
                                Semua kategori
                            </label>
                        </div>
                        <SearchSelect
                            inputId={`kode-${item.id}`}
                            options={masterSelectOptions}
                            value={state.kode_berkas}
                            onChange={(value) => onChange({ kode_berkas: value })}
                            placeholder={
                                state.nik
                                    ? 'Ketik jenis berkas...'
                                    : 'Pilih pegawai dulu...'
                            }
                            isClearable
                            disabled={!state.nik}
                            hasError={Boolean(confirmForm.errors.kode_berkas)}
                            noOptionsMessage="Jenis tidak ditemukan"
                        />
                        <InputError message={confirmForm.errors.kode_berkas} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor={`tgl-${item.id}`}>Tanggal upload</Label>
                        <Input
                            id={`tgl-${item.id}`}
                            type="date"
                            value={state.tgl_uploud}
                            onChange={(e) => onChange({ tgl_uploud: e.target.value })}
                            required
                        />
                        <InputError message={confirmForm.errors.tgl_uploud} />
                    </div>
                </div>

                <div className="flex shrink-0 flex-row gap-2 lg:flex-col">
                    <Button type="submit" disabled={confirmForm.processing}>
                        <Check className="mr-1 h-4 w-4" />
                        Confirm
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={reject}
                        disabled={confirmForm.processing}
                    >
                        <X className="mr-1 h-4 w-4" />
                        Reject
                    </Button>
                </div>
            </div>
        </form>
    );
}
