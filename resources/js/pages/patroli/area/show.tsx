import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { SearchSelect, type SearchSelectOption } from '@/components/search-select';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AsetOption = SearchSelectOption & { kode: string; nama: string };

type RuangRow = {
    id: number;
    kode: string | null;
    nama: string;
    is_active: boolean;
    patroli_template_id: number | null;
    template_nama: string | null;
    scan_url: string;
    label_url: string;
};

type Template = { id: number; nama: string };

type Props = {
    area: {
        id: number;
        nama: string;
        deskripsi: string | null;
        is_active: boolean;
    };
    ruang: RuangRow[];
    templates: Template[];
    asetRuangOptions: AsetOption[];
};

export default function PatroliAreaShow({ area, ruang, templates, asetRuangOptions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Area & Ruang', href: '/patroli/area' },
        { title: area.nama, href: `/patroli/area/${area.id}` },
    ];

    const areaForm = useForm({
        nama: area.nama,
        deskripsi: area.deskripsi ?? '',
        is_active: area.is_active,
    });

    const ruangForm = useForm({
        nama: '',
        kode: '',
        patroli_template_id: '' as string,
        is_active: true,
    });

    const [asetValue, setAsetValue] = useState('');

    const applyAsetHelper = (value: string) => {
        setAsetValue(value);
        const opted = asetRuangOptions.find((o) => o.value === value);
        if (! opted) {
            return;
        }
        ruangForm.setData((current) => ({
            ...current,
            nama: opted.nama,
        }));
    };

    const submitArea = (e: FormEvent) => {
        e.preventDefault();
        areaForm.put(`/patroli/area/${area.id}`, { preserveScroll: true });
    };

    const submitRuang = (e: FormEvent) => {
        e.preventDefault();
        router.post(
            `/patroli/area/${area.id}/ruang`,
            {
                nama: ruangForm.data.nama,
                kode: ruangForm.data.kode || null,
                patroli_template_id:
                    ruangForm.data.patroli_template_id === ''
                        ? null
                        : Number(ruangForm.data.patroli_template_id),
                is_active: ruangForm.data.is_active,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    ruangForm.reset();
                    setAsetValue('');
                },
                onError: (errs) => {
                    if (errs.nama) {
                        ruangForm.setError('nama', errs.nama);
                    }
                    if (errs.kode) {
                        ruangForm.setError('kode', errs.kode);
                    }
                },
            },
        );
    };

    const saveRuangRow = (row: RuangRow) => {
        router.put(
            `/patroli/area/${area.id}/ruang/${row.id}`,
            {
                nama: row.nama,
                kode: row.kode,
                is_active: row.is_active,
                patroli_template_id: row.patroli_template_id,
            },
            { preserveScroll: true },
        );
    };

    const updateRuangTemplate = (row: RuangRow, templateId: string) => {
        saveRuangRow({
            ...row,
            patroli_template_id: templateId === '__none__' ? null : Number(templateId),
        });
    };

    const toggleRuangActive = (row: RuangRow) => {
        saveRuangRow({ ...row, is_active: !row.is_active });
    };

    const removeRuang = (row: RuangRow) => {
        if (! confirm(`Hapus ruang "${row.nama}"?`)) {
            return;
        }
        router.delete(`/patroli/area/${area.id}/ruang/${row.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Area ${area.nama}`} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">{area.nama}</h1>
                        <p className="text-sm text-muted-foreground">Kelola ruang & QR di area ini</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/patroli/area">Kembali</Link>
                    </Button>
                </div>

                <form onSubmit={submitArea} className="grid max-w-xl gap-3 rounded-md border p-4">
                    <h2 className="font-medium">Detail area</h2>
                    <div className="grid gap-2">
                        <Label htmlFor="area-nama">Nama</Label>
                        <Input
                            id="area-nama"
                            value={areaForm.data.nama}
                            onChange={(e) => areaForm.setData('nama', e.target.value)}
                        />
                        <InputError message={areaForm.errors.nama} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="area-deskripsi">Deskripsi</Label>
                        <Textarea
                            id="area-deskripsi"
                            value={areaForm.data.deskripsi}
                            onChange={(e) => areaForm.setData('deskripsi', e.target.value)}
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={areaForm.data.is_active}
                            onChange={(e) => areaForm.setData('is_active', e.target.checked)}
                        />
                        Area aktif
                    </label>
                    <Button type="submit" disabled={areaForm.processing} className="w-fit">
                        Simpan area
                    </Button>
                </form>

                <form onSubmit={submitRuang} className="grid max-w-xl gap-3 rounded-md border p-4">
                    <h2 className="font-medium">Tambah ruang</h2>
                    <div className="grid gap-2">
                        <Label>Isi cepat dari inventaris (opsional)</Label>
                        <SearchSelect
                            options={asetRuangOptions}
                            value={asetValue}
                            onChange={applyAsetHelper}
                            placeholder="Cari aset_ruang untuk salin nama..."
                            isClearable
                        />
                        <p className="text-xs text-muted-foreground">
                            Hanya menyalin nama — tidak menyimpan relasi ke inventaris.
                        </p>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="ruang-nama">Nama ruang</Label>
                        <Input
                            id="ruang-nama"
                            value={ruangForm.data.nama}
                            onChange={(e) => ruangForm.setData('nama', e.target.value)}
                        />
                        <InputError message={ruangForm.errors.nama} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="ruang-kode">Kode (opsional, unik)</Label>
                        <Input
                            id="ruang-kode"
                            value={ruangForm.data.kode}
                            onChange={(e) => ruangForm.setData('kode', e.target.value)}
                        />
                        <InputError message={ruangForm.errors.kode} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Template</Label>
                        <Select
                            value={ruangForm.data.patroli_template_id || '__none__'}
                            onValueChange={(value) =>
                                ruangForm.setData('patroli_template_id', value === '__none__' ? '' : value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih template" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__none__">— Belum assign —</SelectItem>
                                {templates.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" disabled={ruangForm.processing} className="w-fit">
                        Tambah ruang
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Kode</th>
                                <th className="p-3">Nama</th>
                                <th className="p-3">Template</th>
                                <th className="p-3">Status</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {ruang.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-muted-foreground">
                                        Belum ada ruang di area ini.
                                    </td>
                                </tr>
                            ) : (
                                ruang.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="p-3">
                                            <Input
                                                className="h-8 w-[110px]"
                                                defaultValue={row.kode ?? ''}
                                                onBlur={(e) => {
                                                    const next = e.target.value || null;
                                                    if (next !== row.kode) {
                                                        saveRuangRow({ ...row, kode: next });
                                                    }
                                                }}
                                            />
                                        </td>
                                        <td className="p-3">
                                            <Input
                                                className="h-8 min-w-[140px]"
                                                defaultValue={row.nama}
                                                onBlur={(e) => {
                                                    const next = e.target.value.trim();
                                                    if (next && next !== row.nama) {
                                                        saveRuangRow({ ...row, nama: next });
                                                    }
                                                }}
                                            />
                                        </td>
                                        <td className="p-3">
                                            <Select
                                                value={
                                                    row.patroli_template_id
                                                        ? String(row.patroli_template_id)
                                                        : '__none__'
                                                }
                                                onValueChange={(value) => updateRuangTemplate(row, value)}
                                            >
                                                <SelectTrigger className="w-[200px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="__none__">— Tanpa template —</SelectItem>
                                                    {templates.map((t) => (
                                                        <SelectItem key={t.id} value={String(t.id)}>
                                                            {t.nama}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </td>
                                        <td className="p-3">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => toggleRuangActive(row)}
                                            >
                                                {row.is_active ? 'Aktif' : 'Nonaktif'}
                                            </Button>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex flex-wrap gap-2">
                                                <Button asChild variant="outline" size="sm">
                                                    <a href={row.label_url} target="_blank" rel="noreferrer">
                                                        Cetak QR
                                                    </a>
                                                </Button>
                                                <Button asChild variant="secondary" size="sm">
                                                    <a href={row.scan_url}>Buka scan</a>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeRuang(row)}
                                                >
                                                    Hapus
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
        </AppLayout>
    );
}
