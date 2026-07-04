<script lang="ts">
    export const layout = () => null;

    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { router } from '@inertiajs/svelte';
    import { statusLabels } from '@/components/team-hub/mock-data';
    import TasksTable from '@/components/team-hub/TasksTable.svelte';
    import TaskStatusBadge from '@/components/team-hub/TaskStatusBadge.svelte';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { HubTask, TaskStatus } from '@/types/team-hub';

    let {
        tasks = [],
        search = '',
        status = 'all',
        workspaceId = null,
    }: {
        tasks?: HubTask[];
        search?: string;
        status?: TaskStatus | 'all';
        workspaceId?: number | null;
    } = $props();

    const statuses: (TaskStatus | 'all')[] = [
        'all',
        'todo',
        'in_progress',
        'review',
        'done',
    ];

    let query = $state(search);
    let filter = $state<TaskStatus | 'all'>(status);

    function applyFilters() {
        router.get(
            '/hub/tasks',
            {
                q: query.trim() || undefined,
                status: filter === 'all' ? undefined : filter,
                workspace: workspaceId ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    }
</script>

<TeamHubLayout title="المهام — Team Hub" activePath="/hub/tasks">
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
        <header class="mb-6">
            <h1 class="text-xl font-bold" style="color: var(--th-text)">
                {t('hub.nav.tasks')}
            </h1>
            <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
                {t('hub.all_tasks')}
            </p>
        </header>

        <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <form
                class="flex max-w-md flex-1 items-center gap-2 rounded-xl border px-4 py-2.5"
                style="background: var(--th-surface); border-color: var(--th-border)"
                onsubmit={(e) => {
                    e.preventDefault();
                    applyFilters();
                }}
            >
                <HugeiconsIcon
                    icon={Search01Icon}
                    size={18}
                    style="color: var(--th-text-muted)"
                />
                <input
                    type="search"
                    bind:value={query}
                    placeholder={t('hub.search_tasks')}
                    class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                    style="color: var(--th-text)"
                />
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {#each statuses as statusKey (statusKey)}
                    <button
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors
                            {filter === statusKey
                            ? 'border-transparent th-btn-primary text-white'
                            : ''}"
                        style={filter !== statusKey
                            ? 'border-color: var(--th-border); color: var(--th-text-muted)'
                            : undefined}
                        onclick={() => {
                            filter = statusKey;
                            applyFilters();
                        }}
                    >
                        {#if statusKey === 'all'}
                            الكل
                        {:else}
                            {statusLabels[statusKey]}
                        {/if}
                    </button>
                {/each}
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <span class="text-xs" style="color: var(--th-text-muted)"
                >مرجع الحالات:</span
            >
            {#each ['todo', 'in_progress', 'review', 'done'] as const as s (s)}
                <TaskStatusBadge status={s} />
            {/each}
        </div>

        <TasksTable {tasks} />
        <p class="mt-4 text-center text-sm" style="color: var(--th-text-muted)">
            {t('hub.task_count', { count: tasks.length })}
        </p>
    </div>
</TeamHubLayout>
