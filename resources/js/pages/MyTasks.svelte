<script lang="ts">
    import {
        Calendar02Icon,
        Clock01Icon,
        TaskDaily01Icon,
        TimeQuarterPassIcon,
    } from '@hugeicons/core-free-icons';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link, router } from '@inertiajs/svelte';
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

    type Summary = {
        overdue_count: number;
        due_today_count: number;
        upcoming_count: number;
        no_due_date_count: number;
        open_count: number;
    };

    type TaskItem = {
        id: number;
        title: string;
        status: 'todo' | 'in_progress' | 'review' | 'done';
        status_label: string;
        priority: 'low' | 'medium' | 'high' | 'urgent';
        priority_label: string;
        due_at: string | null;
        has_deliverable: boolean;
        workspace: { id: number; name: string } | null;
        project: { id: number; name: string } | null;
        detail_url: string;
        project_url: string;
        update_url: string;
        quick_action: { label: string; value: 'todo' | 'in_progress' } | null;
        can_toggle_progress: boolean;
    };

    type UpdateItem = {
        id: number;
        title: string;
        project_name: string;
        workspace_name: string;
        published_at: string | null;
        url: string;
    };

    type Props = {
        summary: Summary;
        overdueTasks?: TaskItem[];
        dueTodayTasks?: TaskItem[];
        upcomingTasks?: TaskItem[];
        noDueDateTasks?: TaskItem[];
        recentUpdates?: UpdateItem[];
    };

    let {
        summary,
        overdueTasks = [],
        dueTodayTasks = [],
        upcomingTasks = [],
        noDueDateTasks = [],
        recentUpdates = [],
    }: Props = $props();

    let pageLoading = $state(false);
    const skeletonItems = [0, 1, 2];

    const statCards: Stat[] = $derived([
        {
            label: t('tasks.sections.overdue'),
            value: formatNumber(summary.overdue_count),
            icon: Clock01Icon,
        },
        {
            label: t('tasks.sections.due_today'),
            value: formatNumber(summary.due_today_count),
            icon: Calendar02Icon,
        },
        {
            label: t('tasks.sections.upcoming'),
            value: formatNumber(summary.upcoming_count),
            icon: TimeQuarterPassIcon,
        },
        {
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(summary.open_count),
            icon: TaskDaily01Icon,
        },
    ]);

    const sections = $derived([
        {
            key: 'overdue',
            title: t('tasks.sections.overdue'),
            tasks: overdueTasks,
        },
        {
            key: 'due_today',
            title: t('tasks.sections.due_today'),
            tasks: dueTodayTasks,
        },
        {
            key: 'upcoming',
            title: t('tasks.sections.upcoming'),
            tasks: upcomingTasks,
        },
        {
            key: 'no_due_date',
            title: t('tasks.sections.no_due_date'),
            tasks: noDueDateTasks,
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

    function applyQuickAction(task: TaskItem): void {
        if (!task.quick_action) {
            return;
        }

        router.patch(
            task.update_url,
            {
                status: task.quick_action.value,
                return_to: '/my-tasks',
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    $effect(() => {
        const offStart = router.on('start', () => {
            pageLoading = true;
        });
        const offFinish = router.on('finish', () => {
            pageLoading = false;
        });

        return () => {
            offStart();
            offFinish();
        };
    });
</script>

<AppHead title={t('tasks.my_tasks_title')} />

<div class="flex flex-col bg-[#fdfdfd] dark:bg-[#0f172a]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <section
            class="rounded-[28px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] dark:bg-[#111827] dark:shadow-[8px_8px_48px_rgba(0,0,0,0.28)] sm:p-8"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-2 text-start">
                    <p class="text-sm text-[#7e7e7e]">{t('nav.my_work')}</p>
                    <h1
                        class="text-3xl font-semibold text-black dark:text-white"
                    >
                        {t('tasks.my_tasks_title')}
                    </h1>
                    <p class="text-sm text-[#5f5f5f] dark:text-[#cbd5e1]">
                        {t('tasks.my_tasks_description')}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/student-dashboard"
                        class="rounded-full bg-brand/10 px-5 py-2.5 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        {t('nav.my_work')}
                    </Link>
                </div>
            </div>
        </section>

        <section>
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

        <section
            class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]"
        >
            <div class="space-y-5">
                {#each sections as section (section.key)}
                    <DashboardCard class="flex flex-col gap-4">
                        <SectionHeader title={section.title} />

                        {#if pageLoading}
                            <div class="space-y-3">
                                {#each skeletonItems as item (item)}
                                    <div
                                        class="animate-pulse rounded-[14px] border border-black/10 p-4 dark:border-white/10"
                                    >
                                        <div
                                            class="h-4 w-1/3 rounded bg-black/10 dark:bg-white/10"
                                        ></div>
                                        <div
                                            class="mt-3 h-3 w-1/2 rounded bg-black/10 dark:bg-white/10"
                                        ></div>
                                        <div
                                            class="mt-4 h-10 rounded bg-black/10 dark:bg-white/10"
                                        ></div>
                                    </div>
                                {/each}
                            </div>
                        {:else if section.tasks.length === 0}
                            <EmptyState
                                message={t('tasks.empty_my_tasks')}
                                class="shadow-none"
                            />
                        {:else}
                            <div class="space-y-3">
                                {#each section.tasks as task (task.id)}
                                    <div
                                        class="rounded-[14px] border border-black/10 p-4"
                                    >
                                        <div
                                            class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                                        >
                                            <div class="space-y-2 text-start">
                                                <p
                                                    class="text-sm font-medium text-black dark:text-white"
                                                >
                                                    {task.title}
                                                </p>
                                                <p
                                                    class="text-xs text-[#7e7e7e] dark:text-[#94a3b8]"
                                                >
                                                    {task.workspace?.name} / {task
                                                        .project?.name}
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
                                                    {#if task.has_deliverable}
                                                        <span
                                                            class="rounded-full bg-brand/10 px-3 py-1 text-xs text-brand"
                                                        >
                                                            {t(
                                                                'tasks.deliverable_flag',
                                                            )}
                                                        </span>
                                                    {/if}
                                                </div>
                                                <p
                                                    class="text-xs text-[#9a9a9a] dark:text-[#94a3b8]"
                                                >
                                                    {dueLabel(task.due_at)}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <Link
                                                    href={task.detail_url}
                                                    class="rounded-full bg-brand/10 px-4 py-2 text-xs font-medium text-brand transition-colors hover:bg-brand/20"
                                                >
                                                    {t('tasks.view_task')}
                                                </Link>
                                                {#if task.quick_action}
                                                    <button
                                                        type="button"
                                                        onclick={() =>
                                                            applyQuickAction(
                                                                task,
                                                            )}
                                                        class="rounded-full bg-brand px-4 py-2 text-xs font-medium text-white transition-colors hover:bg-brand-dark"
                                                    >
                                                        {task.quick_action
                                                            .label}
                                                    </button>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/each}
                            </div>
                        {/if}
                    </DashboardCard>
                {/each}
            </div>

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
                                class="block rounded-[14px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5 dark:border-white/10 dark:hover:bg-brand/10"
                            >
                                <p
                                    class="text-sm font-medium text-black dark:text-white"
                                >
                                    {update.title}
                                </p>
                                <p
                                    class="mt-1 text-xs text-[#7e7e7e] dark:text-[#94a3b8]"
                                >
                                    {update.workspace_name} / {update.project_name}
                                </p>
                                {#if update.published_at}
                                    <p
                                        class="mt-1 text-xs text-[#9a9a9a] dark:text-[#94a3b8]"
                                    >
                                        {formatDate(update.published_at, {
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
        </section>
    </div>
</div>
