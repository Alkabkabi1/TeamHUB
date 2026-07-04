<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import * as Select from '@/components/ui/select';
    import { cn } from '@/lib/utils';

    type Option = { value: string; label: string };
    type Direction = 'rtl' | 'ltr';

    // The dropdown content is portaled to <body>, outside the dir="rtl"
    // wrapper, so logical CSS props (start/end, ps/pe) would otherwise resolve
    // as LTR. Forward the page direction onto the content to fix that.
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );

    let {
        value = $bindable(''),
        options,
        name,
        id,
        disabled,
        allLabel,
        placeholder,
        ariaLabel,
        class: className,
        onValueChange,
    }: {
        value?: string;
        options: Option[];
        name?: string;
        id?: string;
        disabled?: boolean;
        /** When provided, prepends a selectable "all" entry with an empty value. */
        allLabel?: string;
        placeholder?: string;
        ariaLabel?: string;
        class?: string;
        onValueChange?: (value: string) => void;
    } = $props();

    const resolvedOptions = $derived(
        allLabel != null
            ? [{ value: '', label: allLabel }, ...options]
            : options,
    );

    const selectedLabel = $derived(
        resolvedOptions.find((option) => option.value === value)?.label,
    );
</script>

<Select.Root type="single" {name} {disabled} bind:value {onValueChange}>
    <Select.Trigger
        {id}
        aria-label={ariaLabel}
        class={cn(
            'min-h-11 w-full justify-between rounded-full border-black/10 bg-white px-4 text-start text-sm text-[#5f5f5f] focus-visible:ring-brand/30 data-[size=default]:h-11',
            className,
        )}
    >
        <span class={cn(!selectedLabel && 'text-[#7e7e7e]')}>
            {selectedLabel ?? placeholder ?? ''}
        </span>
    </Select.Trigger>
    <Select.Content dir={direction}>
        {#each resolvedOptions as option (option.value)}
            <Select.Item value={option.value} label={option.label} />
        {/each}
    </Select.Content>
</Select.Root>
