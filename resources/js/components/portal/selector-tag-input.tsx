import { Plus, X } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface SelectorTagInputProps {
    selectors: string[];
    onChange: (selectors: string[]) => void;
    presets?: string[];
    placeholder?: string;
    label?: string;
}

export function SelectorTagInput({
    selectors,
    onChange,
    presets = [],
    placeholder = 'Tambah selector CSS / XPath...',
    label,
}: SelectorTagInputProps) {
    const [inputValue, setInputValue] = useState('');

    const handleAdd = (valueToAdd?: string) => {
        const target = (valueToAdd ?? inputValue).trim();
        if (!target) return;
        if (!selectors.includes(target)) {
            onChange([...selectors, target]);
        }
        if (!valueToAdd) {
            setInputValue('');
        }
    };

    const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAdd();
        }
    };

    const handleRemove = (index: number) => {
        onChange(selectors.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-2">
            {label && (
                <label className="text-sm font-medium text-foreground">
                    {label}
                </label>
            )}

            <div className="flex min-h-[38px] flex-wrap gap-1.5 rounded-lg border border-border bg-muted/40 p-2">
                {selectors.length === 0 ? (
                    <span className="self-center text-xs text-muted-foreground italic">
                        Belum ada selector dikonfigurasi
                    </span>
                ) : (
                    selectors.map((sel, idx) => (
                        <Badge
                            key={idx}
                            variant="secondary"
                            className="items-center gap-1.5 border border-border bg-background px-2 py-0.5 font-mono text-xs"
                        >
                            <span>{sel}</span>
                            <button
                                type="button"
                                onClick={() => handleRemove(idx)}
                                className="cursor-pointer text-muted-foreground hover:text-destructive focus:outline-none"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))
                )}
            </div>

            <div className="flex gap-2">
                <Input
                    type="text"
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    className="h-9 font-mono text-xs"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => handleAdd()}
                    className="h-9 shrink-0 px-3"
                >
                    <Plus className="mr-1 size-4" /> Tambah
                </Button>
            </div>

            {presets.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5 pt-1">
                    <span className="text-[11px] text-muted-foreground">
                        Preset cepat:
                    </span>
                    {presets.map((preset) => (
                        <button
                            key={preset}
                            type="button"
                            onClick={() => handleAdd(preset)}
                            disabled={selectors.includes(preset)}
                            className="cursor-pointer rounded border border-border/50 bg-muted px-1.5 py-0.5 font-mono text-[11px] text-foreground hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            + {preset}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
