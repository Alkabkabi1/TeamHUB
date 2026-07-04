<script lang="ts">
    import { priorityLabels } from '@/components/team-hub/mock-data';
    import type { TaskPriority } from '@/types/team-hub';

    let {
        priority,
    }: {
        priority: TaskPriority;
    } = $props();

    const dotColors: Record<TaskPriority, string> = {
        low: 'bg-yellow-400',
        medium: 'bg-orange-400',
        high: 'bg-red-500',
        urgent: 'bg-red-800',
    };
</script>

<span
    class="inline-flex items-center gap-1.5 text-xs"
    style="color: var(--th-text-muted)"
>
    <span class="size-2 rounded-full {dotColors[priority]}"></span>
    {priorityLabels[priority]}
</span>
