<script lang="ts">
    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { page, router } from '@inertiajs/svelte';
    import ActivityFeed from '@/components/app/ActivityFeed.svelte';
    import CalendarWidget from '@/components/app/CalendarWidget.svelte';
    import AdminDashboardPanel from '@/components/app/dashboard/AdminDashboardPanel.svelte';
    import ProjectLeaderDashboardPanel from '@/components/app/dashboard/ProjectLeaderDashboardPanel.svelte';
    import WorkspaceLeadDashboardPanel from '@/components/app/dashboard/WorkspaceLeadDashboardPanel.svelte';
    import LateTasksWidget from '@/components/app/LateTasksWidget.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type {
        CalendarMarker,
        CreatableWorkspace,
        DashboardActivity,
        DashboardTask,
    } from '@/types/app-dashboard';

    let {
        demoPersona = null,
        greeting = '',
        todayLabel = '',
        dashboard = { type: 'legacy' },
        lateTasks = [],
        activities = [],
        calendarMarkers = [],
        creatableWorkspaces: _creatableWorkspaces = [],
    }: {
        demoPersona?: string | null;
        greeting?: string;
        todayLabel?: string;
        dashboard?: Record<string, unknown>;
        lateTasks?: DashboardTask[];
        activities?: DashboardActivity[];
        calendarMarkers?: CalendarMarker[];
        creatableWorkspaces?: CreatableWorkspace[];
    } = $props();

    let search = $state('');

    const auth = $derived(page.props.auth);
    const usesMyTasksHome = $derived(
        auth?.user &&
            !auth.user.managed_projects?.length &&
            !auth.user.managed_workspaces?.length &&
            auth.user.role !== 'admin',
    );

    function submitSearch() {
        if (!search.trim()) {
            return;
        }

        if (usesMyTasksHome) {
            router.get('/my-tasks', { q: search.trim() });

            return;
        }

        const managedProject = auth?.user?.managed_projects?.[0];

        if (managedProject) {
            router.get(
                `/workspaces/${managedProject.workspace_id}/projects/${managedProject.id}/tasks`,
                { q: search.trim() },
            );

            return;
        }

        router.get('/dashboard', { q: search.trim() });
    }
</script>

<AppHead title={t('dashboard.nav.dashboard')} />

<div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
    <div
        class="xl:grid xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start xl:gap-6"
    >
        <div>
            <header
                class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--th-text)">
                        {greeting}
                    </h1>
                    <p class="text-sm" style="color: var(--th-text-muted)">
                        {todayLabel}
                    </p>
                </div>

                <form
                    class="flex flex-1 items-center gap-2 rounded-xl border px-4 py-2.5 lg:max-w-xl"
                    style="background: var(--th-surface); border-color: var(--th-border)"
                    onsubmit={(e) => {
                        e.preventDefault();
                        submitSearch();
                    }}
                >
                    <HugeiconsIcon
                        icon={Search01Icon}
                        size={18}
                        style="color: var(--th-text-muted)"
                    />
                    <input
                        type="search"
                        bind:value={search}
                        placeholder={t('dashboard.search_all')}
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                        style="color: var(--th-text)"
                    />
                </form>
            </header>

            {#if demoPersona === 'admin' || dashboard.type === 'admin'}
                <AdminDashboardPanel
                    projects={dashboard.projects as never}
                    leaders={dashboard.leaders as never}
                    workspaceLeaders={dashboard.workspace_leaders as never}
                    managedWorkspaces={dashboard.managed_workspaces as never}
                    workspaces={dashboard.workspaces as never}
                    stats={dashboard.stats as never}
                />
            {:else if demoPersona === 'project_leader' || dashboard.type === 'project_leader'}
                <ProjectLeaderDashboardPanel
                    project={dashboard.project as never}
                    projects={dashboard.projects as never}
                    activeProjectId={dashboard.active_project_id as
                        | number
                        | null}
                    team={dashboard.team as never}
                    reviewQueue={dashboard.review_queue as never}
                    members={dashboard.members as never}
                    openTasks={dashboard.open_tasks as number}
                    taskStoreUrl={dashboard.task_store_url as string}
                    tasksIndexUrl={dashboard.tasks_index_url as string}
                />
            {:else if demoPersona === 'workspace_lead' || dashboard.type === 'workspace_lead'}
                <WorkspaceLeadDashboardPanel
                    workspace={dashboard.workspace as never}
                    manageUrl={dashboard.manage_url as string}
                    projects={dashboard.projects as never}
                    pendingRequests={dashboard.pending_requests as number}
                    stats={dashboard.stats as never}
                />
            {/if}
        </div>

        <aside class="hidden space-y-4 xl:block">
            <CalendarWidget markers={calendarMarkers} />
            <LateTasksWidget tasks={lateTasks} />
            <ActivityFeed {activities} />
        </aside>
    </div>
</div>
