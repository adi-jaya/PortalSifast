import CreatableSelect from 'react-select/creatable';
import type { SingleValue } from 'react-select';
import { cn } from '@/lib/utils';
import type { SearchSelectOption } from '@/components/search-select';

type Props = {
    options: SearchSelectOption[];
    value: string | null | undefined;
    onChange: (value: string) => void;
    onCreateOption: (inputValue: string) => Promise<SearchSelectOption | null>;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
    inputId?: string;
    isCreating?: boolean;
};

export function CreatableSearchSelect({
    options,
    value,
    onChange,
    onCreateOption,
    placeholder = 'Ketik untuk mencari atau buat baru...',
    disabled = false,
    hasError = false,
    inputId,
    isCreating = false,
}: Props) {
    const selected = options.find((o) => o.value === value) ?? null;

    return (
        <CreatableSelect<SearchSelectOption, false>
            inputId={inputId}
            options={options}
            value={selected}
            isClearable
            isDisabled={disabled || isCreating}
            isLoading={isCreating}
            isSearchable
            placeholder={placeholder}
            noOptionsMessage={() => 'Ketik nama baru lalu Enter'}
            formatCreateLabel={(input) => `Tambah "${input}"`}
            onChange={(option: SingleValue<SearchSelectOption>) => onChange(option?.value ?? '')}
            onCreateOption={async (inputValue) => {
                const created = await onCreateOption(inputValue.trim());
                if (created) {
                    onChange(created.value);
                }
            }}
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
