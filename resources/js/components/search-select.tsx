import ReactSelect, { type SingleValue } from 'react-select';
import { cn } from '@/lib/utils';

export type SearchSelectOption = {
    value: string;
    label: string;
    description?: string;
};

type Props = {
    options: SearchSelectOption[];
    value: string | null | undefined;
    onChange: (value: string) => void;
    placeholder?: string;
    isClearable?: boolean;
    disabled?: boolean;
    hasError?: boolean;
    inputId?: string;
    noOptionsMessage?: string;
};

export function SearchSelect({
    options,
    value,
    onChange,
    placeholder = 'Ketik untuk mencari...',
    isClearable = true,
    disabled = false,
    hasError = false,
    inputId,
    noOptionsMessage = 'Tidak ditemukan',
}: Props) {
    const selected = options.find((o) => o.value === value) ?? null;

    return (
        <ReactSelect<SearchSelectOption, false>
            inputId={inputId}
            options={options}
            value={selected}
            isClearable={isClearable}
            isDisabled={disabled}
            isSearchable
            placeholder={placeholder}
            noOptionsMessage={() => noOptionsMessage}
            onChange={(option: SingleValue<SearchSelectOption>) => onChange(option?.value ?? '')}
            formatOptionLabel={(option) =>
                option.description ? (
                    <div className="flex flex-col py-0.5">
                        <span className="text-sm font-medium">{option.label}</span>
                        <span className="text-xs text-muted-foreground">{option.description}</span>
                    </div>
                ) : (
                    <span className="text-sm">{option.label}</span>
                )
            }
            className="text-sm"
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
