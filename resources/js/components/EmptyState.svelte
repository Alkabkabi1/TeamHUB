<script lang="ts">
    /**
     * Centered "nothing here yet" card used for empty list/grid states.
     * Defaults to the common card style; pass `class` to override the radius,
     * padding or shadow for pages that use a different elevation.
     */
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Props = {
        title?: string;
        description?: string;
        message?: string;
        class?: string;
        children?: Snippet;
    };

    let {
        title,
        description,
        message,
        class: className,
        children,
    }: Props = $props();
</script>

<div
    class={cn(
        'rounded-[20px] bg-white px-5 py-8 text-center text-sm text-[#7e7e7e] shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]',
        className,
    )}
>
    {#if children}
        {@render children()}
    {:else if title || description}
        {#if title}
            <p class="font-medium text-[#5f5f5f]">{title}</p>
        {/if}
        {#if description}
            <p class:mt-2={!!title}>{description}</p>
        {/if}
    {:else}
        <p>{message}</p>
    {/if}
</div>
