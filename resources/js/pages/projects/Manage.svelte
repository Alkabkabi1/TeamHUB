<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        Clock05Icon,
        PlusSignIcon,
        TaskDaily01Icon,
        UserGroupIcon,
    } from '@hugeicons/core-free-icons';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import {
        search as memberSearch,
        store as memberStore,
        updateRoles as memberUpdateRoles,
        destroy as memberDestroy,
    } from '@/actions/App/Http/Controllers/ProjectMemberController';
    import {
        approve as requestApprove,
        reject as requestReject,
    } from '@/actions/App/Http/Controllers/ProjectMembershipController';
    import { members as reportMembers } from '@/actions/App/Http/Controllers/ProjectReportController';
    import {
        create as newsCreate,
        edit as newsEdit,
        destroy as postDestroy,
    } from '@/actions/App/Http/Controllers/ProjectUpdateController';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import ReportCard from '@/components/ReportCard.svelte';
    import SectionHeading from '@/components/SectionHeading.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type { WorkspaceRef } from '@/types';

    type Stat = {
        label: string;
        value: string;
        sub: string;
        icon: IconSvgElement;
    };

    type RoleOption = { value: string; label: string; isManager: boolean };

    type Member = {
        membershipId: number;
        userId: number;
        name: string;
        email: string;
        joinDate: string;
        roles: string[];
        isManager: boolean;
        status: string;
    };

    type PendingRequest = {
        id: number;
        name: string;
        details: string;
        time: string | null;
    };

    type PostItem = { id: number; title: string; published_at: string | null };

    type ActivityItem = {
        id: number;
        message: string;
        created_at: string | null;
        task_title: string;
        task_url: string;
    };

    type TaskStats = {
        todo: number;
        in_progress: number;
        review: number;
        done: number;
        overdue: number;
    };

    type DashboardStats = {
        membersCount: number;
        pendingApplicationsCount: number;
    };

    type ReportLocale = 'ar' | 'en';

    type Props = {
        workspace: WorkspaceRef & { logo_url?: string | null };
        project: {
            id: number;
            name: string;
            logo_url: string | null;
            status: string;
        };
        capabilities?: string[];
        canManageRoles?: boolean;
        roleOptions?: RoleOption[];
        stats?: DashboardStats;
        taskStats?: TaskStats;
        overviewMembers?: Member[];
        recentUpdates?: PostItem[];
        recentActivities?: ActivityItem[];
        members?: Member[];
        pendingApplications?: PendingRequest[];
        posts?: PostItem[];
    };

    let {
        workspace,
        project,
        capabilities = [],
        canManageRoles = false,
        roleOptions = [],
        stats = {
            membersCount: 0,
            pendingApplicationsCount: 0,
        },
        taskStats = {
            todo: 0,
            in_progress: 0,
            review: 0,
            done: 0,
            overdue: 0,
        },
        overviewMembers = [],
        recentUpdates = [],
        recentActivities = [],
        members = [],
        pendingApplications = [],
        posts = [],
    }: Props = $props();

    const CAP = {
        project: 'manage-project',
        news: 'manage-project-updates',
        members: 'manage-project-members',
        reports: 'view-project-reports',
    } as const;

    const ids = $derived<[number, number]>([workspace.id, project.id]);

    const openTasksCount = $derived(
        taskStats.todo + taskStats.in_progress + taskStats.review,
    );

    function can(capability: string): boolean {
        return capabilities.includes(capability);
    }

    let reportLocale = $state<ReportLocale>(
        page.props.locale === 'en' ? 'en' : 'ar',
    );

    const reportLocaleOptions = $derived([
        { value: 'ar', label: t('app.locale_ar') },
        { value: 'en', label: t('app.locale_en') },
    ]);

    const membersReportUrl = $derived(
        reportMembers.url(ids, { query: { locale: reportLocale } }),
    );

    const statCards: Stat[] = $derived([
        {
            label: t('project.dashboard.members'),
            value: formatNumber(stats.membersCount),
            sub: t('app.members'),
            icon: UserGroupIcon,
        },
        {
            label: t('project.dashboard.pending_requests'),
            value: formatNumber(stats.pendingApplicationsCount),
            sub: t('dashboard.pending_review'),
            icon: Clock05Icon,
        },
        {
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(openTasksCount),
            sub: t('dashboard.in_progress_count'),
            icon: TaskDaily01Icon,
        },
        {
            label: t('tasks.sections.done'),
            value: formatNumber(taskStats.done),
            sub: `${formatNumber(taskStats.overdue)} ${t('tasks.sections.overdue')}`,
            icon: CheckmarkCircle01Icon,
        },
    ]);

    function dateLabel(iso: string | null): string {
        if (!iso) {
            return '';
        }

        return new Date(iso).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    function approveRequest(id: number): void {
        router.post(
            requestApprove([workspace.id, project.id, id]).url,
            {},
            { preserveScroll: true },
        );
    }

    function rejectRequest(id: number): void {
        router.post(
            requestReject([workspace.id, project.id, id]).url,
            {},
            { preserveScroll: true },
        );
    }

    let term = $state('');
    let results = $state<{ id: number; name: string; email: string }[]>([]);
    let searching = $state(false);

    async function runSearch(): Promise<void> {
        if (term.trim().length < 2) {
            results = [];

            return;
        }

        searching = true;

        try {
            const url = `${memberSearch(ids).url}?q=${encodeURIComponent(term.trim())}`;
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            results = data.users ?? [];
        } finally {
            searching = false;
        }
    }

    function addMember(userId: number): void {
        router.post(
            memberStore(ids).url,
            { user_id: userId, roles: [] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    term = '';
                    results = [];
                },
            },
        );
    }

    let editingId = $state<number | null>(null);
    let draftRoles = $state<string[]>([]);

    function startEditRoles(member: Member): void {
        editingId = member.membershipId;
        draftRoles = [...member.roles];
    }

    function toggleRole(value: string): void {
        draftRoles = draftRoles.includes(value)
            ? draftRoles.filter((r) => r !== value)
            : [...draftRoles, value];
    }

    function saveRoles(membershipId: number): void {
        router.put(
            memberUpdateRoles([workspace.id, project.id, membershipId]).url,
            { roles: draftRoles },
            { preserveScroll: true, onSuccess: () => (editingId = null) },
        );
    }

    function removeMember(membershipId: number): void {
        router.delete(
            memberDestroy([workspace.id, project.id, membershipId]).url,
            { preserveScroll: true },
        );
    }

    function deletePost(id: number): void {
        router.delete(
            postDestroy({
                workspace: workspace.id,
                project: project.id,
                post: id,
            }).url,
            { preserveScroll: true },
        );
    }
</script>

<AppHead title={`${project.name} — ${t('project.manage')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <ProjectManageShell active="overview" {workspace} {project} />

        <HeroBanner
            ariaLabel={project.name}
            title={workspace.name}
            subtitle={project.name}
            foregroundLogo={project.logo_url ?? workspace.logo_url ?? undefined}
            backgroundLogo={project.logo_url ?? workspace.logo_url ?? undefined}
        />

        <section
            aria-label={t('dashboard.overview')}
            class="flex flex-col gap-5"
        >
            <div class="flex items-end justify-start">
                <h2 class="text-lg text-[#5f5f5f] sm:text-xl">
                    {t('dashboard.overview')}
                </h2>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {#each statCards as stat (stat.label)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                        sub={stat.sub}
                    />
                {/each}
            </div>
            <DashboardCard
                class="flex flex-wrap items-center justify-between gap-4"
            >
                <div class="flex flex-col gap-1 text-start">
                    <h3 class="text-[14px] text-black">{t('tasks.title')}</h3>
                    <p class="text-[12px] text-[#7e7e7e]">
                        {t('tasks.subtitle')}
                    </p>
                </div>
                <Link
                    href={`/workspaces/${workspace.id}/projects/${project.id}/tasks`}
                    class="rounded-full bg-brand px-5 py-2 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('tasks.title')}
                </Link>
            </DashboardCard>
        </section>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <DashboardCard class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-start">
                        <h2 class="text-lg font-medium text-black">
                            {t('app.project')}
                        </h2>
                        <p class="text-sm text-[#7e7e7e]">
                            {openTasksCount}
                            {t('tasks.title')} • {taskStats.overdue}
                            {t('tasks.sections.overdue')}
                        </p>
                    </div>
                    <Link
                        href={`/workspaces/${workspace.id}/projects/${project.id}/tasks`}
                        class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        {t('app.show_more')}
                    </Link>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-[14px] bg-black/5 p-4 text-start">
                        <p class="text-xs text-[#7e7e7e]">Todo</p>
                        <p class="mt-1 text-xl font-semibold text-black">
                            {taskStats.todo}
                        </p>
                    </div>
                    <div class="rounded-[14px] bg-black/5 p-4 text-start">
                        <p class="text-xs text-[#7e7e7e]">In progress</p>
                        <p class="mt-1 text-xl font-semibold text-black">
                            {taskStats.in_progress}
                        </p>
                    </div>
                    <div class="rounded-[14px] bg-black/5 p-4 text-start">
                        <p class="text-xs text-[#7e7e7e]">Review</p>
                        <p class="mt-1 text-xl font-semibold text-black">
                            {taskStats.review}
                        </p>
                    </div>
                    <div class="rounded-[14px] bg-black/5 p-4 text-start">
                        <p class="text-xs text-[#7e7e7e]">Done</p>
                        <p class="mt-1 text-xl font-semibold text-black">
                            {taskStats.done}
                        </p>
                    </div>
                    <div class="rounded-[14px] bg-rose-50 p-4 text-start">
                        <p class="text-xs text-rose-700">Overdue</p>
                        <p class="mt-1 text-xl font-semibold text-rose-700">
                            {taskStats.overdue}
                        </p>
                    </div>
                </div>
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <div class="text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('app.updates')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">{project.name}</p>
                </div>

                {#if recentUpdates.length === 0}
                    <p class="text-sm text-[#7e7e7e]">{t('news.empty')}</p>
                {:else}
                    <div class="space-y-3">
                        {#each recentUpdates as update (update.id)}
                            <Link
                                href={`/workspaces/${workspace.id}/projects/${project.id}/updates`}
                                class="block rounded-[14px] border border-black/10 p-3 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <p class="text-sm font-medium text-black">
                                    {update.title}
                                </p>
                                <p class="mt-1 text-xs text-[#9a9a9a]">
                                    {dateLabel(update.published_at)}
                                </p>
                            </Link>
                        {/each}
                    </div>
                {/if}

                <div class="border-t border-black/10 pt-4">
                    <p class="mb-3 text-start text-sm font-medium text-black">
                        {t('project.dashboard.recent_activity')}
                    </p>
                    {#if recentActivities.length === 0}
                        <p class="text-sm text-[#7e7e7e]">
                            {t('project.dashboard.no_recent_activity')}
                        </p>
                    {:else}
                        <div class="space-y-3">
                            {#each recentActivities as activity (activity.id)}
                                <Link
                                    href={activity.task_url}
                                    class="block rounded-[14px] border border-black/10 p-3 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                                >
                                    <p class="text-sm font-medium text-black">
                                        {activity.task_title}
                                    </p>
                                    <p class="mt-1 text-sm text-[#5f5f5f]">
                                        {activity.message}
                                    </p>
                                    <p class="mt-1 text-xs text-[#9a9a9a]">
                                        {dateLabel(activity.created_at)}
                                    </p>
                                </Link>
                            {/each}
                        </div>
                    {/if}
                </div>

                {#if overviewMembers.length > 0}
                    <div class="border-t border-black/10 pt-4">
                        <p
                            class="mb-3 text-start text-sm font-medium text-black"
                        >
                            {t('project.dashboard.members')}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            {#each overviewMembers as member (member.membershipId)}
                                <span
                                    class="rounded-full bg-black/5 px-3 py-1 text-xs text-[#5f5f5f]"
                                >
                                    {member.name}
                                </span>
                            {/each}
                        </div>
                    </div>
                {/if}
            </DashboardCard>
        </section>

        {#if can(CAP.members)}
            <section
                aria-label={t('project.dashboard.members_management')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('project.dashboard.members_management')}
                />

                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('project.dashboard.pending_requests')}
                    </h3>
                    {#if pendingApplications.length === 0}
                        <EmptyState
                            message={t('project.dashboard.no_requests')}
                        />
                    {:else}
                        <ul class="flex flex-col gap-3">
                            {#each pendingApplications as req (req.id)}
                                <li
                                    class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-black/5 px-4 py-3"
                                >
                                    <div
                                        class="flex flex-col items-start text-start"
                                    >
                                        <span class="text-[13px] text-black"
                                            >{req.name}</span
                                        >
                                        <span class="text-[12px] text-[#7e7e7e]"
                                            >{req.details}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onclick={() =>
                                                approveRequest(req.id)}
                                            class="rounded-full bg-brand px-5 py-1.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                        >
                                            {t('project.dashboard.approve')}
                                        </button>
                                        <button
                                            type="button"
                                            onclick={() =>
                                                rejectRequest(req.id)}
                                            class="rounded-full bg-[#f13e3e]/10 px-5 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                        >
                                            {t('project.dashboard.reject')}
                                        </button>
                                    </div>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </DashboardCard>

                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('project.dashboard.add_member')}
                    </h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            type="search"
                            bind:value={term}
                            oninput={runSearch}
                            placeholder={t('project.dashboard.search_members')}
                            class="h-10 min-w-0 flex-1 rounded-full border border-black/10 px-5 text-start text-[13px] outline-none focus:border-brand"
                        />
                    </div>
                    {#if searching}
                        <p class="text-start text-[12px] text-[#7e7e7e]">
                            {t('app.loading')}
                        </p>
                    {:else if results.length > 0}
                        <ul class="flex flex-col gap-2">
                            {#each results as user (user.id)}
                                <li
                                    class="flex items-center justify-between gap-3 rounded-[10px] border border-black/5 px-4 py-2"
                                >
                                    <div
                                        class="flex flex-col items-start text-start"
                                    >
                                        <span class="text-[13px] text-black"
                                            >{user.name}</span
                                        >
                                        <span class="text-[12px] text-[#7e7e7e]"
                                            >{user.email}</span
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        onclick={() => addMember(user.id)}
                                        class="rounded-full bg-brand px-5 py-1.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                    >
                                        {t('project.dashboard.add')}
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </DashboardCard>

                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('project.dashboard.current_members')}
                    </h3>
                    {#if members.length === 0}
                        <EmptyState
                            message={t('project.dashboard.no_members')}
                        />
                    {:else}
                        <div class="flex flex-col divide-y divide-black/5">
                            {#each members as member (member.membershipId)}
                                <div class="flex flex-col gap-2 py-3">
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3"
                                    >
                                        <div
                                            class="flex flex-col items-start text-start"
                                        >
                                            <span class="text-[13px] text-black"
                                                >{member.name}</span
                                            >
                                            <span
                                                class="text-[12px] text-[#7e7e7e]"
                                                >{member.email}</span
                                            >
                                        </div>
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            {#each member.roles as role (role)}
                                                <span
                                                    class="rounded-full bg-brand/10 px-3 py-0.5 text-[11px] text-brand"
                                                >
                                                    {t(`project_roles.${role}`)}
                                                </span>
                                            {/each}
                                            {#if canManageRoles}
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        startEditRoles(member)}
                                                    class="rounded-full bg-brand/15 px-4 py-1.5 text-[12px] text-brand transition-colors hover:bg-brand/30"
                                                >
                                                    {t(
                                                        'project.dashboard.edit_roles',
                                                    )}
                                                </button>
                                            {/if}
                                            <button
                                                type="button"
                                                onclick={() =>
                                                    removeMember(
                                                        member.membershipId,
                                                    )}
                                                class="rounded-full bg-[#f13e3e]/10 px-4 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                            >
                                                {t('project.dashboard.remove')}
                                            </button>
                                        </div>
                                    </div>

                                    {#if editingId === member.membershipId}
                                        <div
                                            class="flex flex-col gap-3 rounded-[12px] bg-brand/5 p-4"
                                        >
                                            <div class="flex flex-wrap gap-3">
                                                {#each roleOptions as role (role.value)}
                                                    <label
                                                        class="flex items-center gap-2 text-[12px] text-[#5f5f5f]"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={draftRoles.includes(
                                                                role.value,
                                                            )}
                                                            onchange={() =>
                                                                toggleRole(
                                                                    role.value,
                                                                )}
                                                        />
                                                        {role.label}
                                                    </label>
                                                {/each}
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        saveRoles(
                                                            member.membershipId,
                                                        )}
                                                    class="rounded-full bg-brand px-5 py-1.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                                >
                                                    {t(
                                                        'project.dashboard.save',
                                                    )}
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        (editingId = null)}
                                                    class="rounded-full bg-brand/15 px-5 py-1.5 text-[12px] text-brand"
                                                >
                                                    {t('project.form.cancel')}
                                                </button>
                                            </div>
                                        </div>
                                    {/if}
                                </div>
                            {/each}
                        </div>
                    {/if}
                </DashboardCard>
            </section>
        {/if}

        {#if can(CAP.news)}
            <section
                aria-label={t('project.dashboard.news_management')}
                class="flex flex-col gap-5"
            >
                <SectionHeading title={t('project.dashboard.news_management')}>
                    {#snippet action()}
                        <Link
                            href={newsCreate(ids).url}
                            class="flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={PlusSignIcon}
                                class="size-4"
                            />
                            {t('project.dashboard.add_news')}
                        </Link>
                    {/snippet}
                </SectionHeading>
                {#if posts.length === 0}
                    <EmptyState message={t('project.dashboard.no_news')} />
                {:else}
                    <DashboardCard
                        class="flex flex-col divide-y divide-black/5"
                    >
                        {#each posts as post (post.id)}
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 py-3"
                            >
                                <span class="text-[13px] text-black"
                                    >{post.title}</span
                                >
                                <div class="flex items-center gap-2">
                                    <Link
                                        href={newsEdit([
                                            workspace.id,
                                            project.id,
                                            post.id,
                                        ]).url}
                                        class="rounded-full bg-brand/15 px-5 py-1.5 text-[12px] text-brand transition-colors hover:bg-brand/30"
                                    >
                                        {t('project.dashboard.edit')}
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => deletePost(post.id)}
                                        class="rounded-full bg-[#f13e3e]/10 px-5 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                    >
                                        {t('project.dashboard.delete')}
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </DashboardCard>
                {/if}
            </section>
        {/if}

        {#if can(CAP.reports)}
            <section
                aria-label={t('dashboard_workspace_lead.reports_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_workspace_lead.reports_section')}
                >
                    {#snippet action()}
                        <label
                            class="flex items-center gap-2 text-[12px] text-[#5f5f5f]"
                        >
                            <span
                                >{t(
                                    'dashboard_workspace_lead.report_language',
                                )}</span
                            >
                            <FilterSelect
                                class="min-h-0 w-auto gap-1.5 rounded-full border-brand/20 px-3 py-1.5 text-[12px] text-brand data-[size=default]:h-auto"
                                ariaLabel={t(
                                    'dashboard_workspace_lead.report_language',
                                )}
                                value={reportLocale}
                                options={reportLocaleOptions}
                                onValueChange={(locale) =>
                                    (reportLocale = locale as ReportLocale)}
                            />
                        </label>
                    {/snippet}
                </SectionHeading>
                <div class="grid grid-cols-1 gap-5 sm:max-w-sm">
                    <ReportCard
                        title={t('project.dashboard.report_members')}
                        description={t(
                            'dashboard_workspace_lead.report_members_desc',
                        )}
                        href={membersReportUrl}
                        buttonLabel={t('app.export_pdf')}
                    />
                </div>
            </section>
        {/if}
    </div>
</div>
