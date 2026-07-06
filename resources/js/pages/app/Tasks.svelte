<script lang="ts">
    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { router } from '@inertiajs/svelte';
    import AppPagination from '@/components/app/AppPagination.svelte';
    import TasksTable from '@/components/app/TasksTable.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { DashboardTask, TaskStatus } from '@/types/app-dashboard';

    type Paginator<T> = {
        data: T[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };

    let {
        tasks = {
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: null,
            to: null,
        },
        search = '',
        status = 'all',
        workspaceId = null,
    }: {
        tasks?: Paginator<DashboardTask> | DashboardTask[];
        search?: string;
        status?: TaskStatus | 'all';
        workspaceId?: number | null;
    } = $props();

    const taskItems = $derived(Array.isArray(tasks) ? tasks : tasks.data);
    const pagination = $derived(Array.isArray(tasks) ? null : tasks);

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
            '/tasks',
            {
                q: query.trim() || undefined,
                status: filter === 'all' ? undefined : filter,
                workspace: workspaceId ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    }
</script>

<AppHead title={t('dashboard.nav.tasks')} />

<div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
    <header class="mb-6">
        <h1 class="text-xl font-bold" style="color: var(--th-text)">
            {t('dashboard.nav.tasks')}
        </h1>
        <p class="mt-1 text-sm" style="color: var(--th-text-muted)">
            {t('dashboard.all_tasks')}
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
                placeholder={t('dashboard.search_tasks')}
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
                        {t('app.all')}
                    {:else}
                        {t(`tasks.statuses.${statusKey}`)}
                    {/if}
                </button>
            {/each}
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <span class="text-xs" style="color: var(--th-text-muted)"
            >{t('tasks.status')}:</span
        >
        {#each ['todo', 'in_progress', 'review', 'done'] as const as s (s)}
            <TaskStatusBadge status={s} variant="shell" />
        {/each}
    </div>

    <TasksTable tasks={taskItems} />
    <p class="mt-4 text-center text-sm" style="color: var(--th-text-muted)">
        {t('dashboard.task_count', {
            count: pagination?.total ?? taskItems.length,
        })}
    </p>

    {#if pagination}
        <AppPagination paginator={pagination} />
    {/if}
</div>
