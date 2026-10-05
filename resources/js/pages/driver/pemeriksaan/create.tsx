import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Item = {
    id: number;
    nama: string;
    kategori: string | null;
    urutan: number;
    berlaku: boolean;
};

type Props = {
    kendaraan: { id: number; nama: string; no_polisi: string | null };
    pemeriksaan_ke: number;
    tanggal: string;
    waktu: string;
    petugas: string;
    items: Item[];
};

type ItemForm = {
    driver_checklist_item_id: number;
    hasil: 'layak' | 'tidak_layak';
    temuan: string;
    rekomendasi: string;
    keterangan: string;
};

export default function DriverPemeriksaanCreate({
    kendaraan,
    pemeriksaan_ke,
    tanggal,
    waktu,
    petugas,
    items,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Pemeriksaan', href: '/driver/pemeriksaan' },
        { title: kendaraan.nama, href: '#' },
    ];

    const applicable = items.filter((item) => item.berlaku);

    const { data, setData, post, processing, errors } = useForm({
        driver_kendaraan_id: kendaraan.id,
        catatan: '',
        items: applicable.map(
            (item): ItemForm => ({
                driver_checklist_item_id: item.id,
                hasil: 'layak',
                temuan: '',
                rekomendasi: '',
                keterangan: '',
            }),
        ),
    });

    const setItemField = (itemId: number, field: keyof ItemForm, value: string) => {
        setData(
            'items',
            data.items.map((row) =>
                row.driver_checklist_item_id === itemId ? { ...row, [field]: value } : row,
            ),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pemeriksaan ${kendaraan.nama}`} />
            <form
                className="mx-auto flex w-full max-w-xl flex-col gap-4 p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/driver/pemeriksaan');
                }}
            >
                <div>
                    <h1 className="text-xl font-semibold">{kendaraan.nama}</h1>
                    <p className="text-sm text-muted-foreground">
                        {tanggal} · {waktu} · Pemeriksaan ke-{pemeriksaan_ke}
                    </p>
                    <p className="text-sm text-muted-foreground">Petugas: {petugas}</p>
                </div>

                <div className="grid gap-3">
                    {items.map((item) => {
                        if (!item.berlaku) {
                            return (
                                <div key={item.id} className="rounded-lg border bg-muted/40 p-4">
                                    <div className="font-medium">{item.nama}</div>
                                    <p className="text-sm text-muted-foreground">
                                        Tidak berlaku untuk kendaraan ini
                                    </p>
                                </div>
                            );
                        }

                        const current = data.items.find((row) => row.driver_checklist_item_id === item.id);

                        return (
                            <div key={item.id} className="rounded-lg border p-4">
                                <div className="mb-3 font-medium">{item.nama}</div>
                                <div className="grid gap-2">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name={`hasil-${item.id}`}
                                            checked={current?.hasil === 'layak'}
                                            onChange={() => setItemField(item.id, 'hasil', 'layak')}
                                        />
                                        Baik
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name={`hasil-${item.id}`}
                                            checked={current?.hasil === 'tidak_layak'}
                                            onChange={() => setItemField(item.id, 'hasil', 'tidak_layak')}
                                        />
                                        Tidak Baik
                                    </label>
                                </div>

                                {current?.hasil === 'tidak_layak' && (
                                    <div className="mt-3 grid gap-2">
                                        <div className="grid gap-1">
                                            <Label>Temuan</Label>
                                            <Textarea
                                                value={current.temuan}
                                                onChange={(e) => setItemField(item.id, 'temuan', e.target.value)}
                                                rows={2}
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Label>Rekomendasi</Label>
                                            <Textarea
                                                value={current.rekomendasi}
                                                onChange={(e) =>
                                                    setItemField(item.id, 'rekomendasi', e.target.value)
                                                }
                                                rows={2}
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <Label>Keterangan (opsional)</Label>
                                            <Textarea
                                                value={current.keterangan}
                                                onChange={(e) =>
                                                    setItemField(item.id, 'keterangan', e.target.value)
                                                }
                                                rows={2}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                    <InputError message={errors.items} />
                    <InputError message={errors.driver_kendaraan_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="catatan">Catatan (opsional)</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        rows={3}
                    />
                    <InputError message={errors.catatan} />
                </div>

                <Button type="submit" disabled={processing} className="w-full">
                    Simpan Pemeriksaan
                </Button>
            </form>
        </AppLayout>
    );
}
