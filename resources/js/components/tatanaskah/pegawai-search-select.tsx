import AsyncSelect from 'react-select/async';
import type { SingleValue } from 'react-select';
import { cn } from '@/lib/utils';

export type PegawaiOption = {
    value: string;
    label: string;
    nik: string;
    nama: string;
    jbtn: string | null;
    departemen: string | null;
};

type Props = {
    value: PegawaiOption | null;
    onChange: (option: PegawaiOption | null) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    inputId?: string;
};

async function fetchPegawaiOptions(inputValue: string): Promise<PegawaiOption[]> {
    const params = new URLSearchParams({ q: inputValue.trim() });
    const response = await fetch(`/tatanaskah/pegawai/search?${params.toString()}`);

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as Array<{
        nik: string;
        nama: string;
        jbtn: string | null;
        departemen: string | null;
        label: string;
    }>;

    return data.map((item) => ({
        value: item.nik,
        label: item.label,
        nik: item.nik,
        nama: item.nama,
        jbtn: item.jbtn,
        departemen: item.departemen,
    }));
}

export function PegawaiSearchSelect({
    value,
    onChange,
    placeholder = 'Cari nama pegawai...',
    disabled = false,
    className,
    inputId = 'penandatangan_nik',
}: Props) {
    return (
        <AsyncSelect<PegawaiOption, false>
            inputId={inputId}
            cacheOptions
            defaultOptions
            loadOptions={fetchPegawaiOptions}
            value={value}
            isClearable
            isDisabled={disabled}
            placeholder={placeholder}
            noOptionsMessage={({ inputValue }) =>
                inputValue ? 'Pegawai tidak ditemukan' : 'Ketik untuk mencari pegawai'
            }
            loadingMessage={() => 'Mencari...'}
            onChange={(option: SingleValue<PegawaiOption>) => onChange(option ?? null)}
            formatOptionLabel={(option) => (
                <div className="flex flex-col py-0.5">
                    <span className="text-sm font-medium">{option.nama}</span>
                    <span className="text-xs text-muted-foreground">
                        {[option.jbtn, option.departemen, option.nik].filter(Boolean).join(' · ')}
                    </span>
                </div>
            )}
            className={cn('text-sm', className)}
            classNames={{
                control: () =>
                    '!min-h-10 !rounded-md !border-input !bg-background !shadow-xs hover:!border-input',
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
