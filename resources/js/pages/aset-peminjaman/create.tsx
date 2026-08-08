import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Search } from 'lucide-react';
import { FormEvent, useEffect, useRef, useState } from 'react';
import { AktorSearchInput, type AktorSelection } from '@/components/aset/aktor-search-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AsetOption = {
    id: number;
    kode_aset: string;
    nama_barang?: string | null;
    label?: string;
};

type Props = {
    initialAset?: AsetOption | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Peminjaman Aset', href: '/aset-peminjaman' },
    { title: 'Buat', href: '/aset-peminjaman/create' },
];

export default function AsetPeminjamanCreate({ initialAset = null }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        aset_id: initialAset?.id ?? (null as number | null),
        peminjam_user_id: null as number | null,
        peminjam_nik: null as string | null,
        tanggal_pinjam: new Date().toISOString().slice(0, 16),
        tanggal_kembali_rencana: '',
        catatan: '',
    });

    const [asetLabel, setAsetLabel] = useState(
        initialAset ? `${initialAset.kode_aset} — ${initialAset.nama_barang ?? ''}` : '',
    );
    const [asetQuery, setAsetQuery] = useState('');
    const [asetResults, setAsetResults] = useState<AsetOption[]>([]);
    const [aktor, setAktor] = useState<AktorSelection | null>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (!asetQuery.trim() || data.aset_id) {
            setAsetResults([]);

            return;
        }
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            const res = await fetch(`/aset-peminjaman/search-aset?q=${encodeURIComponent(asetQuery)}`);
            const json = await res.json();
            setAsetResults(Array.isArray(json) ? json : []);
        }, 300);
    }, [asetQuery, data.aset_id]);

    useEffect(() => {
        setData({
            ...data,
            peminjam_user_id: aktor?.user_id ?? null,
            peminjam_nik: aktor?.nik ?? null,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [aktor]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/aset-peminjaman');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pinjamkan Aset" />
            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-4">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/aset-peminjaman">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">Pinjamkan aset</h1>
                </div>

                <form onSubmit={submit} className="space-y-4 rounded-lg border p-4">
                    <div className="grid gap-2">
                        <Label>Aset</Label>
                        {data.aset_id ? (
                            <div className="flex gap-2">
                                <div className="flex-1 rounded-md border bg-muted/50 px-3 py-2 text-sm">
                                    {asetLabel}
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setData('aset_id', null);
                                        setAsetLabel('');
                                    }}
                                >
                                    Ganti
                                </Button>
                            </div>
                        ) : (
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={asetQuery}
                                    onChange={(e) => setAsetQuery(e.target.value)}
                                    placeholder="Cari kode aset / nama..."
                                    className="pl-9"
                                />
                                {asetResults.length > 0 && (
                                    <ul className="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md border bg-popover shadow">
                                        {asetResults.map((item) => (
                                            <li key={item.id}>
                                                <button
                                                    type="button"
                                                    className="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                                                    onClick={() => {
                                                        setData('aset_id', item.id);
                                                        setAsetLabel(item.label ?? item.kode_aset);
                                                        setAsetQuery('');
                                                        setAsetResults([]);
                                                    }}
                                                >
                                                    {item.label ?? item.kode_aset}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        )}
                        <InputError message={errors.aset_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Peminjam</Label>
                        <AktorSearchInput
                            value={aktor}
                            onChange={setAktor}
                            userSearchUrl="/aset-peminjaman/search-user"
                            pegawaiSearchUrl="/aset-peminjaman/search-pegawai"
                            label="Cari peminjam"
                        />
                        <InputError message={errors.peminjam || errors.peminjam_user_id || errors.peminjam_nik} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="tanggal_pinjam">Tanggal pinjam</Label>
                            <Input
                                id="tanggal_pinjam"
                                type="datetime-local"
                                value={data.tanggal_pinjam}
                                onChange={(e) => setData('tanggal_pinjam', e.target.value)}
                            />
                            <InputError message={errors.tanggal_pinjam} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="tanggal_kembali_rencana">Rencana kembali (opsional)</Label>
                            <Input
                                id="tanggal_kembali_rencana"
                                type="date"
                                value={data.tanggal_kembali_rencana}
                                onChange={(e) => setData('tanggal_kembali_rencana', e.target.value)}
                            />
                            <InputError message={errors.tanggal_kembali_rencana} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="catatan">Catatan</Label>
                        <Textarea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            rows={3}
                        />
                        <InputError message={errors.catatan} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan peminjaman
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
