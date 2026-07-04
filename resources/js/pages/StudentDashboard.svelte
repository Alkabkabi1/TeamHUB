<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        Clock01Icon,
        TaskDaily01Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import TaskPriorityBadge from '@/components/tasks/TaskPriorityBadge.svelte';
    import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.svelte';
    import { formatDate, formatNumber, t } from '@/lib/i18n.svelte';

    type Stat = {
        label: string;
        value: string;
        icon: IconSvgElement;
    };

    type DashboardStats = {
        workspacesCount: number;
        projectsCount: number;
        openTasksCount: number;
        dueTodayCount: number;
        overdueCount: number;
    };

    type Profile = {
        name: string;
        email: string;
        subtitle: string;
        joinedAt: string | null;
    };

    type WorkspaceSummary = {
        id: number;
        name: string;
        memberSince: string;
        projectCount: number;
    };

    type ProjectSummary = {
        id: number;
        name: string;
        clubId: number;
        clubName: string;
        joinedAt: string | null;
    };

    type TaskPreview = {
        id: number;
        title: string;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        statusLabel: string;
        priority: 'low' | 'medium' | 'high' | 'urgent';
        priorityLabel: string;
        dueAt: string | null;
        clubId: number;
        clubName: string;
        committeeId: number;
        committeeName: string;
        detailUrl: string;
    };

    type UpdateItem = {
        id: number;
        title: string;
        committeeName: string;
        clubName: string;
        publishedAt: string | null;
        url: string;
    };

    type Props = {
        stats: DashboardStats;
        workspaces: WorkspaceSummary[];
        projects: ProjectSummary[];
        profile: Profile;
        attentionTasks: TaskPreview[];
        upcomingTasks: TaskPreview[];
        recentUpdates: UpdateItem[];
        myTasksUrl: string;
    };

    let {
        stats,
        workspaces = [],
        projects = [],
        profile,
        attentionTasks = [],
        upcomingTasks = [],
        recentUpdates = [],
        myTasksUrl,
    }: Props = $props();

    const joinedLabel = $derived(
        profile.joinedAt
            ? t('dashboard_student.joined_in', {
                  date: formatDate(profile.joinedAt, {
                      month: 'long',
                      year: 'numeric',
                  }),
              })
            : '',
    );

    const heroSummary = $derived(
        stats.overdueCount > 0 || stats.dueTodayCount > 0
            ? t('dashboard_student.hero_summary', {
                  overdue: formatNumber(stats.overdueCount),
                  today: formatNumber(stats.dueTodayCount),
              })
            : t('dashboard_student.hero_summary_empty'),
    );

    const statCards: Stat[] = $derived([
        {
            label: t('dashboard_student.stats.workspaces'),
            value: formatNumber(stats.workspacesCount),
            icon: UserGroup03Icon,
        },
        {
            label: t('dashboard_student.stats.projects'),
            value: formatNumber(stats.projectsCount),
            icon: TaskDaily01Icon,
        },
        {
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(stats.openTasksCount),
            icon: CheckmarkCircle01Icon,
        },
        {
            label: t('dashboard_student.stats.overdue'),
            value: formatNumber(stats.overdueCount),
            icon: Clock01Icon,
        },
    ]);

    function dueLabel(iso: string | null): string {
        if (!iso) {
            return t('tasks.not_set');
        }

        return formatDate(iso, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        });
    }
</script>

<AppHead title={t('dashboard_student.title')} />

<div class="flex flex-col bg-[#fdfdfd] dark:bg-[#0f172a]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <section
            class="rounded-[28px] bg-brand px-6 py-8 text-white shadow-[8px_8px_48px_rgba(0,0,0,0.08)] sm:px-8"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-2 text-start">
                    <p class="text-sm text-white/75">
                        {t('dashboard_student.eyebrow')}
                    </p>
                    <h1 class="text-3xl font-semibold">{profile.name}</h1>
                    {#if profile.subtitle}
                        <p class="text-sm text-white/80">{profile.subtitle}</p>
                    {/if}
                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/70"
                    >
                        {#if joinedLabel}
                            <span>{joinedLabel}</span>
                        {/if}
                        <span dir="ltr">{profile.email}</span>
                    </div>
                    <p class="text-base text-white">{heroSummary}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        href={myTasksUrl}
                        class="rounded-full bg-white px-5 py-2.5 text-sm font-medium text-brand transition-colors hover:bg-white/90 dark:bg-[#111827] dark:text-white dark:hover:bg-[#1f2937]"
                    >
                        {t('dashboard_student.view_my_tasks')}
                    </Link>
                    <Link
                        href="/clubs"
                        class="rounded-full border border-white/30 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-white/10"
                    >
                        {t('nav.clubs')}
                    </Link>
                </div>
            </div>
        </section>

        <section aria-label={t('dashboard.overview')}>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {#each statCards as stat, i (i)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                    />
                {/each}
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('dashboard_student.needs_attention')} />

                {#if attentionTasks.length === 0}
                    <EmptyState
                        message={t('dashboard_student.empty_tasks')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each attentionTasks as task (task.id)}
                            <Link
                                href={task.detailUrl}
                                class="block rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="space-y-2">
                                        <p
                                            class="text-sm font-medium text-black"
                                        >
                                            {task.title}
                                        </p>
                                        <p class="text-xs text-[#7e7e7e]">
                                            {task.clubName} / {task.committeeName}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <TaskStatusBadge
                                                status={task.status}
                                            />
                                            <TaskPriorityBadge
                                                priority={task.priority}
                                            />
                                        </div>
                                    </div>
                                    <p class="text-xs text-[#9a9a9a]">
                                        {dueLabel(task.dueAt)}
                                    </p>
                                </div>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('dashboard_student.upcoming_tasks')} />

                {#if upcomingTasks.length === 0}
                    <EmptyState
                        message={t('dashboard_student.empty_upcoming')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each upcomingTasks as task (task.id)}
                            <Link
                                href={task.detailUrl}
                                class="block rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="space-y-2">
                                        <p
                                            class="text-sm font-medium text-black"
                                        >
                                            {task.title}
                                        </p>
                                        <p class="text-xs text-[#7e7e7e]">
                                            {task.clubName} / {task.committeeName}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <TaskStatusBadge
                                                status={task.status}
                                            />
                                            <TaskPriorityBadge
                                                priority={task.priority}
                                            />
                                        </div>
                                    </div>
                                    <p class="text-xs text-[#9a9a9a]">
                                        {dueLabel(task.dueAt)}
                                    </p>
                                </div>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        </section>

        <section
            class="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]"
        >
            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('dashboard_student.recent_activity')} />

                {#if recentUpdates.length === 0}
                    <EmptyState
                        message={t('dashboard_student.empty_activity')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each recentUpdates as update (update.id)}
                            <Link
                                href={update.url}
                                class="block rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <p class="text-sm font-medium text-black">
                                    {update.title}
                                </p>
                                <p class="mt-1 text-xs text-[#7e7e7e]">
                                    {update.clubName} / {update.committeeName}
                                </p>
                                {#if update.publishedAt}
                                    <p class="mt-1 text-xs text-[#9a9a9a]">
                                        {formatDate(update.publishedAt, {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })}
                                    </p>
                                {/if}
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <SectionHeader title={t('dashboard_student.my_workspaces')} />

                {#if workspaces.length === 0}
                    <EmptyState
                        message={t('dashboard_student.empty_workspaces')}
                        class="shadow-none"
                    />
                {:else}
                    <div class="space-y-3">
                        {#each workspaces as workspace (workspace.id)}
                            <Link
                                href={`/clubs/${workspace.id}`}
                                class="block rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <p class="text-sm font-medium text-black">
                                    {workspace.name}
                                </p>
                                <p class="mt-1 text-xs text-[#7e7e7e]">
                                    {t('dashboard_student.member_since', {
                                        year: workspace.memberSince,
                                    })}
                                </p>
                                <p class="mt-1 text-xs text-[#9a9a9a]">
                                    {t(
                                        'dashboard_student.workspace_projects_count',
                                        {
                                            count: workspace.projectCount,
                                        },
                                    )}
                                </p>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('dashboard_student.my_projects')} />

            {#if projects.length === 0}
                <EmptyState message={t('dashboard_student.empty_projects')} />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
                >
                    {#each projects as project (project.id)}
                        <Link
                            href={`/clubs/${project.clubId}/committees/${project.id}/manage`}
                            class="rounded-[20px] bg-white p-5 text-start shadow-[8px_8px_48px_rgba(0,0,0,0.06)] transition-colors hover:bg-brand/5"
                        >
                            <p class="text-sm font-medium text-black">
                                {project.name}
                            </p>
                            <p class="mt-1 text-xs text-[#7e7e7e]">
                                {project.clubName}
                            </p>
                            {#if project.joinedAt}
                                <p class="mt-2 text-xs text-[#9a9a9a]">
                                    {formatDate(project.joinedAt, {
                                        month: 'short',
                                        year: 'numeric',
                                    })}
                                </p>
                            {/if}
                        </Link>
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>
