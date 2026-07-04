<script lang="ts">
    import { t } from '@/lib/i18n.svelte';

    let {
        status,
        variant = 'app',
    }: {
        status: 'todo' | 'in_progress' | 'review' | 'done';
        variant?: 'app' | 'hub';
    } = $props();

    const appStyles = {
        todo: 'bg-slate-100 text-slate-700',
        in_progress: 'bg-sky-100 text-sky-700',
        review: 'bg-amber-100 text-amber-700',
        done: 'bg-emerald-100 text-emerald-700',
    } as const;

    const hubStyles: Record<typeof status, { bg: string; color: string }> = {
        todo: {
            bg: 'color-mix(in srgb, var(--th-text-muted) 14%, transparent)',
            color: 'var(--th-text-muted)',
        },
        in_progress: {
            bg: 'color-mix(in srgb, var(--th-info) 14%, transparent)',
            color: 'var(--th-info)',
        },
        review: {
            bg: 'color-mix(in srgb, var(--th-review) 14%, transparent)',
            color: 'var(--th-review)',
        },
        done: {
            bg: 'color-mix(in srgb, var(--th-success) 14%, transparent)',
            color: 'var(--th-success)',
        },
    };
</script>

{#if variant === 'hub'}
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        style="background: {hubStyles[status].bg}; color: {hubStyles[status]
            .color}"
    >
        {t(`tasks.statuses.${status}`)}
    </span>
{:else}
    <span
        class={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${appStyles[status]}`}
    >
        {t(`tasks.statuses.${status}`)}
    </span>
{/if}
