import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

type Item = { id: number; nama: string; urutan: number };

type Props = {
    ruang: { id: number; kode: string | null; nama: string; nama_area: string | null };
    template: { id: number; nama: string };
    items: Item[];
    statuses: string[];
};

export default function PatroliCheckinScan({ ruang, template, items, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Check-in', href: '/patroli/checkin' },
        { title: ruang.nama, href: `/patroli/scan/${ruang.id}` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        patroli_ruang_id: ruang.id,
        catatan: '',
        items: items.map((item) => ({
            patroli_template_item_id: item.id,
            status: 'berfungsi',
        })),
    });

    const setStatus = (itemId: number, status: string) => {
        setData(
            'items',
            data.items.map((row) =>
                row.patroli_template_item_id === itemId ? { ...row, status } : row,
            ),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Scan Patroli ${ruang.nama}`} />
            <form
                className="mx-auto flex max-w-xl flex-col gap-4 p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/patroli/checkin');
                }}
            >
                <div>
                    <h1 className="text-xl font-semibold">{ruang.nama}</h1>
                    <p className="text-sm text-muted-foreground">
                        {ruang.nama_area}
                        {ruang.kode ? ` · ${ruang.kode}` : ''} · Template: {template.nama}
                    </p>
                </div>

                <div className="grid gap-3 rounded-md border p-4">
                    {items.map((item) => {
                        const current = data.items.find((row) => row.patroli_template_item_id === item.id);
                        return (
                            <div key={item.id} className="flex flex-wrap items-center justify-between gap-2">
                                <Label className="font-medium">{item.nama}</Label>
                                <Select
                                    value={current?.status ?? 'berfungsi'}
                                    onValueChange={(value) => setStatus(item.id, value)}
                                >
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem key={status} value={status}>
                                                {status.replaceAll('_', ' ')}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        );
                    })}
                    <InputError message={errors.items} />
                    <InputError message={errors.patroli_ruang_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="catatan">Catatan (opsional)</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                    />
                </div>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        Simpan check-in
                    </Button>
                    <Button asChild type="button" variant="outline">
                        <Link href="/patroli/checkin">Batal</Link>
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
