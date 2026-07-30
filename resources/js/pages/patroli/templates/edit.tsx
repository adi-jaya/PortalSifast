import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ItemDraft = {
    id?: number;
    nama: string;
    urutan: number;
    is_active: boolean;
};

type Props = {
    template: {
        id: number;
        nama: string;
        deskripsi: string | null;
        is_active: boolean;
        items: ItemDraft[];
    };
};

export default function PatroliTemplatesEdit({ template }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Template Patroli', href: '/patroli/templates' },
        { title: template.nama, href: `/patroli/templates/${template.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm<{
        nama: string;
        deskripsi: string;
        is_active: boolean;
        items: ItemDraft[];
    }>({
        nama: template.nama,
        deskripsi: template.deskripsi ?? '',
        is_active: template.is_active,
        items: template.items,
    });

    const updateItem = (index: number, patch: Partial<ItemDraft>) => {
        setData(
            'items',
            data.items.map((item, i) => (i === index ? { ...item, ...patch } : item)),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Template: ${template.nama}`} />
            <form
                className="mx-auto flex max-w-2xl flex-col gap-4 p-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    put(`/patroli/templates/${template.id}`);
                }}
            >
                <div>
                    <h1 className="text-xl font-semibold">Edit Template</h1>
                    <p className="text-sm text-muted-foreground">Perubahan item tidak mengubah check-in lama (snapshot)</p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="nama">Nama template</Label>
                    <Input id="nama" value={data.nama} onChange={(e) => setData('nama', e.target.value)} />
                    <InputError message={errors.nama} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="deskripsi">Deskripsi</Label>
                    <Textarea id="deskripsi" value={data.deskripsi} onChange={(e) => setData('deskripsi', e.target.value)} />
                </div>

                <div className="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked === true)}
                    />
                    <Label htmlFor="is_active">Aktif</Label>
                </div>

                <div className="grid gap-3 rounded-md border p-4">
                    <div className="flex items-center justify-between">
                        <Label>Item checklist</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setData('items', [
                                    ...data.items,
                                    { nama: '', urutan: data.items.length, is_active: true },
                                ])
                            }
                        >
                            <Plus className="mr-1 h-4 w-4" /> Item
                        </Button>
                    </div>
                    <InputError message={errors.items} />
                    {data.items.map((item, index) => (
                        <div key={item.id ?? `new-${index}`} className="flex flex-wrap items-center gap-2">
                            <Input
                                className="min-w-[180px] flex-1"
                                value={item.nama}
                                onChange={(e) => updateItem(index, { nama: e.target.value })}
                            />
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    checked={item.is_active}
                                    onCheckedChange={(checked) => updateItem(index, { is_active: checked === true })}
                                />
                                <span className="text-xs text-muted-foreground">Aktif</span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setData('items', data.items.filter((_, i) => i !== index))}
                                disabled={data.items.length <= 1}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </div>
                    ))}
                </div>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                    <Button asChild type="button" variant="outline">
                        <Link href="/patroli/templates">Kembali</Link>
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
