import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, RotateCcw } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type KategoriRow = {
    kode_kategori: string;
    nama_kategori: string;
    selected: boolean;
};

type Props = {
    kategori: KategoriRow[];
    selectedCodes: string[];
    defaults: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Monitoring', href: '/monitoring' },
    { title: 'Pengaturan kategori', href: '/monitoring/pengaturan-kategori' },
];

export default function MonitoringPengaturanKategori({ kategori, selectedCodes, defaults }: Props) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const [q, setQ] = useState('');
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm<{
        kategori_codes: string[];
    }>({
        kategori_codes: selectedCodes,
    });

    const filtered = useMemo(() => {
        const needle = q.trim().toLowerCase();
        if (!needle) {
            return kategori;
        }

        return kategori.filter(
            (row) =>
                row.kode_kategori.toLowerCase().includes(needle) ||
                row.nama_kategori.toLowerCase().includes(needle),
        );
    }, [kategori, q]);

    const toggle = (code: string, checked: boolean) => {
        if (checked) {
            if (!data.kategori_codes.includes(code)) {
                setData('kategori_codes', [...data.kategori_codes, code]);
            }

            return;
        }

        setData(
            'kategori_codes',
            data.kategori_codes.filter((item) => item !== code),
        );
    };

    const selectSuggested = () => {
        setData('kategori_codes', [...defaults]);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put('/monitoring/pengaturan-kategori');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan kategori monitoring" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 md:p-6">
                <header className="border-b border-border/70 pb-5">
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                        <Link href="/monitoring">
                            <ArrowLeft className="size-4" />
                            Kembali
                        </Link>
                    </Button>
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Operasional IT
                    </p>
                    <h1 className="text-[1.65rem] font-semibold tracking-tight">Kategori yang boleh dimonitor</h1>
                    <p className="mt-1.5 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        Centang kategori inventaris yang boleh dihubungkan ke RS Agent (PC, laptop, dll). Contoh: Dell T30
                        yang salah masuk Display bisa ikut dimonitor setelah Anda centang kategori tersebut — atau
                        perbaiki master barangnya.
                    </p>
                </header>

                {(flash?.success || recentlySuccessful) && (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash?.success ?? 'Pengaturan disimpan.'}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4 rounded-xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] sm:p-5">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">{data.kategori_codes.length} dipilih</Badge>
                            <span className="text-xs text-muted-foreground">dari {kategori.length} kategori</span>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={selectSuggested}>
                            <RotateCcw className="size-3.5" />
                            Pakai default
                        </Button>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="kategori-search">Cari kategori</Label>
                        <Input
                            id="kategori-search"
                            value={q}
                            onChange={(event) => setQ(event.target.value)}
                            placeholder="Kode atau nama… (KI005, Komputer)"
                            className="h-10"
                        />
                    </div>

                    <div className="max-h-[28rem] space-y-1 overflow-y-auto rounded-lg border border-border/70 p-2">
                        {filtered.length === 0 ? (
                            <p className="px-2 py-8 text-center text-sm text-muted-foreground">Tidak ada kategori cocok.</p>
                        ) : (
                            filtered.map((row) => {
                                const checked = data.kategori_codes.includes(row.kode_kategori);

                                return (
                                    <label
                                        key={row.kode_kategori}
                                        className="flex cursor-pointer items-start gap-3 rounded-md px-2 py-2.5 transition-colors hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            checked={checked}
                                            onCheckedChange={(value) => toggle(row.kode_kategori, value === true)}
                                            className="mt-0.5"
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block font-mono text-xs text-muted-foreground">
                                                {row.kode_kategori}
                                            </span>
                                            <span className="block text-sm font-medium">{row.nama_kategori}</span>
                                        </span>
                                    </label>
                                );
                            })
                        )}
                    </div>

                    <InputError message={errors.kategori_codes} />
                    <InputError message={errors['kategori_codes.0']} />

                    <div className="flex flex-wrap gap-2 pt-1">
                        <Button type="submit" disabled={processing} className="bg-teal-700 hover:bg-teal-800">
                            Simpan pengaturan
                        </Button>
                        <Button type="button" variant="ghost" asChild>
                            <Link href="/monitoring">Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
