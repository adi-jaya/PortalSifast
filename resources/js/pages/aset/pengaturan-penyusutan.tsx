import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Pengaturan = {
    metode: string;
    residu_persen_default: number;
    umur_bulan_medis: number;
    umur_bulan_non_medis: number;
    umur_bulan_default: number;
};

type Props = {
    pengaturan: Pengaturan;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
    { title: 'Pengaturan Penyusutan', href: '/aset/pengaturan-penyusutan' },
];

function formatUmurLabel(bulan: number): string {
    if (bulan % 12 === 0) {
        return `${bulan / 12} tahun`;
    }

    return `${bulan} bulan`;
}

export default function PengaturanPenyusutan({ pengaturan }: Props) {
    const flash = (usePage().props as { flash?: { success?: string } }).flash;
    const { data, setData, put, processing, errors, recentlySuccessful, transform } = useForm({
        residu_persen_default: String(pengaturan.residu_persen_default),
        umur_bulan_medis: String(pengaturan.umur_bulan_medis),
        umur_bulan_non_medis: String(pengaturan.umur_bulan_non_medis),
        umur_bulan_default: String(pengaturan.umur_bulan_default),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((d) => ({
            residu_persen_default: Number(d.residu_persen_default),
            umur_bulan_medis: Number(d.umur_bulan_medis),
            umur_bulan_non_medis: Number(d.umur_bulan_non_medis),
            umur_bulan_default: Number(d.umur_bulan_default),
        }));
        put('/aset/pengaturan-penyusutan');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan Penyusutan" />

            <div className="mx-auto max-w-2xl space-y-6 px-4 pb-10 sm:px-0">
                <header className="border-b border-border/70 pb-5">
                    <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris portal
                    </p>
                    <h1 className="text-[1.75rem] font-semibold tracking-tight">Pengaturan penyusutan</h1>
                    <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
                        Atur default global. Nilai per aset di Edit tetap bisa diisi manual (override).
                        Bukan inflasi — ini penurunan nilai aset karena umur pakai (depresiasi).
                    </p>
                </header>

                {(flash?.success || recentlySuccessful) && (
                    <div className="rounded-lg border border-teal-700/20 bg-teal-50 px-4 py-3 text-sm text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                        {flash?.success ?? 'Pengaturan disimpan.'}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-5 rounded-xl border border-border/80 bg-card p-5">
                    <div className="space-y-2">
                        <Label>Metode</Label>
                        <div className="flex items-center gap-2">
                            <Badge variant="secondary">Garis lurus</Badge>
                            <span className="text-xs text-muted-foreground">
                                Satu-satunya metode saat ini (standar aset tetap)
                            </span>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="residu_persen_default">Nilai residu default (%)</Label>
                        <Input
                            id="residu_persen_default"
                            type="number"
                            min={0}
                            max={50}
                            step="0.1"
                            value={data.residu_persen_default}
                            onChange={(e) => setData('residu_persen_default', e.target.value)}
                            className="h-10"
                        />
                        <p className="text-xs text-muted-foreground">
                            Contoh 1% dari harga perolehan Rp6.000.000 → residu Rp60.000 bila aset belum isi nilai residu.
                        </p>
                        <InputError message={errors.residu_persen_default} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="umur_bulan_medis">Umur default medis (bulan)</Label>
                            <Input
                                id="umur_bulan_medis"
                                type="number"
                                min={1}
                                max={600}
                                value={data.umur_bulan_medis}
                                onChange={(e) => setData('umur_bulan_medis', e.target.value)}
                                className="h-10"
                            />
                            <p className="text-xs text-muted-foreground">
                                ≈ {formatUmurLabel(Number(data.umur_bulan_medis) || 0)}
                            </p>
                            <InputError message={errors.umur_bulan_medis} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="umur_bulan_non_medis">Umur default non-medis</Label>
                            <Input
                                id="umur_bulan_non_medis"
                                type="number"
                                min={1}
                                max={600}
                                value={data.umur_bulan_non_medis}
                                onChange={(e) => setData('umur_bulan_non_medis', e.target.value)}
                                className="h-10"
                            />
                            <p className="text-xs text-muted-foreground">
                                ≈ {formatUmurLabel(Number(data.umur_bulan_non_medis) || 0)}
                            </p>
                            <InputError message={errors.umur_bulan_non_medis} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="umur_bulan_default">Umur default umum</Label>
                            <Input
                                id="umur_bulan_default"
                                type="number"
                                min={1}
                                max={600}
                                value={data.umur_bulan_default}
                                onChange={(e) => setData('umur_bulan_default', e.target.value)}
                                className="h-10"
                            />
                            <p className="text-xs text-muted-foreground">
                                Jika kelas aset belum diisi · ≈ {formatUmurLabel(Number(data.umur_bulan_default) || 0)}
                            </p>
                            <InputError message={errors.umur_bulan_default} />
                        </div>
                    </div>

                    <div className="rounded-lg bg-muted/40 px-3 py-3 text-xs leading-relaxed text-muted-foreground">
                        Saat tambah/edit aset, jika <span className="font-medium text-foreground">umur manfaat</span> atau{' '}
                        <span className="font-medium text-foreground">nilai residu</span> dikosongkan, sistem memakai
                        angka di atas. Isi manual di Edit aset untuk override per unit/barang.
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan pengaturan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
