import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Item = {
    id: number;
    nama: string;
    kategori: string | null;
    berlaku: boolean;
};

type Props = {
    kendaraan: { id: number; nama: string };
    items: Item[];
};

export default function DriverKendaraanItems({ kendaraan, items }: Props) {
    const flash = (usePage().props.flash ?? {}) as { success?: string };
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Master Kendaraan', href: '/driver/kendaraan' },
        { title: kendaraan.nama, href: '#' },
    ];

    const { data, setData, put, processing } = useForm({
        items: items.map((item) => ({
            driver_checklist_item_id: item.id,
            berlaku: item.berlaku,
        })),
    });

    const toggle = (itemId: number, berlaku: boolean) => {
        setData(
            'items',
            data.items.map((row) =>
                row.driver_checklist_item_id === itemId ? { ...row, berlaku } : row,
            ),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Item ${kendaraan.nama}`} />
            <form
                className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    put(`/driver/kendaraan/${kendaraan.id}/item`);
                }}
            >
                <div>
                    <h1 className="text-xl font-semibold">Item berlaku — {kendaraan.nama}</h1>
                    <p className="text-sm text-muted-foreground">
                        Nonaktifkan item yang tidak berlaku (akan tampil sebagai N/A).
                    </p>
                </div>

                {flash.success && (
                    <div className="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm">
                        {flash.success}
                    </div>
                )}

                <div className="grid gap-2">
                    {items.map((item) => {
                        const current = data.items.find((row) => row.driver_checklist_item_id === item.id);
                        return (
                            <label key={item.id} className="flex items-center justify-between rounded-lg border p-3">
                                <span>
                                    <span className="font-medium">{item.nama}</span>
                                    {item.kategori && (
                                        <span className="ml-2 text-xs text-muted-foreground">{item.kategori}</span>
                                    )}
                                </span>
                                <input
                                    type="checkbox"
                                    checked={Boolean(current?.berlaku)}
                                    onChange={(e) => toggle(item.id, e.target.checked)}
                                />
                            </label>
                        );
                    })}
                </div>

                <Button type="submit" disabled={processing}>
                    Simpan konfigurasi
                </Button>
            </form>
        </AppLayout>
    );
}
