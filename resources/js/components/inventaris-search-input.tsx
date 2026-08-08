import { Search, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export type InventarisSearchResult = {
    asset_id?: number | null;
    kode_aset?: string | null;
    no_inventaris: string;
    kode_barang: string;
    nama_barang: string;
    nama_ruang: string | null;
    status_barang: string | null;
    sumber?: 'portal' | 'simrs';
};

export type AssetSelection = {
    asset_id: number | null;
    asset_no_inventaris: string | null;
    label: string;
};

type Props = {
    value: string | null;
    assetId?: number | null;
    onChange: (selection: AssetSelection | null) => void;
    /** Label to show when value is set from server (e.g. edit form) */
    initialLabel?: string | null;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
};

export function InventarisSearchInput({
    value,
    assetId = null,
    onChange,
    initialLabel,
    placeholder,
    disabled,
    className,
}: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<InventarisSearchResult[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState<string | null>(initialLabel ?? null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const fetchResults = useCallback(async (q: string) => {
        if (!q.trim()) {
            setResults([]);

            return;
        }
        setLoading(true);
        try {
            const params = new URLSearchParams({ q: q.trim() });
            const res = await fetch(`/tickets/search-for-inventaris?${params}`);
            const data = await res.json();
            setResults(Array.isArray(data) ? data : []);
        } catch {
            setResults([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            fetchResults(query);
        }, 300);

        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [query, fetchResults]);

    const handleSelect = (item: InventarisSearchResult) => {
        const code = item.kode_aset || item.no_inventaris;
        onChange({
            asset_id: item.asset_id ?? null,
            asset_no_inventaris: item.no_inventaris,
            label: `${code} — ${item.nama_barang}`,
        });
        setSelectedLabel(`${code} — ${item.nama_barang}`);
        setQuery('');
        setResults([]);
        setIsOpen(false);
    };

    const handleClear = () => {
        onChange(null);
        setSelectedLabel(null);
        setQuery('');
        setResults([]);
    };

    const handleBlur = () => {
        setTimeout(() => setIsOpen(false), 150);
    };

    const handleFocus = () => {
        if (value && selectedLabel) return;
        if (query) fetchResults(query);
        setIsOpen(true);
    };

    useEffect(() => {
        if ((value || assetId) && initialLabel) setSelectedLabel(initialLabel);
        else if (!value && !assetId) setSelectedLabel(null);
    }, [value, assetId, initialLabel]);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);

        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    if (value || assetId) {
        return (
            <div className={cn('flex gap-2', className)}>
                <div className="flex flex-1 items-center gap-2 rounded-md border bg-muted/50 px-3 py-2 text-sm">
                    <Search className="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span className="truncate">{selectedLabel ?? value}</span>
                    {assetId ? (
                        <Badge variant="secondary" className="ml-auto shrink-0 text-[10px]">
                            Portal
                        </Badge>
                    ) : null}
                </div>
                <Button type="button" variant="outline" size="icon" onClick={handleClear} disabled={disabled}>
                    <X className="h-4 w-4" />
                </Button>
            </div>
        );
    }

    return (
        <div ref={containerRef} className={cn('relative', className)}>
            <div className="relative">
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    placeholder={placeholder ?? 'Cari kode aset portal / SIMRS / nama barang...'}
                    disabled={disabled}
                    className="pl-9"
                    autoComplete="off"
                />
            </div>
            {isOpen && (query.trim() || loading) && (
                <ul className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover py-1 text-sm shadow-md">
                    {loading && (
                        <li className="px-3 py-2 text-muted-foreground">Mencari...</li>
                    )}
                    {!loading && results.length === 0 && (
                        <li className="px-3 py-2 text-muted-foreground">Tidak ditemukan</li>
                    )}
                    {!loading &&
                        results.map((item) => (
                            <li key={`${item.sumber ?? 'x'}-${item.asset_id ?? item.no_inventaris}`}>
                                <button
                                    type="button"
                                    className="flex w-full flex-col gap-0.5 px-3 py-2 text-left hover:bg-accent"
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => handleSelect(item)}
                                >
                                    <span className="flex items-center gap-2 font-medium">
                                        <span className="font-mono text-xs">
                                            {item.kode_aset || item.no_inventaris}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            className="text-[10px]"
                                        >
                                            {item.sumber === 'simrs' ? 'SIMRS' : 'Portal'}
                                        </Badge>
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {item.nama_barang}
                                        {item.nama_ruang ? ` · ${item.nama_ruang}` : ''}
                                    </span>
                                </button>
                            </li>
                        ))}
                </ul>
            )}
        </div>
    );
}
