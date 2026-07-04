<script lang="ts">
    import { t } from '@/lib/i18n.svelte';

    let {
        priority,
        variant = 'pill',
    }: {
        priority: 'low' | 'medium' | 'high' | 'urgent';
        variant?: 'pill' | 'dot';
    } = $props();

    const pillStyles = {
        low: 'bg-slate-100 text-slate-700',
        medium: 'bg-violet-100 text-violet-700',
        high: 'bg-orange-100 text-orange-700',
        urgent: 'bg-rose-100 text-rose-700',
    } as const;

    const dotColors: Record<typeof priority, string> = {
        low: 'bg-yellow-400',
        medium: 'bg-orange-400',
        high: 'bg-red-500',
        urgent: 'bg-red-800',
    };
</script>

{#if variant === 'dot'}
    <span
        class="inline-flex items-center gap-1.5 text-xs"
        style="color: var(--th-text-muted)"
    >
        <span class="size-2 rounded-full {dotColors[priority]}"></span>
        {t(`tasks.priorities.${priority}`)}
    </span>
{:else}
    <span
        class={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${pillStyles[priority]}`}
    >
        {t(`tasks.priorities.${priority}`)}
    </span>
{/if}
