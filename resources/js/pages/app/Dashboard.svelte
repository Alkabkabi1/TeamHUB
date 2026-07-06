<script lang="ts">
    import {
        Notification01Icon,
        Search01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import ActivityFeed from '@/components/app/ActivityFeed.svelte';
    import CalendarWidget from '@/components/app/CalendarWidget.svelte';
    import AdminDashboardPanel from '@/components/app/dashboard/AdminDashboardPanel.svelte';
    import ProjectLeaderDashboardPanel from '@/components/app/dashboard/ProjectLeaderDashboardPanel.svelte';
    import StaffDashboardPanel from '@/components/app/dashboard/StaffDashboardPanel.svelte';
    import DashboardProjectCard from '@/components/app/DashboardProjectCard.svelte';
    import KpiCard from '@/components/app/KpiCard.svelte';
    import LateTasksWidget from '@/components/app/LateTasksWidget.svelte';
    import TasksTable from '@/components/app/TasksTable.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type {
        CalendarMarker,
        CreatableWorkspace,
        DashboardActivity,
        DashboardKpi,
        DashboardProject,
        DashboardTask,
        RoleContext,
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

    const unreadCount = $derived(
        Number(page.props.auth?.user?.unread_notifications_count ?? 0),
    );

    const legacy = $derived(
        dashboard.type === 'legacy'
            ? (dashboard as {
                  kpis?: DashboardKpi[];
                  projects?: DashboardProject[];
                  tasks?: DashboardTask[];
                  roleContext?: RoleContext;
              })
            : null,
    );

    function submitSearch() {
        if (!search.trim()) {
            return;
        }

        router.get('/tasks', { q: search.trim() });
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

                <div class="flex flex-1 items-center gap-3 lg:max-w-xl">
                    <form
                        class="flex flex-1 items-center gap-2 rounded-xl border px-4 py-2.5"
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
                    <Link
                        href="/notifications"
                        class="relative flex size-10 shrink-0 items-center justify-center rounded-xl border"
                        style="border-color: var(--th-border); background: var(--th-surface)"
                        aria-label={t('dashboard.nav.notifications')}
                    >
                        <HugeiconsIcon
                            icon={Notification01Icon}
                            size={20}
                            style="color: var(--th-text-muted)"
                        />
                        {#if unreadCount > 0}
                            <span
                                class="absolute -top-1 -start-1 flex size-4 min-w-4 items-center justify-center rounded-full px-0.5 text-[9px] font-bold text-white"
                                style="background: var(--th-danger)"
                            >
                                {unreadCount}
                            </span>
                        {/if}
                    </Link>
                </div>
            </header>

            {#if demoPersona === 'admin'}
                <AdminDashboardPanel
                    projects={dashboard.projects as never}
                    leaders={dashboard.leaders as never}
                    workspaces={dashboard.workspaces as never}
                    stats={dashboard.stats as never}
                />
            {:else if demoPersona === 'project_leader'}
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
                />
            {:else if demoPersona === 'staff'}
                <StaffDashboardPanel
                    tasks={dashboard.tasks as never}
                    stats={dashboard.stats as never}
                />
            {:else if legacy}
                <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {#each legacy.kpis ?? [] as kpi (kpi.id)}
                        <KpiCard {kpi} />
                    {/each}
                </section>

                <section class="mb-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-base font-semibold"
                            style="color: var(--th-text)"
                        >
                            {t('dashboard.your_projects')}
                        </h2>
                        <Link
                            href="/projects"
                            class="text-sm font-medium"
                            style="color: var(--th-primary)"
                        >
                            {t('dashboard.view_all')}
                        </Link>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {#each legacy.projects ?? [] as project (project.id)}
                            <DashboardProjectCard {project} />
                        {/each}
                    </div>
                </section>

                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-base font-semibold"
                            style="color: var(--th-text)"
                        >
                            {t('dashboard.today_tasks')}
                        </h2>
                        <Link
                            href="/tasks"
                            class="text-sm font-medium"
                            style="color: var(--th-primary)"
                        >
                            {t('dashboard.view_all')}
                        </Link>
                    </div>
                    <TasksTable tasks={legacy.tasks ?? []} />
                </section>
            {/if}
        </div>

        <aside class="hidden space-y-4 xl:block">
            <CalendarWidget markers={calendarMarkers} />
            {#if demoPersona !== 'staff'}
                <LateTasksWidget tasks={lateTasks} />
            {/if}
            <ActivityFeed {activities} />
        </aside>
    </div>
</div>
