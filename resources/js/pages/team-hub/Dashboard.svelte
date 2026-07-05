<script lang="ts">
    export const layout = () => null;

    import {
        Notification01Icon,
        Search01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import ActivityFeed from '@/components/team-hub/ActivityFeed.svelte';
    import CalendarWidget from '@/components/team-hub/CalendarWidget.svelte';
    import AdminDashboardPanel from '@/components/team-hub/dashboard/AdminDashboardPanel.svelte';
    import ProjectLeaderDashboardPanel from '@/components/team-hub/dashboard/ProjectLeaderDashboardPanel.svelte';
    import StaffDashboardPanel from '@/components/team-hub/dashboard/StaffDashboardPanel.svelte';
    import KpiCard from '@/components/team-hub/KpiCard.svelte';
    import LateTasksWidget from '@/components/team-hub/LateTasksWidget.svelte';
    import ProjectCard from '@/components/team-hub/ProjectCard.svelte';
    import TasksTable from '@/components/team-hub/TasksTable.svelte';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';
    import { t } from '@/lib/i18n.svelte';
    import type {
        CalendarMarker,
        CreatableWorkspace,
        HubActivity,
        HubKpi,
        HubProject,
        HubTask,
        RoleContext,
    } from '@/types/team-hub';

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
        lateTasks?: HubTask[];
        activities?: HubActivity[];
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
                  kpis?: HubKpi[];
                  projects?: HubProject[];
                  tasks?: HubTask[];
                  roleContext?: RoleContext;
              })
            : null,
    );

    function submitSearch() {
        if (!search.trim()) {
            return;
        }

        router.get('/hub/tasks', { q: search.trim() });
    }
</script>

{#snippet rightWidgets()}
    <CalendarWidget markers={calendarMarkers} />
    {#if demoPersona !== 'staff'}
        <LateTasksWidget tasks={lateTasks} />
    {/if}
    <ActivityFeed {activities} />
{/snippet}

<TeamHubLayout
    title="لوحة التحكم — Team Hub"
    activePath="/hub/dashboard"
    showRightPanel
    rightPanel={rightWidgets}
>
    <div class="thin-scrollbar flex-1 overflow-y-auto p-4 lg:p-6">
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
                        placeholder={t('hub.search_all')}
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                        style="color: var(--th-text)"
                    />
                </form>
                <Link
                    href="/notifications"
                    class="relative flex size-10 shrink-0 items-center justify-center rounded-xl border"
                    style="border-color: var(--th-border); background: var(--th-surface)"
                    aria-label={t('hub.nav.notifications')}
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
                activeProjectId={dashboard.active_project_id as number | null}
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
                        {t('hub.your_projects')}
                    </h2>
                    <Link
                        href="/hub/projects"
                        class="text-sm font-medium"
                        style="color: var(--th-primary)"
                    >
                        {t('hub.view_all')}
                    </Link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {#each legacy.projects ?? [] as project (project.id)}
                        <ProjectCard {project} />
                    {/each}
                </div>
            </section>

            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="text-base font-semibold"
                        style="color: var(--th-text)"
                    >
                        {t('hub.today_tasks')}
                    </h2>
                    <Link
                        href="/hub/tasks"
                        class="text-sm font-medium"
                        style="color: var(--th-primary)"
                    >
                        {t('hub.view_all')}
                    </Link>
                </div>
                <TasksTable tasks={legacy.tasks ?? []} />
            </section>
        {/if}
    </div>
</TeamHubLayout>
