import AsyncSelect from 'react-select/async';
import type { SingleValue, StylesConfig } from 'react-select';
import { cn } from '@/lib/utils';

export type AspakOption = {
    value: string;
    label: string;
    id: number;
    id_alat_aspak: string;
    nama_alat: string;
    kode: string | null;
    wajib_kalibrasi: boolean;
    durasi_kalibrasi_hari: number | null;
};

const menuPortalStyles: StylesConfig<AspakOption, false> = {
    menuPortal: (base) => ({ ...base, zIndex: 9999 }),
};

type Props = {
    value: AspakOption | null;
    onChange: (option: AspakOption | null) => void;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
    inputId?: string;
    className?: string;
};

async function fetchAspakLeaves(inputValue: string): Promise<AspakOption[]> {
    const params = new URLSearchParams({ q: inputValue.trim() });
    const response = await fetch(`/aset/master/aspak/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as Array<{
        id: number;
        id_alat_aspak: string;
        nama_alat: string;
        kode: string | null;
        wajib_kalibrasi: boolean;
        durasi_kalibrasi_hari: number | null;
        label: string;
    }>;

    return data.map((item) => ({
        value: String(item.id),
        label: item.label,
        id: item.id,
        id_alat_aspak: item.id_alat_aspak,
        nama_alat: item.nama_alat,
        kode: item.kode,
        wajib_kalibrasi: item.wajib_kalibrasi,
        durasi_kalibrasi_hari: item.durasi_kalibrasi_hari,
    }));
}

export function AspakLeafSearchSelect({
    value,
    onChange,
    placeholder = 'Cari katalog ASPAK (kode / nama)...',
    disabled = false,
    hasError = false,
    inputId = 'aset_aspak_alat_id',
    className,
}: Props) {
    return (
        <AsyncSelect<AspakOption, false>
            inputId={inputId}
            cacheOptions
            defaultOptions
            loadOptions={fetchAspakLeaves}
            value={value}
            isClearable
            isDisabled={disabled}
            placeholder={placeholder}
            noOptionsMessage={({ inputValue }) =>
                inputValue ? 'Item ASPAK tidak ditemukan' : 'Ketik nama atau kode ASPAK'
            }
            loadingMessage={() => 'Mencari...'}
            onChange={(option: SingleValue<AspakOption>) => onChange(option ?? null)}
            formatOptionLabel={(option) => (
                <div className="flex flex-col py-0.5">
                    <span className="text-sm font-medium">{option.nama_alat}</span>
                    <span className="text-xs text-muted-foreground">
                        {[option.kode, option.id_alat_aspak].filter(Boolean).join(' · ')}
                        {option.wajib_kalibrasi ? ' · wajib kalibrasi' : ''}
                    </span>
                </div>
            )}
            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
            menuPosition="fixed"
            styles={menuPortalStyles}
            className={cn('text-sm', className)}
            classNames={{
                control: () =>
                    cn(
                        '!min-h-10 !rounded-md !border-input !bg-background !shadow-xs hover:!border-input',
                        hasError && '!border-destructive',
                    ),
                menu: () => '!z-[9999] !rounded-md !border !bg-popover !text-popover-foreground !shadow-md',
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
