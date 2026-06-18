<script lang="ts">
    /**
     * Centered "nothing here yet" card used for empty list/grid states.
     * Defaults to the common card style; pass `class` to override the radius,
     * padding or shadow for pages that use a different elevation.
     */
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Props = {
        message?: string;
        class?: string;
        children?: Snippet;
    };

    let { message, class: className, children }: Props = $props();
</script>

<p
    class={cn(
        'rounded-[20px] bg-white px-5 py-8 text-center text-sm text-[#7e7e7e] shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]',
        className,
    )}
>
    {#if children}{@render children()}{:else}{message}{/if}
</p>
