<script lang="ts">
    export const layout = () => null;

    import TasksTable from '@/components/team-hub/TasksTable.svelte';
    import TaskStatusBadge from '@/components/team-hub/TaskStatusBadge.svelte';
    import { statusLabels, tasks } from '@/components/team-hub/mock-data';
    import type { TaskStatus } from '@/components/team-hub/mock-data';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';
    import { FilterHorizontalIcon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';

    const statuses: (TaskStatus | 'all')[] = ['all', 'todo', 'in_progress', 'review', 'done'];
    let filter = $state<TaskStatus | 'all'>('all');

    const filtered = $derived(
        filter === 'all' ? tasks : tasks.filter((t) => t.status === filter),
    );
</script>

<TeamHubLayout title="المهام — Team Hub" activePath="/preview/team-hub/tasks">
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
        <header class="mb-6">
            <h1 class="text-xl font-bold" style="color: var(--th-text)">المهام</h1>
            <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
                جميع المهام عبر المشاريع — فلترة حسب الحالة والأولوية
            </p>
        </header>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div
                class="flex max-w-md flex-1 items-center gap-2 rounded-xl border px-4 py-2.5"
                style="background: var(--th-surface); border-color: var(--th-border)"
            >
                <HugeiconsIcon icon={Search01Icon} size={18} style="color: var(--th-text-muted)" />
                <input
                    type="search"
                    placeholder="بحث في المهام..."
                    class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                    style="color: var(--th-text)"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {#each statuses as status (status)}
                    <button
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors
                            {filter === status ? 'border-transparent th-btn-primary text-white' : ''}"
                        style={filter !== status
                            ? 'border-color: var(--th-border); color: var(--th-text-muted)'
                            : undefined}
                        onclick={() => (filter = status)}
                    >
                        {#if status === 'all'}
                            الكل
                        {:else}
                            {statusLabels[status]}
                        {/if}
                    </button>
                {/each}
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs"
                    style="border-color: var(--th-border); color: var(--th-text-muted)"
                >
                    <HugeiconsIcon icon={FilterHorizontalIcon} size={14} />
                    المزيد
                </button>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <span class="text-xs" style="color: var(--th-text-muted)">مرجع الحالات:</span>
            {#each (['todo', 'in_progress', 'review', 'done'] as const) as s (s)}
                <TaskStatusBadge status={s} />
            {/each}
        </div>

        <TasksTable tasks={filtered} />
        <p class="mt-4 text-center text-sm" style="color: var(--th-text-muted)">
            {filtered.length} مهمة
        </p>
    </div>
</TeamHubLayout>
