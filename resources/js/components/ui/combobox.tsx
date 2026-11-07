import { Fragment, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface ComboboxDetail {
    label: string;
    value: string;
}

export interface ComboboxOption {
    value: string;
    label: string;
    details?: ComboboxDetail[];
}

interface ComboboxProps {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    options: ComboboxOption[];
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
    /** Extra classes for the trigger button, e.g. a different border color */
    className?: string;
}

export function Combobox({
    id,
    value,
    onChange,
    options,
    placeholder,
    searchPlaceholder,
    emptyText,
    className,
}: ComboboxProps) {
    const [open, setOpen] = useState(false);
    const selected = options.find((o) => o.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'h-10 w-full justify-between border-gray-200 font-normal',
                        className,
                    )}
                >
                    <span className={cn('truncate', !selected && 'text-muted-foreground')}>
                        {selected ? selected.label : placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="p-0"
                align="start"
                style={{ width: 'var(--radix-popover-trigger-width)' }}
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyText}</CommandEmpty>
                        <CommandGroup>
                            {options.map((o) => (
                                <CommandItem
                                    key={o.value}
                                    // details are included so the search matches them too
                                    value={`${o.label} ${
                                        o.details?.map((d) => `${d.label} ${d.value}`).join(' ') ?? ''
                                    } ${o.value}`}
                                    onSelect={() => {
                                        onChange(o.value);
                                        setOpen(false);
                                    }}
                                    className="items-start py-2.5"
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 mt-0.5 h-4 w-4 shrink-0',
                                            value === o.value ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                                        <span className="font-semibold text-ink">{o.label}</span>

                                        {o.details && o.details.length > 0 && (
                                            <div className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-0.5 rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                                                {o.details.map((d, i) => (
                                                    <Fragment key={`${d.label}-${i}`}>
                                                        <span className="text-muted-foreground">
                                                            {d.label}
                                                        </span>
                                                        <span className="font-medium text-gray-800">
                                                            {d.value}
                                                        </span>
                                                    </Fragment>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}