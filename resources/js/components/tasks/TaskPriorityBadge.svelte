<script lang="ts">
    import { t } from '@/lib/i18n.svelte';

    let {
        priority,
    }: {
        priority: 'low' | 'medium' | 'high' | 'urgent';
    } = $props();

    const styles = {
        low: 'bg-slate-100 text-slate-700',
        medium: 'bg-violet-100 text-violet-700',
        high: 'bg-orange-100 text-orange-700',
        urgent: 'bg-rose-100 text-rose-700',
    } as const;
</script>

<span
    class={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${styles[priority]}`}
>
    {t(`tasks.priorities.${priority}`)}
</span>
