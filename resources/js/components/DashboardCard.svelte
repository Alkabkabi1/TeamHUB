<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    /**
     * The standard white, rounded, soft-shadowed surface used across the club
     * management dashboard sections. Single source of truth for the card chrome.
     */
    let {
        class: className = '',
        children,
    }: {
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div
    class={cn(
        'rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]',
        className,
    )}
>
    {@render children?.()}
</div>
