<script lang="ts">
    /**
     * The shared rounded search input (icon + field) used across the public
     * listing pages (clubs, events, news, resources) and the support FAQ
     * filter. Single source of truth for the search field styling.
     *
     * Pass `name` for server-driven GET forms (the value is submitted via the
     * field name). For live client-side filtering, use `bind:value`.
     */
    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { cn } from '@/lib/utils';

    type Props = {
        name?: string;
        value?: string;
        placeholder?: string;
        ariaLabel?: string;
        type?: 'search' | 'text';
        /** Extra classes merged onto the outer pill (e.g. width overrides). */
        class?: string;
    };

    let {
        name,
        value = $bindable(''),
        placeholder,
        ariaLabel,
        type = 'search',
        class: className,
    }: Props = $props();
</script>

<div
    role="search"
    class={cn(
        'flex min-h-11 flex-1 items-center gap-2 rounded-full border border-black/10 px-4',
        className,
    )}
>
    <input
        {type}
        {name}
        {placeholder}
        aria-label={ariaLabel}
        bind:value
        class="order-2 min-w-0 flex-1 bg-transparent text-start text-sm text-[#5f5f5f] outline-none placeholder:text-[#7e7e7e]"
    />
    <HugeiconsIcon
        strokeWidth={2}
        icon={Search01Icon}
        class="order-1 size-4 shrink-0 text-[#7e7e7e]"
    />
</div>
