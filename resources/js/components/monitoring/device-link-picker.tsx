import { Activity, Check, Search, Unlink } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type LinkableDeviceOption = {
    id: number;
    label: string;
    status: string;
};

type Props = {
    linkedDeviceId: number | null;
    options: LinkableDeviceOption[];
    value: number | null;
    onChange: (deviceId: number | null) => void;
    onSubmit: () => void;
    onUnlink?: () => void;
    processing?: boolean;
    error?: string;
    recentlySuccessful?: boolean;
    defaultEditing?: boolean;
};

export function DeviceLinkPicker({
    linkedDeviceId,
    options,
    value,
    onChange,
    onSubmit,
    onUnlink,
    processing = false,
    error,
    recentlySuccessful = false,
    defaultEditing = false,
}: Props) {
    const [query, setQuery] = useState('');
    const [editing, setEditing] = useState(defaultEditing || !linkedDeviceId);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) {
            return options.slice(0, 40);
        }

        return options.filter((option) => option.label.toLowerCase().includes(q)).slice(0, 40);
    }, [options, query]);

    if (linkedDeviceId && !editing) {
        return (
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" onClick={() => setEditing(true)}>
                    Ganti perangkat
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled={processing}
                    onClick={() => onUnlink?.()}
                >
                    <Unlink className="size-3.5" />
                    Lepas tautan
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-3 rounded-lg border border-border/70 bg-muted/20 p-3">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="text-sm font-medium">Hubungkan perangkat RS Agent</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        Cari hostname atau IP yang belum terhubung ke aset lain.
                    </p>
                </div>
                {linkedDeviceId ? (
                    <Button type="button" variant="ghost" size="sm" onClick={() => setEditing(false)}>
                        Batal
                    </Button>
                ) : null}
            </div>

            <div className="space-y-2">
                <Label htmlFor="device-search">Cari perangkat</Label>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                    <Input
                        id="device-search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Contoh: W11OKY, 10.10.10.5…"
                        className="pl-8"
                        autoComplete="off"
                    />
                </div>
            </div>

            <div
                className="max-h-48 overflow-y-auto rounded-lg border border-border/70 bg-card"
                role="listbox"
                aria-label="Daftar perangkat yang bisa dihubungkan"
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
                        Tidak ada perangkat cocok. Pastikan agent sudah register.
                    </p>
                ) : (
                    filtered.map((device) => (
                        <button
                            key={device.id}
                            type="button"
                            role="option"
                            aria-selected={value === device.id}
                            onClick={() => onChange(device.id)}
                            className={cn(
                                'flex w-full cursor-pointer items-start gap-2 border-b px-3 py-2.5 text-left text-sm last:border-0 transition-colors hover:bg-muted/50',
                                value === device.id && 'bg-teal-50/80 dark:bg-teal-950/30',
                            )}
                        >
                            <Activity
                                className={cn(
                                    'mt-0.5 size-3.5 shrink-0',
                                    device.status === 'online'
                                        ? 'text-emerald-600'
                                        : 'text-amber-600',
                                )}
                            />
                            <span className="min-w-0 flex-1 break-words">{device.label}</span>
                            {value === device.id ? (
                                <Check className="mt-0.5 size-3.5 shrink-0 text-teal-700 dark:text-teal-300" />
                            ) : null}
                        </button>
                    ))
                )}
            </div>

            <InputError message={error} />

            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    size="sm"
                    disabled={processing}
                    onClick={onSubmit}
                    className="bg-teal-700 hover:bg-teal-800"
                >
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
    );
}
