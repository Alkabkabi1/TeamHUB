<script lang="ts">
    import { t } from '@/lib/i18n.svelte';

    let {
        status,
    }: {
        status: 'todo' | 'in_progress' | 'review' | 'done';
    } = $props();

    const styles = {
        todo: 'bg-slate-100 text-slate-700',
        in_progress: 'bg-sky-100 text-sky-700',
        review: 'bg-amber-100 text-amber-700',
        done: 'bg-emerald-100 text-emerald-700',
    } as const;
</script>

<span
    class={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${styles[status]}`}
>
    {t(`tasks.statuses.${status}`)}
</span>
