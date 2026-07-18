import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
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

type Ruang = {
    id: number;
    kode_ruang: string;
    nama_ruang: string;
    aset_count?: number;
};

type Props = {
    ruang: Ruang[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Audit Fisik', href: '/aset/audit' },
    { title: 'Mulai', href: '/aset/audit/create' },
];

export default function AuditAsetCreate({ ruang }: Props) {
    const form = useForm({
        aset_ruang_id: '',
        judul: '',
        catatan: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            aset_ruang_id: Number(data.aset_ruang_id),
            judul: data.judul || null,
            catatan: data.catatan || null,
        }));
        form.post('/aset/audit');
    };

    const selected = ruang.find((r) => String(r.id) === form.data.aset_ruang_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mulai Audit Fisik" />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Mulai audit ruang</h1>
                    <p className="text-sm text-muted-foreground">
                        Checklist diisi dari aset portal di ruang terpilih (aktif, draf, hilang).
                    </p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-4 rounded-lg border p-4">
                    <div className="space-y-2">
                        <Label>Ruang</Label>
                        <Select
                            value={form.data.aset_ruang_id || undefined}
                            onValueChange={(v) => form.setData('aset_ruang_id', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih ruang" />
                            </SelectTrigger>
                            <SelectContent>
                                {ruang.map((r) => (
                                    <SelectItem key={r.id} value={String(r.id)}>
                                        {r.nama_ruang} ({r.aset_count ?? 0} aset)
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.aset_ruang_id} />
                        {selected && (
                            <p className="text-xs text-muted-foreground">
                                Kode ruang: {selected.kode_ruang}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="judul">Judul (opsional)</Label>
                        <Input
                            id="judul"
                            value={form.data.judul}
                            onChange={(e) => form.setData('judul', e.target.value)}
                            placeholder="Audit IGD Juli 2026"
                        />
                        <InputError message={form.errors.judul} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="catatan">Catatan</Label>
                        <Input
                            id="catatan"
                            value={form.data.catatan}
                            onChange={(e) => form.setData('catatan', e.target.value)}
                        />
                        <InputError message={form.errors.catatan} />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing || !form.data.aset_ruang_id}>
                            Buat sesi audit
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/aset/audit">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
