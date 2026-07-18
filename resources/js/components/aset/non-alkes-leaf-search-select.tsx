import AsyncSelect from 'react-select/async';
import type { SingleValue } from 'react-select';
import { cn } from '@/lib/utils';

export type NonAlkesOption = {
    value: string;
    label: string;
    id: number;
    id_alat: string;
    nama_alat: string;
    kode: string | null;
};

type Props = {
    value: NonAlkesOption | null;
    onChange: (option: NonAlkesOption | null) => void;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
    inputId?: string;
    className?: string;
};

async function fetchNonAlkesLeaves(inputValue: string): Promise<NonAlkesOption[]> {
    const params = new URLSearchParams({ q: inputValue.trim() });
    const response = await fetch(`/aset/master/non-alkes/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as Array<{
        id: number;
        id_alat: string;
        nama_alat: string;
        kode: string | null;
        label: string;
    }>;

    return data.map((item) => ({
        value: String(item.id),
        label: item.label,
        id: item.id,
        id_alat: item.id_alat,
        nama_alat: item.nama_alat,
        kode: item.kode,
    }));
}

export function NonAlkesLeafSearchSelect({
    value,
    onChange,
    placeholder = 'Cari katalog non-alkes (kode / nama)...',
    disabled = false,
    hasError = false,
    inputId = 'aset_non_alkes_id',
    className,
}: Props) {
    return (
        <AsyncSelect<NonAlkesOption, false>
            inputId={inputId}
            cacheOptions
            defaultOptions
            loadOptions={fetchNonAlkesLeaves}
            value={value}
            isClearable
            isDisabled={disabled}
            placeholder={placeholder}
            noOptionsMessage={({ inputValue }) =>
                inputValue ? 'Item katalog tidak ditemukan' : 'Ketik nama atau kode, mis. Laptop / 10.02.002'
            }
            loadingMessage={() => 'Mencari...'}
            onChange={(option: SingleValue<NonAlkesOption>) => onChange(option ?? null)}
            formatOptionLabel={(option) => (
                <div className="flex flex-col py-0.5">
                    <span className="text-sm font-medium">{option.nama_alat}</span>
                    <span className="text-xs text-muted-foreground">
                        {[option.kode, option.id_alat].filter(Boolean).join(' · ')}
                    </span>
                </div>
            )}
            className={cn('text-sm', className)}
            classNames={{
                control: () =>
                    cn(
                        '!min-h-10 !rounded-md !border-input !bg-background !shadow-xs hover:!border-input',
                        hasError && '!border-destructive',
                    ),
                menu: () => '!z-50 !rounded-md !border !bg-popover !text-popover-foreground !shadow-md',
                option: ({ isFocused, isSelected }) =>
                    cn(
                        '!cursor-pointer !text-sm',
                        isSelected && '!bg-primary !text-primary-foreground',
                        isFocused && !isSelected && '!bg-accent !text-accent-foreground',
                    ),
                placeholder: () => '!text-muted-foreground',
                singleValue: () => '!text-foreground',
                input: () => '!text-foreground',
            }}
        />
    );
}
