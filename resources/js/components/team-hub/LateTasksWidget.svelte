<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { HubTask } from '@/types/team-hub';

    let { tasks }: { tasks: HubTask[] } = $props();
</script>

<div class="th-card p-4">
    <h3 class="mb-4 text-sm font-semibold" style="color: var(--th-text)">
        مهام متأخرة
    </h3>
    <ul class="space-y-3">
        {#each tasks as task (task.id)}
            <li class="flex items-start justify-between gap-2">
                {#if task.url}
                    <Link
                        href={task.url}
                        class="text-sm hover:underline"
                        style="color: var(--th-text)"
                    >
                        {task.title}
                    </Link>
                {:else}
                    <span class="text-sm" style="color: var(--th-text)"
                        >{task.title}</span
                    >
                {/if}
                <span
                    class="shrink-0 text-xs font-medium"
                    style="color: var(--th-danger)"
                >
                    {task.dueLabel}
                </span>
            </li>
        {:else}
            <li class="text-sm" style="color: var(--th-text-muted)">
                لا توجد مهام متأخرة
            </li>
        {/each}
    </ul>
</div>
