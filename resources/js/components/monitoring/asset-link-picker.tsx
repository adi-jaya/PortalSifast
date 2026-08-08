import { Check, Link2, Search, Unlink } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type LinkableAssetOption = {
    id: number;
    label: string;
};

type LinkedAset = {
    id: number;
    kode_aset: string;
    no_seri: string | null;
};

type Props = {
    linkedAset: LinkedAset | null;
    options: LinkableAssetOption[];
    value: number | null;
    onChange: (asetId: number | null) => void;
    onSubmit: () => void;
    processing?: boolean;
    error?: string;
    recentlySuccessful?: boolean;
};

export function AssetLinkPicker({
    linkedAset,
    options,
    value,
    onChange,
    onSubmit,
    processing = false,
    error,
    recentlySuccessful = false,
}: Props) {
    const [query, setQuery] = useState('');
    const [editing, setEditing] = useState(!linkedAset);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) {
            return options.slice(0, 40);
        }

        return options.filter((option) => option.label.toLowerCase().includes(q)).slice(0, 40);
    }, [options, query]);

    if (linkedAset && !editing) {
        return (
            <div className="rounded-xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-[11px] font-medium tracking-[0.14em] text-teal-700 uppercase dark:text-teal-400">
                            Inventaris
                        </p>
                        <a
                            href={`/aset/${linkedAset.kode_aset}`}
                            className="mt-1 block truncate text-base font-semibold tracking-tight text-foreground underline-offset-2 hover:underline"
                        >
                            {linkedAset.kode_aset}
                        </a>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {linkedAset.no_seri ? `SN ${linkedAset.no_seri}` : 'Serial inventaris belum diisi'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" size="sm" asChild>
                            <a href={`/aset/${linkedAset.kode_aset}`}>Buka inventaris</a>
                        </Button>
                        <Button type="button" variant="ghost" size="sm" onClick={() => setEditing(true)}>
                            <Link2 className="size-3.5" />
                            Ganti
                        </Button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="text-[11px] font-medium tracking-[0.14em] text-teal-700 uppercase dark:text-teal-400">
                        Inventaris
                    </p>
                    <h2 className="mt-1 text-sm font-semibold tracking-tight">
                        {linkedAset ? 'Ganti tautan aset' : 'Hubungkan ke inventaris'}
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Ketik kode / nama / ruang. Hanya PC, laptop, mini PC, dan tablet.
                    </p>
                </div>
                {linkedAset ? (
                    <Button type="button" variant="ghost" size="sm" onClick={() => setEditing(false)}>
                        Batal
                    </Button>
                ) : null}
            </div>

            <div className="mt-4 space-y-3">
                <div className="space-y-2">
                    <Label htmlFor="aset-search">Cari aset</Label>
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                        <Input
                            id="aset-search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Contoh: INV-IT, Mini PC, Ruang IT…"
                            className="pl-8"
                            autoComplete="off"
                        />
                    </div>
                </div>

                <div
                    className="max-h-52 overflow-y-auto rounded-lg border border-border/70"
                    role="listbox"
                    aria-label="Daftar aset yang bisa dihubungkan"
                >
                    <button
                        type="button"
                        role="option"
                        aria-selected={value === null}
                        onClick={() => onChange(null)}
                        className={cn(
                            'flex w-full cursor-pointer items-center gap-2 border-b px-3 py-2.5 text-left text-sm transition-colors hover:bg-muted/50',
                            value === null && 'bg-muted/40',
                        )}
                    >
                        <Unlink className="size-3.5 shrink-0 text-muted-foreground" />
                        <span>Tidak terhubung</span>
                        {value === null ? <Check className="ml-auto size-3.5 text-teal-700" /> : null}
                    </button>
                    {filtered.length === 0 ? (
                        <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                            Tidak ada aset yang cocok. Coba kata kunci lain.
                        </p>
                    ) : (
                        filtered.map((asset) => (
                            <button
                                key={asset.id}
                                type="button"
                                role="option"
                                aria-selected={value === asset.id}
                                onClick={() => onChange(asset.id)}
                                className={cn(
                                    'flex w-full cursor-pointer items-start gap-2 border-b px-3 py-2.5 text-left text-sm last:border-0 transition-colors hover:bg-muted/50',
                                    value === asset.id && 'bg-teal-50/80 dark:bg-teal-950/30',
                                )}
                            >
                                <span className="min-w-0 flex-1 break-words">{asset.label}</span>
                                {value === asset.id ? (
                                    <Check className="mt-0.5 size-3.5 shrink-0 text-teal-700 dark:text-teal-300" />
                                ) : null}
                            </button>
                        ))
                    )}
                </div>

                <InputError message={error} />

                <div className="flex flex-wrap items-center gap-2">
                    <Button type="button" disabled={processing} onClick={onSubmit} className="bg-teal-700 hover:bg-teal-800">
                        Simpan tautan
                    </Button>
                    {recentlySuccessful ? (
                        <Badge
                            variant="outline"
                            className="border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200"
                        >
                            Tersimpan
                        </Badge>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
