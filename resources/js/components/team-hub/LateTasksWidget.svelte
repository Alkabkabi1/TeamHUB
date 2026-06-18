<script lang="ts">
    let { tasks }: { tasks: { id: number; title: string; daysLate: number }[] } = $props();
</script>

<div class="th-card p-4">
    <h3 class="mb-4 text-sm font-semibold" style="color: var(--th-text)">مهام متأخرة</h3>
    <ul class="space-y-3">
        {#each tasks as task (task.id)}
            <li class="flex items-start justify-between gap-2">
                <span class="text-sm" style="color: var(--th-text)">{task.title}</span>
                <span class="shrink-0 text-xs font-medium" style="color: var(--th-danger)">
                    {task.daysLate === 1 ? 'منذ يوم' : `منذ ${task.daysLate} أيام`}
                </span>
            </li>
        {/each}
    </ul>
</div>
