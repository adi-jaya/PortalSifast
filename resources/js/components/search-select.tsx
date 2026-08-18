import ReactSelect, { type SingleValue, type StylesConfig } from 'react-select';
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
    size?: 'default' | 'sm';
    className?: string;
};

const menuPortalStyles: StylesConfig<SearchSelectOption, false> = {
    menuPortal: (base) => ({ ...base, zIndex: 9999 }),
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
    size = 'default',
    className,
}: Props) {
    const selected = options.find((o) => o.value === value) ?? null;

    return (
        <div className={className}>
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
            filterOption={(option, rawInput) => {
                const q = rawInput.trim().toLowerCase();
                if (!q) {
                    return true;
                }
                const haystack = `${option.label} ${option.data.description ?? ''}`.toLowerCase();

                return haystack.includes(q);
            }}
            menuPortalTarget={typeof document !== 'undefined' ? document.body : null}
            menuPosition="fixed"
            styles={menuPortalStyles}
            className="text-sm"
            classNames={{
                control: () =>
                    cn(
                        size === 'sm' ? '!min-h-8' : '!min-h-10',
                        '!rounded-md !border-input !bg-background !shadow-xs hover:!border-input',
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
        </div>
    );
}
