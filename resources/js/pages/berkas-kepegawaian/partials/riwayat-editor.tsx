import { router, useForm } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type RiwayatRow = Record<string, string | number | null> & {
    public_url?: string | null;
};

export type Riwayat = {
    pendidikan: RiwayatRow[];
    jabatan: RiwayatRow[];
    penghargaan: RiwayatRow[];
    seminar: RiwayatRow[];
    surat_peringatan: RiwayatRow[];
    penelitian: RiwayatRow[];
};

type RiwayatKey = keyof Riwayat;

type FieldType = 'text' | 'date' | 'number' | 'year' | 'select';

type FieldDef = {
    key: string;
    label: string;
    type: FieldType;
    maxLength?: number;
    min?: number;
    max?: number;
    options?: SearchSelectOption[];
    optional?: boolean;
};

type ColumnDef = {
    key: string;
    label: string;
    format?: 'date' | 'year';
};

type OriginalField = {
    key: string;
    from: string;
    format?: 'date' | 'year' | 'text';
};

type SectionConfig = {
    key: RiwayatKey;
    title: string;
    routeSlug: string;
    columns: ColumnDef[];
    fields: FieldDef[];
    identityKeys: string[];
    originals: OriginalField[];
    destroyConfirm: (row: RiwayatRow) => string;
};

const PENDIDIKAN_OPTIONS = optionsFrom([
    'SD',
    'SMP',
    'SMA',
    'SMK',
    'D I',
    'D II',
    'D III',
    'D IV',
    'S1',
    'S2',
    'S3',
    'Post Doctor',
]);

const PENDANAAN_OPTIONS = optionsFrom([
    'Biaya Sendiri',
    'Biaya Instansi Sendiri',
    'Lembaga Swasta Kerjasama',
    'Lembaga Swasta Kompetisi',
    'Lembaga Pemerintah Kerjasama',
    'Lembaga Pemerintah Kompetisi',
    'Lembaga Internasional',
]);

const TINGKAT_OPTIONS = optionsFrom(['Local', 'Regional', 'Nasional', 'Internasional']);

const JENIS_SEMINAR_OPTIONS = optionsFrom([
    'WORKSHOP',
    'SIMPOSIUM',
    'SEMINAR',
    'FGD',
    'PELATIHAN',
    'LAINNYA',
]);

const RIWAYAT_SECTIONS: SectionConfig[] = [
    {
        key: 'pendidikan',
        title: 'Pendidikan',
        routeSlug: 'pendidikan',
        columns: [
            { key: 'pendidikan', label: 'Tingkat' },
            { key: 'sekolah', label: 'Sekolah/Kampus' },
            { key: 'jurusan', label: 'Jurusan' },
            { key: 'thn_lulus', label: 'Lulus', format: 'year' },
            { key: 'status', label: 'Status' },
        ],
        fields: [
            { key: 'pendidikan', label: 'Tingkat', type: 'select', options: PENDIDIKAN_OPTIONS },
            { key: 'sekolah', label: 'Sekolah/Kampus', type: 'text', maxLength: 50 },
            { key: 'jurusan', label: 'Jurusan', type: 'text', maxLength: 40 },
            { key: 'thn_lulus', label: 'Tahun lulus', type: 'year' },
            { key: 'kepala', label: 'Kepala sekolah', type: 'text', maxLength: 50 },
            { key: 'pendanaan', label: 'Pendanaan', type: 'select', options: PENDANAAN_OPTIONS },
            { key: 'keterangan', label: 'Keterangan', type: 'text', maxLength: 50 },
            { key: 'status', label: 'Status', type: 'text', maxLength: 40 },
        ],
        identityKeys: ['pendidikan', 'sekolah'],
        originals: [
            { key: 'original_pendidikan', from: 'pendidikan' },
            { key: 'original_sekolah', from: 'sekolah' },
        ],
        destroyConfirm: (row) =>
            `Hapus pendidikan ${String(row.pendidikan ?? '')} — ${String(row.sekolah ?? '')}?`,
    },
    {
        key: 'jabatan',
        title: 'Jabatan / Pangkat',
        routeSlug: 'jabatan',
        columns: [
            { key: 'jabatan', label: 'Jabatan' },
            { key: 'nomor_sk', label: 'No. SK' },
            { key: 'tgl_sk', label: 'Tgl SK', format: 'date' },
            { key: 'tmt_pangkat', label: 'TMT', format: 'date' },
            { key: 'pejabat_penetap', label: 'Pejabat' },
        ],
        fields: [
            { key: 'jabatan', label: 'Jabatan', type: 'text', maxLength: 50 },
            { key: 'tmt_pangkat', label: 'TMT pangkat', type: 'date' },
            { key: 'tmt_pangkat_yad', label: 'TMT pangkat YAD', type: 'date' },
            { key: 'pejabat_penetap', label: 'Pejabat penetap', type: 'text', maxLength: 50 },
            { key: 'nomor_sk', label: 'Nomor SK', type: 'text', maxLength: 25 },
            { key: 'tgl_sk', label: 'Tanggal SK', type: 'date' },
            { key: 'dasar_peraturan', label: 'Dasar peraturan', type: 'text', maxLength: 50 },
            { key: 'masa_kerja', label: 'Masa kerja (tahun)', type: 'number', min: 0 },
            { key: 'bln_kerja', label: 'Bulan kerja', type: 'number', min: 0, max: 11 },
        ],
        identityKeys: ['jabatan'],
        originals: [{ key: 'original_jabatan', from: 'jabatan' }],
        destroyConfirm: (row) => `Hapus jabatan "${String(row.jabatan ?? '')}"?`,
    },
    {
        key: 'penghargaan',
        title: 'Penghargaan',
        routeSlug: 'penghargaan',
        columns: [
            { key: 'jenis', label: 'Jenis' },
            { key: 'nama_penghargaan', label: 'Nama' },
            { key: 'tanggal', label: 'Tanggal', format: 'date' },
            { key: 'instansi', label: 'Instansi' },
        ],
        fields: [
            { key: 'jenis', label: 'Jenis', type: 'text', maxLength: 30 },
            { key: 'nama_penghargaan', label: 'Nama', type: 'text', maxLength: 60 },
            { key: 'tanggal', label: 'Tanggal', type: 'date' },
            { key: 'instansi', label: 'Instansi', type: 'text', maxLength: 40 },
            { key: 'pejabat_pemberi', label: 'Pejabat pemberi', type: 'text', maxLength: 40 },
        ],
        identityKeys: ['nama_penghargaan', 'tanggal'],
        originals: [
            { key: 'original_nama_penghargaan', from: 'nama_penghargaan' },
            { key: 'original_tanggal', from: 'tanggal', format: 'date' },
        ],
        destroyConfirm: (row) =>
            `Hapus penghargaan "${String(row.nama_penghargaan ?? '')}" (${toDateInput(row.tanggal)})?`,
    },
    {
        key: 'seminar',
        title: 'Seminar',
        routeSlug: 'seminar',
        columns: [
            { key: 'nama_seminar', label: 'Nama' },
            { key: 'tingkat', label: 'Tingkat' },
            { key: 'peranan', label: 'Peranan' },
            { key: 'mulai', label: 'Mulai', format: 'date' },
            { key: 'tempat', label: 'Tempat' },
        ],
        fields: [
            { key: 'tingkat', label: 'Tingkat', type: 'select', options: TINGKAT_OPTIONS },
            { key: 'jenis', label: 'Jenis', type: 'select', options: JENIS_SEMINAR_OPTIONS },
            { key: 'nama_seminar', label: 'Nama seminar', type: 'text', maxLength: 50 },
            { key: 'peranan', label: 'Peranan', type: 'text', maxLength: 40 },
            { key: 'mulai', label: 'Mulai', type: 'date' },
            { key: 'selesai', label: 'Selesai', type: 'date' },
            { key: 'penyelengara', label: 'Penyelenggara', type: 'text', maxLength: 50 },
            { key: 'tempat', label: 'Tempat', type: 'text', maxLength: 50 },
        ],
        identityKeys: ['nama_seminar', 'mulai'],
        originals: [
            { key: 'original_nama_seminar', from: 'nama_seminar' },
            { key: 'original_mulai', from: 'mulai', format: 'date' },
        ],
        destroyConfirm: (row) =>
            `Hapus seminar "${String(row.nama_seminar ?? '')}" (${toDateInput(row.mulai)})?`,
    },
    {
        key: 'surat_peringatan',
        title: 'Surat Peringatan',
        routeSlug: 'surat-peringatan',
        columns: [
            { key: 'jenis', label: 'Jenis' },
            { key: 'nama_peringatan', label: 'Nama' },
            { key: 'tanggal', label: 'Tanggal', format: 'date' },
        ],
        fields: [
            { key: 'jenis', label: 'Jenis', type: 'text', maxLength: 30 },
            { key: 'nama_peringatan', label: 'Nama', type: 'text', maxLength: 60 },
            { key: 'tanggal', label: 'Tanggal', type: 'date' },
        ],
        identityKeys: ['nama_peringatan', 'tanggal'],
        originals: [
            { key: 'original_nama_peringatan', from: 'nama_peringatan' },
            { key: 'original_tanggal', from: 'tanggal', format: 'date' },
        ],
        destroyConfirm: (row) =>
            `Hapus surat peringatan "${String(row.nama_peringatan ?? '')}" (${toDateInput(row.tanggal)})?`,
    },
    {
        key: 'penelitian',
        title: 'Penelitian',
        routeSlug: 'penelitian',
        columns: [
            { key: 'judul_penelitian', label: 'Judul' },
            { key: 'jenis_penelitian', label: 'Jenis' },
            { key: 'peranan', label: 'Peranan' },
            { key: 'tahun', label: 'Tahun', format: 'year' },
        ],
        fields: [
            { key: 'jenis_penelitian', label: 'Jenis penelitian', type: 'text', maxLength: 30 },
            { key: 'peranan', label: 'Peranan', type: 'text', maxLength: 30 },
            { key: 'judul_penelitian', label: 'Judul penelitian', type: 'text', maxLength: 60 },
            { key: 'judul_jurnal', label: 'Judul jurnal', type: 'text', maxLength: 60 },
            { key: 'tahun', label: 'Tahun', type: 'year' },
            { key: 'biaya_penelitian', label: 'Biaya penelitian', type: 'number', min: 0, optional: true },
            { key: 'asal_dana', label: 'Asal dana', type: 'text', maxLength: 30 },
        ],
        identityKeys: ['judul_penelitian', 'tahun'],
        originals: [
            { key: 'original_judul_penelitian', from: 'judul_penelitian' },
            { key: 'original_tahun', from: 'tahun', format: 'year' },
        ],
        destroyConfirm: (row) =>
            `Hapus penelitian "${String(row.judul_penelitian ?? '')}" (${String(row.tahun ?? '')})?`,
    },
];

type RiwayatEditorProps = {
    nik: string;
    riwayat: Riwayat;
    activeKey?: RiwayatKey;
    onChangeKey?: (key: RiwayatKey) => void;
};

function optionsFrom(values: string[]): SearchSelectOption[] {
    return values.map((value) => ({ value, label: value }));
}

function todayIso(): string {
    return new Date().toISOString().slice(0, 10);
}

function currentYear(): string {
    return String(new Date().getFullYear());
}

function toDateInput(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value).slice(0, 10);
}

function dash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

function formatCellValue(
    row: RiwayatRow,
    col: ColumnDef,
): string {
    const raw = row[col.key];

    if (col.format === 'date' || col.key === 'tanggal' || col.key === 'mulai' || col.key === 'tgl_sk' || col.key === 'tmt_pangkat') {
        return dash(toDateInput(raw));
    }

    return dash(raw);
}

function fieldValueFromRow(
    row: RiwayatRow,
    field: FieldDef,
): string {
    const raw = row[field.key];

    if (field.type === 'date') {
        return toDateInput(raw);
    }

    if (field.type === 'year' || field.type === 'number') {
        return raw === null || raw === undefined || raw === '' ? '' : String(raw);
    }

    return String(raw ?? '');
}

function originalValueFromRow(row: RiwayatRow, original: OriginalField): string {
    const raw = row[original.from];

    if (original.format === 'date') {
        return toDateInput(raw);
    }

    if (original.format === 'year') {
        return raw === null || raw === undefined || raw === '' ? '' : String(raw);
    }

    return String(raw ?? '');
}

function buildEmptyFormData(section: SectionConfig): Record<string, string> {
    const data: Record<string, string> = {};

    for (const field of section.fields) {
        if (field.type === 'date') {
            data[field.key] = todayIso();
        } else if (field.type === 'year') {
            data[field.key] = currentYear();
        } else if (field.type === 'number') {
            data[field.key] = '0';
        } else if (field.type === 'select' && field.options?.[0]) {
            data[field.key] = field.options[0].value;
        } else {
            data[field.key] = '';
        }
    }

    for (const original of section.originals) {
        data[original.key] = '';
    }

    return data;
}

function rowToFormData(section: SectionConfig, row: RiwayatRow): Record<string, string> {
    const data = buildEmptyFormData(section);

    for (const field of section.fields) {
        data[field.key] = fieldValueFromRow(row, field);
    }

    for (const original of section.originals) {
        data[original.key] = originalValueFromRow(row, original);
    }

    return data;
}

function rowIdentityKey(section: SectionConfig, row: RiwayatRow): string {
    return section.identityKeys
        .map((key) => {
            const value = row[key];
            if (key === 'tanggal' || key === 'mulai') {
                return toDateInput(value);
            }

            return String(value ?? '');
        })
        .join('|');
}

function destroyPayload(section: SectionConfig, row: RiwayatRow): Record<string, string> {
    const payload: Record<string, string> = {};

    for (const key of section.identityKeys) {
        const value = row[key];
        if (key === 'tanggal' || key === 'mulai') {
            payload[key] = toDateInput(value);
        } else if (key === 'tahun') {
            payload[key] = String(value ?? '');
        } else {
            payload[key] = String(value ?? '');
        }
    }

    return payload;
}

function submitPayload(
    section: SectionConfig,
    data: Record<string, string>,
    mode: 'create' | 'edit',
): Record<string, string | number | null> {
    const payload: Record<string, string | number | null> = { ...data };

    for (const field of section.fields) {
        if (field.type === 'number' || field.type === 'year') {
            const raw = data[field.key];
            if (field.optional && raw === '') {
                delete payload[field.key];
                continue;
            }

            payload[field.key] = field.type === 'year' ? raw : Number(raw);
        }
    }

    if (mode === 'create') {
        for (const original of section.originals) {
            delete payload[original.key];
        }
    }

    return payload;
}

export function RiwayatEditor({
    nik,
    riwayat,
    activeKey: controlledKey,
    onChangeKey,
}: RiwayatEditorProps) {
    const [internalKey, setInternalKey] = useState<RiwayatKey>('pendidikan');
    const [formMode, setFormMode] = useState<'idle' | 'create' | 'edit'>('idle');
    const [editingRowKey, setEditingRowKey] = useState<string | null>(null);

    const activeKey = controlledKey ?? internalKey;
    const setActiveKey = (key: RiwayatKey) => {
        if (onChangeKey) {
            onChangeKey(key);
        } else {
            setInternalKey(key);
        }
    };

    const section =
        RIWAYAT_SECTIONS.find((item) => item.key === activeKey) ?? RIWAYAT_SECTIONS[0];
    const rows = riwayat[section.key] ?? [];
    const baseUrl = `/berkas-kepegawaian/${encodeURIComponent(nik)}/riwayat/${section.routeSlug}`;

    const form = useForm<Record<string, string>>(buildEmptyFormData(section));

    const closeForm = () => {
        setFormMode('idle');
        setEditingRowKey(null);
        form.clearErrors();
    };

    const switchSection = (key: RiwayatKey) => {
        setActiveKey(key);
        closeForm();
    };

    const openCreate = () => {
        const nextSection =
            RIWAYAT_SECTIONS.find((item) => item.key === activeKey) ?? RIWAYAT_SECTIONS[0];
        form.setData(buildEmptyFormData(nextSection));
        form.clearErrors();
        setEditingRowKey(null);
        setFormMode('create');
    };

    const openEdit = (row: RiwayatRow) => {
        const rowKey = rowIdentityKey(section, row);
        form.setData(rowToFormData(section, row));
        form.clearErrors();
        setEditingRowKey(rowKey);
        setFormMode('edit');
    };

    const submitForm = (e: FormEvent) => {
        e.preventDefault();
        const payload = submitPayload(section, form.data, formMode === 'create' ? 'create' : 'edit');

        if (formMode === 'create') {
            form.transform(() => payload).post(baseUrl, {
                preserveScroll: true,
                onSuccess: () => closeForm(),
                onFinish: () => form.transform((data) => data),
            });
            return;
        }

        form.transform(() => payload).put(baseUrl, {
            preserveScroll: true,
            onSuccess: () => closeForm(),
            onFinish: () => form.transform((data) => data),
        });
    };

    const destroyRow = (row: RiwayatRow) => {
        if (!confirm(section.destroyConfirm(row))) {
            return;
        }

        router.delete(baseUrl, {
            preserveScroll: true,
            data: destroyPayload(section, row),
        });
    };

    return (
        <div className="space-y-3 p-3">
            <div className="flex flex-wrap gap-1.5">
                {RIWAYAT_SECTIONS.map((item) => (
                    <Button
                        key={item.key}
                        type="button"
                        size="sm"
                        variant={activeKey === item.key ? 'default' : 'outline'}
                        className="h-7"
                        onClick={() => switchSection(item.key)}
                    >
                        {item.title}
                    </Button>
                ))}
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold">{section.title}</h2>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="h-7"
                    onClick={openCreate}
                >
                    Tambah
                </Button>
            </div>

            {formMode !== 'idle' ? (
                <form
                    onSubmit={submitForm}
                    className="grid gap-2 rounded-lg border bg-muted/20 p-2.5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {section.fields.map((field) => (
                        <div key={field.key} className="space-y-1">
                            <Label htmlFor={`riwayat-${section.key}-${field.key}`} className="text-xs">
                                {field.label}
                            </Label>
                            {field.type === 'select' ? (
                                <SearchSelect
                                    inputId={`riwayat-${section.key}-${field.key}`}
                                    options={field.options ?? []}
                                    value={form.data[field.key] ?? ''}
                                    onChange={(value) => form.setData(field.key, value)}
                                    isClearable={false}
                                    hasError={Boolean(form.errors[field.key])}
                                />
                            ) : (
                                <Input
                                    id={`riwayat-${section.key}-${field.key}`}
                                    type={
                                        field.type === 'date'
                                            ? 'date'
                                            : field.type === 'number' || field.type === 'year'
                                              ? 'number'
                                              : 'text'
                                    }
                                    value={form.data[field.key] ?? ''}
                                    onChange={(e) => form.setData(field.key, e.target.value)}
                                    maxLength={field.maxLength}
                                    min={field.min}
                                    max={field.max}
                                    className="h-9"
                                />
                            )}
                            <InputError message={form.errors[field.key]} />
                        </div>
                    ))}
                    <div className="flex gap-2 sm:col-span-2 lg:col-span-4">
                        <Button type="submit" size="sm" className="h-9" disabled={form.processing}>
                            {form.processing ? '...' : 'Simpan'}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            className="h-9"
                            onClick={closeForm}
                        >
                            Batal
                        </Button>
                    </div>
                </form>
            ) : null}

            {rows.length === 0 ? (
                <p className="px-1 py-4 text-sm text-muted-foreground">
                    Belum ada data {section.title.toLowerCase()}.
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b bg-muted/40">
                                {section.columns.map((col) => (
                                    <th key={col.key} className="px-3 py-2 font-medium">
                                        {col.label}
                                    </th>
                                ))}
                                <th className="px-3 py-2 font-medium">Berkas</th>
                                <th className="px-3 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => {
                                const rowKey = rowIdentityKey(section, row) || `${section.key}-${index}`;

                                return (
                                    <tr key={rowKey} className="border-b last:border-0">
                                        {section.columns.map((col) => (
                                            <td key={col.key} className="px-3 py-2">
                                                {formatCellValue(row, col)}
                                            </td>
                                        ))}
                                        <td className="px-3 py-2">
                                            {row.public_url ? (
                                                <a
                                                    href={row.public_url}
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
                                                    onClick={() => openEdit(row)}
                                                    disabled={editingRowKey === rowKey}
                                                >
                                                    Edit
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="destructive"
                                                    className="h-7"
                                                    onClick={() => destroyRow(row)}
                                                >
                                                    Hapus
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
