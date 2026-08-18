import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { MasterCsvActions } from '@/components/aset/master-csv-actions';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    id_alat: string;
    nama_alat: string;
    kode: string | null;
    level: number;
    sinonim: string | null;
    parent_id: number | null;
    parent_nama: string | null;
    is_leaf: boolean;
    children_count: number;
    barang_count: number;
    aset_kategori_id: number | null;
    kategori_efektif_id: number | null;
    kategori_efektif_nama: string | null;
};

type KategoriOpt = { id: number; nama: string };
type ParentOpt = { id: number; kode: string | null; nama: string; level: number };

type Paginated = {
    data: Row[];
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    items: Paginated;
    filters: { q?: string; only_leaf?: boolean; level?: string };
    stats: { total: number; leaf: number };
    kategoriOptions: KategoriOpt[];
    parentOptions: ParentOpt[];
};

type CatalogFormFields = {
    nama_alat: string;
    parent_id: string;
    sinonim: string;
    aset_kategori_id: string;
};

const emptyForm: CatalogFormFields = {
    nama_alat: '',
    parent_id: '__none__',
    sinonim: '',
    aset_kategori_id: '__none__',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Katalog Non-Alkes', href: '/aset/master/non-alkes' },
];

function buildKategoriSelectOptions(options: KategoriOpt[]): SearchSelectOption[] {
    return [
        { value: '__none__', label: 'Belum diisi' },
        ...options.map((k) => ({ value: String(k.id), label: k.nama })),
    ];
}

function buildParentSelectOptions(options: ParentOpt[]): SearchSelectOption[] {
    return [
        { value: '__none__', label: 'Tanpa induk (root)' },
        ...options.map((item) => ({
            value: String(item.id),
            label: item.nama,
            description: item.kode ? `${item.kode} · level ${item.level}` : `level ${item.level}`,
        })),
    ];
}

export default function MasterNonAlkesIndex({ items, filters, stats, kategoriOptions, parentOptions }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string; error?: string };
    const [search, setSearch] = useState(filters.q ?? '');
    const [onlyLeaf, setOnlyLeaf] = useState(Boolean(filters.only_leaf));
    const [level, setLevel] = useState(filters.level || '__all__');
    const [inlineNamaId, setInlineNamaId] = useState<number | null>(null);
    const [editingNama, setEditingNama] = useState('');
    const [showCreate, setShowCreate] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm<CatalogFormFields>(emptyForm);
    const editForm = useForm<CatalogFormFields>(emptyForm);

    const editParentOptions = useMemo(
        () => parentOptions.filter((item) => item.id !== editingId),
        [parentOptions, editingId],
    );

    const levelOptions = useMemo<SearchSelectOption[]>(
        () => [
            { value: '__all__', label: 'Semua' },
            { value: '1', label: 'Level 1' },
            { value: '2', label: 'Level 2' },
            { value: '3', label: 'Level 3' },
        ],
        [],
    );

    const kategoriSelectOptions = useMemo(
        () => buildKategoriSelectOptions(kategoriOptions),
        [kategoriOptions],
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/aset/master/non-alkes',
            {
                q: search || undefined,
                only_leaf: onlyLeaf ? 1 : undefined,
                level: level === '__all__' ? undefined : level,
            },
            { preserveState: true },
        );
    };

    const updateKategori = (rowId: number, value: string) => {
        router.patch(
            `/aset/master/non-alkes/${rowId}/kategori`,
            { aset_kategori_id: value === '__none__' ? null : Number(value) },
            { preserveScroll: true, preserveState: true },
        );
    };

    const startEditNama = (row: Row) => {
        setEditingId(null);
        setShowCreate(false);
        setInlineNamaId(row.id);
        setEditingNama(row.nama_alat);
    };

    const cancelEditNama = () => {
        setInlineNamaId(null);
        setEditingNama('');
    };

    const saveNama = (rowId: number) => {
        const nama = editingNama.trim();
        if (nama.length < 2) {
            return;
        }
        router.patch(
            `/aset/master/non-alkes/${rowId}/nama`,
            { nama_alat: nama },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => cancelEditNama(),
            },
        );
    };

    const openCreate = () => {
        setInlineNamaId(null);
        setEditingId(null);
        setShowCreate(true);
        createForm.setData(emptyForm);
        createForm.clearErrors();
    };

    const openEdit = (row: Row) => {
        setInlineNamaId(null);
        setShowCreate(false);
        setEditingId(row.id);
        editForm.setData({
            nama_alat: row.nama_alat,
            parent_id: row.parent_id ? String(row.parent_id) : '__none__',
            sinonim: row.sinonim ?? '',
            aset_kategori_id: row.aset_kategori_id ? String(row.aset_kategori_id) : '__none__',
        });
        editForm.clearErrors();
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/aset/master/non-alkes', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.setData(emptyForm);
                setShowCreate(false);
            },
        });
    };

    const submitEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingId) {
            return;
        }
        editForm.patch(`/aset/master/non-alkes/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const destroyItem = (row: Row) => {
        if (row.children_count > 0) {
            alert(
                `Katalog "${row.nama_alat}" masih punya ${row.children_count} anak. Hapus atau pindahkan anaknya dulu.`,
            );
            return;
        }
        if (row.barang_count > 0) {
            alert(`Katalog "${row.nama_alat}" masih dipakai ${row.barang_count} barang. Tidak bisa dihapus.`);
            return;
        }
        if (!confirm(`Hapus katalog "${row.nama_alat}"?`)) {
            return;
        }
        router.delete(`/aset/master/non-alkes/${row.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Katalog Non-Alkes" />

            <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">Katalog Non-Alkes</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Set kategori di folder (induk); leaf mewarisi saat tambah aset. Kode leaf ={' '}
                            <code className="text-xs">kode_non_alkes</code> di import unit.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{stats.total} total</Badge>
                        <Badge variant="outline">{stats.leaf} leaf</Badge>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Tambah Katalog
                        </Button>
                    </div>
                </div>

                {(flash.success || flash.error) && (
                    <div
                        className={`rounded-lg border px-3 py-2 text-sm ${
                            flash.error
                                ? 'border-destructive/30 bg-destructive/10 text-destructive'
                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200'
                        }`}
                    >
                        {flash.error ?? flash.success}
                    </div>
                )}

                <MasterCsvActions tipe="non_alkes" />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-end"
                >
                    <div className="relative flex-1 space-y-1.5">
                        <Label htmlFor="q">Cari</Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="q"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Nama, kode, sinonim..."
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="w-full space-y-1.5 sm:w-36">
                        <Label>Level</Label>
                        <SearchSelect
                            options={levelOptions}
                            value={level}
                            onChange={setLevel}
                            placeholder="Semua"
                            isClearable={false}
                        />
                    </div>
                    <div className="flex items-center gap-2 pb-2 sm:pb-2.5">
                        <Checkbox
                            id="only_leaf"
                            checked={onlyLeaf}
                            onCheckedChange={(c) => setOnlyLeaf(Boolean(c))}
                        />
                        <Label htmlFor="only_leaf" className="cursor-pointer font-normal">
                            Hanya leaf
                        </Label>
                    </div>
                    <Button type="submit">Terapkan</Button>
                </form>

                {showCreate && (
                    <CatalogForm
                        title="Tambah katalog"
                        form={createForm}
                        parentOptions={parentOptions}
                        kategoriOptions={kategoriOptions}
                        processing={createForm.processing}
                        submitLabel="Simpan"
                        onSubmit={submitCreate}
                        onCancel={() => setShowCreate(false)}
                    />
                )}

                {editingId !== null && (
                    <CatalogForm
                        title="Edit katalog"
                        form={editForm}
                        parentOptions={editParentOptions}
                        kategoriOptions={kategoriOptions}
                        processing={editForm.processing}
                        submitLabel="Perbarui"
                        onSubmit={submitEdit}
                        onCancel={() => setEditingId(null)}
                        excludeId={editingId}
                        currentKode={items.data.find((row) => row.id === editingId)?.kode ?? null}
                        initialParentId={
                            items.data.find((row) => row.id === editingId)?.parent_id
                                ? String(items.data.find((row) => row.id === editingId)?.parent_id)
                                : '__none__'
                        }
                    />
                )}

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40">
                                    <th className="px-4 py-3 font-medium">Kode</th>
                                    <th className="px-4 py-3 font-medium">Nama</th>
                                    <th className="px-4 py-3 font-medium">Induk</th>
                                    <th className="px-4 py-3 font-medium">Level</th>
                                    <th className="px-4 py-3 font-medium">Kategori</th>
                                    <th className="px-4 py-3 font-medium">Tipe</th>
                                    <th className="px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                                            Belum ada data. Tambah katalog atau import CSV.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="px-4 py-3 font-mono text-xs">{row.kode ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                {inlineNamaId === row.id ? (
                                                    <div className="flex max-w-sm flex-col gap-2">
                                                        <Input
                                                            value={editingNama}
                                                            onChange={(e) => setEditingNama(e.target.value)}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    e.preventDefault();
                                                                    saveNama(row.id);
                                                                }
                                                                if (e.key === 'Escape') {
                                                                    cancelEditNama();
                                                                }
                                                            }}
                                                            className="h-8"
                                                            autoFocus
                                                        />
                                                        <div className="flex gap-2">
                                                            <Button type="button" size="sm" onClick={() => saveNama(row.id)}>
                                                                Simpan
                                                            </Button>
                                                            <Button type="button" size="sm" variant="ghost" onClick={cancelEditNama}>
                                                                Batal
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        className="group text-left"
                                                        onClick={() => startEditNama(row)}
                                                        title="Klik untuk edit nama"
                                                    >
                                                        <div className="font-medium group-hover:underline">{row.nama_alat}</div>
                                                        {row.sinonim ? (
                                                            <div className="text-xs text-muted-foreground">{row.sinonim}</div>
                                                        ) : null}
                                                    </button>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{row.parent_nama ?? '—'}</td>
                                            <td className="px-4 py-3">{row.level}</td>
                                            <td className="px-4 py-3">
                                                {row.is_leaf ? (
                                                    <span className="text-muted-foreground">
                                                        {row.kategori_efektif_nama ?? '—'}
                                                        {row.kategori_efektif_nama && !row.aset_kategori_id ? (
                                                            <span className="ml-1 text-[11px]">(warisan)</span>
                                                        ) : null}
                                                    </span>
                                                ) : (
                                                    <SearchSelect
                                                        options={kategoriSelectOptions}
                                                        value={
                                                            row.aset_kategori_id
                                                                ? String(row.aset_kategori_id)
                                                                : '__none__'
                                                        }
                                                        onChange={(v) => updateKategori(row.id, v || '__none__')}
                                                        placeholder="Pilih kategori"
                                                        isClearable={false}
                                                        size="sm"
                                                        className="w-[180px]"
                                                    />
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.is_leaf ? (
                                                    <Badge className="bg-teal-700 text-white hover:bg-teal-700">Leaf</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Folder</Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => openEdit(row)}
                                                        aria-label={`Edit ${row.nama_alat}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => destroyItem(row)}
                                                        aria-label={`Hapus ${row.nama_alat}`}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {items.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {items.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

type CatalogFormProps = {
    title: string;
    form: {
        data: CatalogFormFields;
        setData: (key: keyof CatalogFormFields, value: string) => void;
        errors: Partial<Record<keyof CatalogFormFields, string>>;
    };
    parentOptions: ParentOpt[];
    kategoriOptions: KategoriOpt[];
    processing: boolean;
    submitLabel: string;
    onSubmit: (e: FormEvent) => void;
    onCancel: () => void;
    excludeId?: number;
    currentKode?: string | null;
    initialParentId?: string;
};

async function fetchSuggestedKode(parentId: string, excludeId?: number): Promise<string> {
    const params = new URLSearchParams();
    if (parentId !== '__none__') {
        params.set('parent_id', parentId);
    }
    if (excludeId) {
        params.set('exclude_id', String(excludeId));
    }
    const response = await fetch(`/aset/master/non-alkes/suggest-kode?${params.toString()}`);
    const data = (await response.json()) as { kode: string };

    return data.kode;
}

function CatalogForm({
    title,
    form,
    parentOptions,
    kategoriOptions,
    processing,
    submitLabel,
    onSubmit,
    onCancel,
    excludeId,
    currentKode = null,
    initialParentId = '__none__',
}: CatalogFormProps) {
    const [suggestedKode, setSuggestedKode] = useState<string | null>(null);
    const parentChanged = form.data.parent_id !== initialParentId;
    const showAutoKode = excludeId === undefined || parentChanged;

    const parentSelectOptions = useMemo(
        () => buildParentSelectOptions(parentOptions),
        [parentOptions],
    );

    const kategoriFormOptions = useMemo(
        () => buildKategoriSelectOptions(kategoriOptions),
        [kategoriOptions],
    );

    useEffect(() => {
        let cancelled = false;

        fetchSuggestedKode(form.data.parent_id, excludeId).then((kode) => {
            if (!cancelled) {
                setSuggestedKode(kode);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [form.data.parent_id, excludeId]);

    return (
        <form onSubmit={onSubmit} className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-2">
            <div className="md:col-span-2">
                <p className="text-sm font-medium">{title}</p>
                <p className="mt-1 text-xs text-muted-foreground">
                    Pilih induk dulu — kode lanjutan otomatis (mis. 10 → 10.01 → 10.01.001).
                </p>
            </div>
            <div className="space-y-1.5 md:col-span-2">
                <Label htmlFor={`${title}-nama`}>Nama</Label>
                <Input
                    id={`${title}-nama`}
                    value={form.data.nama_alat}
                    onChange={(e) => form.setData('nama_alat', e.target.value)}
                    placeholder="Mis. Laptop"
                    required
                />
                <InputError message={form.errors.nama_alat} />
            </div>
            <div className="space-y-1.5">
                <Label>Induk</Label>
                <SearchSelect
                    options={parentSelectOptions}
                    value={form.data.parent_id}
                    onChange={(v) => form.setData('parent_id', v || '__none__')}
                    placeholder="Tanpa induk (root)"
                    isClearable={false}
                />
                <InputError message={form.errors.parent_id} />
            </div>
            <div className="space-y-1.5">
                <Label>Kode</Label>
                <div className="flex h-9 items-center rounded-md border bg-muted/40 px-3 font-mono text-sm">
                    {showAutoKode ? (
                        suggestedKode ?? '…'
                    ) : (
                        currentKode ?? '—'
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    {showAutoKode ? 'Diisi otomatis saat disimpan' : 'Tetap sama kecuali induk diubah'}
                </p>
            </div>
            <div className="space-y-1.5">
                <Label>Kategori</Label>
                <SearchSelect
                    options={kategoriFormOptions}
                    value={form.data.aset_kategori_id}
                    onChange={(v) => form.setData('aset_kategori_id', v || '__none__')}
                    placeholder="Opsional"
                    isClearable={false}
                />
                <InputError message={form.errors.aset_kategori_id} />
            </div>
            <div className="space-y-1.5 md:col-span-2">
                <Label htmlFor={`${title}-sinonim`}>Sinonim</Label>
                <Input
                    id={`${title}-sinonim`}
                    value={form.data.sinonim}
                    onChange={(e) => form.setData('sinonim', e.target.value)}
                    placeholder="Opsional"
                />
                <InputError message={form.errors.sinonim} />
            </div>
            <div className="flex items-end gap-2 md:col-span-2">
                <Button type="button" variant="outline" onClick={onCancel}>
                    Batal
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Menyimpan...' : submitLabel}
                </Button>
            </div>
        </form>
    );
}
