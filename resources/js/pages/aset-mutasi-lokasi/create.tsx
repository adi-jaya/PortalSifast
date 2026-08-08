import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Search } from 'lucide-react';
import { FormEvent, useEffect, useRef, useState } from 'react';
import { AktorSearchInput, type AktorSelection } from '@/components/aset/aktor-search-input';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AsetOption = {
    id: number;
    kode_aset: string;
    nama_barang?: string | null;
    aset_ruang_id?: number | null;
    nama_ruang?: string | null;
    label?: string;
};

type Ruang = { id: number; kode_ruang: string; nama_ruang: string };

type Props = {
    initialAset?: AsetOption | null;
    ruangOptions: Ruang[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Mutasi Lokasi', href: '/aset-mutasi-lokasi' },
    { title: 'Buat', href: '/aset-mutasi-lokasi/create' },
];

export default function AsetMutasiLokasiCreate({ initialAset = null, ruangOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        aset_id: initialAset?.id ?? (null as number | null),
        aset_ruang_tujuan_id: '' as string,
        penerima_user_id: null as number | null,
        penerima_nik: null as string | null,
        tanggal_mutasi: new Date().toISOString().slice(0, 16),
        catatan: '',
    });

    const [asetLabel, setAsetLabel] = useState(
        initialAset ? `${initialAset.kode_aset} — ${initialAset.nama_barang ?? ''}` : '',
    );
    const [ruangAsal, setRuangAsal] = useState(initialAset?.nama_ruang ?? '');
    const [asetRuangId, setAsetRuangId] = useState<number | null>(initialAset?.aset_ruang_id ?? null);
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
            const res = await fetch(`/aset-mutasi-lokasi/search-aset?q=${encodeURIComponent(asetQuery)}`);
            const json = await res.json();
            setAsetResults(Array.isArray(json) ? json : []);
        }, 300);
    }, [asetQuery, data.aset_id]);

    useEffect(() => {
        setData({
            ...data,
            penerima_user_id: aktor?.user_id ?? null,
            penerima_nik: aktor?.nik ?? null,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [aktor]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/aset-mutasi-lokasi');
    };

    const tujuanOptions = ruangOptions.filter((r) => r.id !== asetRuangId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mutasi Lokasi Aset" />
            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-4">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/aset-mutasi-lokasi">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">Mutasi lokasi aset</h1>
                </div>

                <form onSubmit={submit} className="space-y-4 rounded-lg border p-4">
                    <div className="grid gap-2">
                        <Label>Aset</Label>
                        {data.aset_id ? (
                            <div className="space-y-1">
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
                                            setRuangAsal('');
                                            setAsetRuangId(null);
                                        }}
                                    >
                                        Ganti
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground">Ruang saat ini: {ruangAsal || '–'}</p>
                            </div>
                        ) : (
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={asetQuery}
                                    onChange={(e) => setAsetQuery(e.target.value)}
                                    placeholder="Cari kode aset..."
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
                                                        setRuangAsal(item.nama_ruang ?? '');
                                                        setAsetRuangId(item.aset_ruang_id ?? null);
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
                        <Label>Ruang tujuan</Label>
                        <Select
                            value={data.aset_ruang_tujuan_id}
                            onValueChange={(v) => setData('aset_ruang_tujuan_id', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih ruang..." />
                            </SelectTrigger>
                            <SelectContent>
                                {tujuanOptions.map((r) => (
                                    <SelectItem key={r.id} value={String(r.id)}>
                                        {r.nama_ruang} ({r.kode_ruang})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.aset_ruang_tujuan_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Penerima di lokasi baru</Label>
                        <AktorSearchInput
                            value={aktor}
                            onChange={setAktor}
                            userSearchUrl="/aset-mutasi-lokasi/search-user"
                            pegawaiSearchUrl="/aset-mutasi-lokasi/search-pegawai"
                            label="Cari penerima"
                        />
                        <InputError message={errors.penerima || errors.penerima_user_id || errors.penerima_nik} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="tanggal_mutasi">Tanggal mutasi</Label>
                        <Input
                            id="tanggal_mutasi"
                            type="datetime-local"
                            value={data.tanggal_mutasi}
                            onChange={(e) => setData('tanggal_mutasi', e.target.value)}
                        />
                        <InputError message={errors.tanggal_mutasi} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="catatan">Catatan</Label>
                        <Textarea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            rows={3}
                        />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan mutasi
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
