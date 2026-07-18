import { Search, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
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
import { cn } from '@/lib/utils';

export type AktorSelection = {
    user_id: number | null;
    nik: string | null;
    label: string;
};

type Mode = 'user' | 'pegawai';

type Props = {
    value: AktorSelection | null;
    onChange: (value: AktorSelection | null) => void;
    userSearchUrl: string;
    pegawaiSearchUrl: string;
    label?: string;
    className?: string;
};

type ResultItem = { id?: number; nik?: string; label: string };

export function AktorSearchInput({
    value,
    onChange,
    userSearchUrl,
    pegawaiSearchUrl,
    label = 'Pilih aktor',
    className,
}: Props) {
    const [mode, setMode] = useState<Mode>(value?.nik ? 'pegawai' : 'user');
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ResultItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const fetchResults = useCallback(
        async (q: string, currentMode: Mode) => {
            if (!q.trim()) {
                setResults([]);

                return;
            }
            setLoading(true);
            try {
                const url = currentMode === 'user' ? userSearchUrl : pegawaiSearchUrl;
                const res = await fetch(`${url}?q=${encodeURIComponent(q.trim())}`);
                const data = await res.json();
                setResults(Array.isArray(data) ? data : []);
            } catch {
                setResults([]);
            } finally {
                setLoading(false);
            }
        },
        [userSearchUrl, pegawaiSearchUrl],
    );

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchResults(query, mode), 300);

        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [query, mode, fetchResults]);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onClick);

        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    if (value) {
        return (
            <div className={cn('flex gap-2', className)}>
                <div className="flex flex-1 items-center rounded-md border bg-muted/50 px-3 py-2 text-sm">
                    <span className="truncate">{value.label}</span>
                </div>
                <Button type="button" variant="outline" size="icon" onClick={() => onChange(null)}>
                    <X className="h-4 w-4" />
                </Button>
            </div>
        );
    }

    return (
        <div ref={containerRef} className={cn('space-y-2', className)}>
            <div className="grid gap-2 sm:grid-cols-2">
                <div className="grid gap-1">
                    <Label className="text-xs text-muted-foreground">Tipe</Label>
                    <Select
                        value={mode}
                        onValueChange={(v) => {
                            setMode(v as Mode);
                            setQuery('');
                            setResults([]);
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="user">User portal</SelectItem>
                            <SelectItem value="pegawai">Pegawai SIMRS</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="grid gap-1 sm:col-span-1">
                    <Label className="text-xs text-muted-foreground">{label}</Label>
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onFocus={() => setOpen(true)}
                            placeholder={mode === 'user' ? 'Cari nama / email...' : 'Cari nama / NIK...'}
                            className="pl-9"
                            autoComplete="off"
                        />
                    </div>
                </div>
            </div>
            {open && (query.trim() || loading) && (
                <ul className="max-h-48 overflow-auto rounded-md border bg-popover py-1 text-sm shadow-md">
                    {loading && <li className="px-3 py-2 text-muted-foreground">Mencari...</li>}
                    {!loading && results.length === 0 && (
                        <li className="px-3 py-2 text-muted-foreground">Tidak ditemukan</li>
                    )}
                    {!loading &&
                        results.map((item) => (
                            <li key={item.id ?? item.nik}>
                                <button
                                    type="button"
                                    className="w-full px-3 py-2 text-left hover:bg-accent"
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => {
                                        onChange({
                                            user_id: mode === 'user' ? (item.id ?? null) : null,
                                            nik: mode === 'pegawai' ? (item.nik ?? null) : null,
                                            label: item.label,
                                        });
                                        setOpen(false);
                                        setQuery('');
                                    }}
                                >
                                    {item.label}
                                </button>
                            </li>
                        ))}
                </ul>
            )}
        </div>
    );
}
