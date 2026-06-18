<script lang="ts">
    import type { Snippet } from 'svelte';

    type ShellTag = 'section' | 'div' | 'article';

    let {
        children,
        class: className = '',
        as = 'section',
        ariaLabel,
    }: {
        children: Snippet;
        class?: string;
        as?: ShellTag;
        ariaLabel?: string;
    } = $props();
</script>

<svelte:element this={as} class={className} aria-label={ariaLabel}>
    {@render children()}
</svelte:element>
