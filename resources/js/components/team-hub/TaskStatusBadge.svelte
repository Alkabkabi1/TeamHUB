<script lang="ts">
    import { statusLabels } from '@/components/team-hub/mock-data';
    import type { TaskStatus } from '@/types/team-hub';

    let {
        status,
    }: {
        status: TaskStatus;
    } = $props();

    const styles: Record<TaskStatus, { bg: string; color: string }> = {
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

<span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    style="background: {styles[status].bg}; color: {styles[status].color}"
>
    {statusLabels[status]}
</span>
