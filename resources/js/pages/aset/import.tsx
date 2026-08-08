import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, FileDown, Upload } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Import CSV', href: '/aset/import' },
];

type PreviewRow = {
    line: number;
    ok: boolean;
    errors: string[];
    summary: {
        kelas_aset: string | null;
        kode_katalog: string | null;
        kode_ruang: string | null;
        no_seri: string | null;
        nama_barang: string | null;
    };
};

type Preview = {
    token: string;
    error_count: number;
    ok_count: number;
    rows: PreviewRow[];
};

type Hints = {
    ruang_count: number;
    sample_ruang: { kode_ruang: string; nama_ruang: string }[];
    sample_non_alkes: { kode: string; nama_alat: string } | null;
    template_has_examples: boolean;
};

type Props = {
    templateUrl: string;
    hints: Hints;
    preview: Preview | null;
};

type Flash = { success?: string; error?: string };

export default function AsetImport({ templateUrl, hints, preview }: Props) {
    const flash = (usePage().props.flash ?? {}) as Flash;
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    const submitPreview = (e: FormEvent) => {
        e.preventDefault();
        if (!data.file) {
            return;
        }
        post('/aset/import/preview', {
            forceFormData: true,
            onSuccess: () => reset('file'),
        });
    };

    const confirmImport = () => {
        if (!preview || preview.error_count > 0) {
            return;
        }
        router.post('/aset/import', { token: preview.token });
    };

    const clearPreview = () => {
        router.delete('/aset/import/preview');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Import Aset CSV" />

            <div className="relative mx-auto max-w-3xl space-y-5 px-4 pb-28 pt-2 sm:px-0">
                <header className="mb-2 border-b border-border/70 pb-5">
                    <div className="mb-3">
                        <Button variant="ghost" size="sm" asChild className="-ml-2">
                            <Link href="/aset">
                                <ArrowLeft className="mr-1.5 h-3.5 w-3.5" />
                                Kembali
                            </Link>
                        </Button>
                    </div>
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris portal
                    </p>
                    <h1 className="text-[1.75rem] leading-tight font-semibold tracking-tight">Import aset CSV</h1>
                    <p className="mt-1.5 max-w-xl text-sm leading-relaxed text-muted-foreground">
                        Unduh template → isi (1 baris = 1 unit) → preview → konfirmasi. Kolom{' '}
                        <span className="font-medium text-foreground">kode_ruang</span> harus cocok dengan Master
                        Ruang portal (bukan kode fiktif).
                    </p>
                </header>

                {flash.success && (
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-900 dark:text-emerald-200">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                        {flash.error}
                    </div>
                )}

                <section className="rounded-xl border border-border/80 bg-card p-5 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <h2 className="text-sm font-semibold tracking-tight">Kode ruang yang dipakai</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ada {hints.ruang_count} ruang di Master Ruang.
                        {hints.template_has_examples
                            ? ' Template contoh otomatis memakai kode ruang nyata dari daftar ini.'
                            : ' Tambah ruang dulu agar template berisi contoh yang bisa diimpor.'}
                    </p>
                    {hints.sample_ruang.length > 0 ? (
                        <ul className="mt-3 flex flex-wrap gap-2">
                            {hints.sample_ruang.map((ruang) => (
                                <li key={ruang.kode_ruang}>
                                    <Badge variant="outline" className="font-mono text-[11px]">
                                        {ruang.kode_ruang}
                                        <span className="ml-1 font-sans font-normal text-muted-foreground">
                                            {ruang.nama_ruang}
                                        </span>
                                    </Badge>
                                </li>
                            ))}
                            {hints.ruang_count > hints.sample_ruang.length ? (
                                <li>
                                    <Badge variant="secondary" className="text-[11px]">
                                        +{hints.ruang_count - hints.sample_ruang.length} lainnya
                                    </Badge>
                                </li>
                            ) : null}
                        </ul>
                    ) : (
                        <p className="mt-3 text-sm text-amber-800 dark:text-amber-200">
                            Belum ada ruang. Tambah di Master Ruang sebelum import.
                        </p>
                    )}
                    {hints.sample_non_alkes ? (
                        <p className="mt-3 text-xs text-muted-foreground">
                            Contoh katalog non-medis di template:{' '}
                            <span className="font-mono text-foreground">{hints.sample_non_alkes.kode}</span>{' '}
                            ({hints.sample_non_alkes.nama_alat})
                        </p>
                    ) : null}
                    <div className="mt-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/aset/master/ruang">Buka Master Ruang</Link>
                        </Button>
                    </div>
                </section>

                <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <div className="border-b border-border/60 bg-muted/30 px-5 py-4">
                        <h2 className="text-base font-semibold tracking-tight">1. Template & upload</h2>
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            CSV UTF-8, maksimal 500 baris. Jangan ubah nama kolom di baris header. Excel ID
                            (pemisah titik-koma) juga diterima.
                        </p>
                    </div>
                    <div className="space-y-5 p-5">
                        <Button variant="outline" size="sm" asChild>
                            <a href={templateUrl}>
                                <FileDown className="mr-1.5 h-3.5 w-3.5" />
                                Unduh template CSV
                            </a>
                        </Button>

                        <form onSubmit={submitPreview} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="file">File CSV</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                                    className="h-10 cursor-pointer"
                                />
                                <InputError message={errors.file} />
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing || !data.file}
                                className="bg-teal-700 hover:bg-teal-800"
                            >
                                <Upload className="mr-1.5 h-3.5 w-3.5" />
                                {processing ? 'Memproses...' : 'Preview'}
                            </Button>
                        </form>
                    </div>
                </section>

                {preview && (
                    <section className="rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 bg-muted/30 px-5 py-4">
                            <div>
                                <h2 className="text-base font-semibold tracking-tight">2. Preview</h2>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {preview.ok_count} OK · {preview.error_count} error
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Badge variant={preview.error_count > 0 ? 'destructive' : 'secondary'}>
                                    {preview.error_count > 0 ? 'Perbaiki dulu' : 'Siap import'}
                                </Badge>
                                <Button type="button" variant="ghost" size="sm" onClick={clearPreview}>
                                    Hapus preview
                                </Button>
                            </div>
                        </div>
                        <div className="overflow-x-auto p-5">
                            <table className="w-full min-w-[640px] text-left text-sm">
                                <thead>
                                    <tr className="border-b text-xs text-muted-foreground">
                                        <th className="py-2 pr-3 font-medium">Baris</th>
                                        <th className="py-2 pr-3 font-medium">Status</th>
                                        <th className="py-2 pr-3 font-medium">Katalog</th>
                                        <th className="py-2 pr-3 font-medium">Ruang</th>
                                        <th className="py-2 pr-3 font-medium">Seri</th>
                                        <th className="py-2 font-medium">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {preview.rows.map((row) => (
                                        <tr key={row.line} className="border-b border-border/50 align-top">
                                            <td className="py-2 pr-3 font-mono text-xs">{row.line}</td>
                                            <td className="py-2 pr-3">
                                                {row.ok ? (
                                                    <Badge variant="secondary">OK</Badge>
                                                ) : (
                                                    <Badge variant="destructive">Error</Badge>
                                                )}
                                            </td>
                                            <td className="py-2 pr-3">
                                                <div className="font-medium">{row.summary.nama_barang || '—'}</div>
                                                <div className="font-mono text-[11px] text-muted-foreground">
                                                    {row.summary.kelas_aset} · {row.summary.kode_katalog || '—'}
                                                </div>
                                            </td>
                                            <td className="py-2 pr-3 font-mono text-xs">{row.summary.kode_ruang || '—'}</td>
                                            <td className="py-2 pr-3 font-mono text-xs">{row.summary.no_seri || '—'}</td>
                                            <td className="py-2 text-xs text-destructive">
                                                {row.errors.length > 0 ? row.errors.join(' ') : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}

                <div className="fixed inset-x-0 bottom-0 z-40 border-t border-border/80 bg-background/90 backdrop-blur-md">
                    <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                        <p className="min-w-0 truncate text-xs text-muted-foreground sm:text-sm">
                            {preview
                                ? preview.error_count > 0
                                    ? 'Perbaiki CSV lalu preview ulang'
                                    : `${preview.ok_count} unit siap diimpor`
                                : 'Upload CSV untuk preview'}
                        </p>
                        <div className="flex shrink-0 items-center gap-2">
                            <Button type="button" variant="ghost" size="sm" asChild>
                                <Link href="/aset">Batal</Link>
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                disabled={!preview || preview.error_count > 0}
                                onClick={confirmImport}
                                className="bg-teal-700 hover:bg-teal-800"
                            >
                                Konfirmasi import
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
