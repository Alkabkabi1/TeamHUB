<script lang="ts">
    import {
        CheckmarkCircle01Icon,
        Clock05Icon,
        TaskDaily01Icon,
        UserGroupIcon,
    } from '@hugeicons/core-free-icons';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import { members as reportMembers } from '@/actions/App/Http/Controllers/WorkspaceReportController';
    import AppHead from '@/components/AppHead.svelte';
    import ManageMembersSection from '@/components/clubs/manage/ManageMembersSection.svelte';
    import ClubThemeForm from '@/components/ClubThemeForm.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import ReportCard from '@/components/ReportCard.svelte';
    import SectionHeading from '@/components/SectionHeading.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import WorkspaceManageShell from '@/components/WorkspaceManageShell.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type { ClubBranding } from '@/types';
    import type {
        ClubManageMember,
        ClubManagePendingMember,
        ClubManageRoleOption,
    } from '@/types/club-manage';

    type Stat = {
        label: string;
        value: string;
        sub: string;
        icon: IconSvgElement;
    };

    type DashboardStats = {
        membersCount: number;
        pendingApplicationsCount: number;
        projectsCount: number;
        openTasksCount: number;
    };

    type WorkspaceStats = {
        projects_count: number;
        tasks_count: number;
        overdue_tasks_count: number;
    };

    type ManageClub = ClubBranding & { university: string | null };

    type WorkspaceProject = {
        id: number;
        name: string;
        description: string | null;
        status: string;
        logo_url: string | null;
        members_count: number;
        tasks_count: number;
        overdue_tasks_count: number;
    };

    type RecentActivity = {
        id: string;
        type: 'task' | 'update';
        title: string;
        context: string;
        time: string | null;
        url: string;
    };

    type ReportLocale = 'ar' | 'en';

    type Props = {
        club: ManageClub;
        capabilities: string[];
        canManageRoles: boolean;
        roleOptions: ClubManageRoleOption[];
        stats: DashboardStats;
        workspaceStats: WorkspaceStats;
        workspaceProjects: WorkspaceProject[];
        recentActivity: RecentActivity[];
        members: ClubManageMember[];
        pendingApplications: ClubManagePendingMember[];
    };

    let {
        club,
        capabilities = [],
        canManageRoles = false,
        roleOptions = [],
        stats = {
            membersCount: 0,
            pendingApplicationsCount: 0,
            projectsCount: 0,
            openTasksCount: 0,
        },
        workspaceStats = {
            projects_count: 0,
            tasks_count: 0,
            overdue_tasks_count: 0,
        },
        workspaceProjects = [],
        recentActivity = [],
        members = [],
        pendingApplications = [],
    }: Props = $props();

    const CAP = {
        club: 'manage-club',
        members: 'manage-members',
        reports: 'view-reports',
    } as const;

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
        reportMembers.url(club.id, { query: { locale: reportLocale } }),
    );

    const statCards: Stat[] = $derived([
        {
            label: t('dashboard_supervisor.stats.members'),
            value: formatNumber(stats.membersCount),
            sub: t('dashboard.members_this_month'),
            icon: UserGroupIcon,
        },
        {
            label: t('dashboard_supervisor.stats.applications'),
            value: formatNumber(stats.pendingApplicationsCount),
            sub: t('dashboard.pending_review'),
            icon: Clock05Icon,
        },
        {
            label: t('dashboard_student.stats.projects'),
            value: formatNumber(stats.projectsCount),
            sub: t('app.projects'),
            icon: TaskDaily01Icon,
        },
        {
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(stats.openTasksCount),
            sub: t('dashboard.in_progress_count'),
            icon: CheckmarkCircle01Icon,
        },
    ]);
</script>

<AppHead title={t('dashboard_supervisor.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <WorkspaceManageShell active="overview" {club} />

        <section
            aria-label={t('dashboard_supervisor.hero_aria')}
            class="w-full"
        >
            <!-- Mobile / tablet hero -->
            <div
                class="relative h-[260px] w-full overflow-hidden rounded-[20px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:h-[320px] sm:rounded-[28px] lg:hidden"
            >
                <img
                    src="/images/hero/stars-mobile-left.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 left-0 h-full w-1/2"
                />
                <img
                    src="/images/hero/stars-mobile-right.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 right-0 h-full w-1/2"
                />

                <img
                    src="/teamhub-icon.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[-1%] left-1/2 h-[66%] -translate-x-1/2 object-contain opacity-[0.05]"
                />
                <img
                    src="/teamhub-icon.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[9%] left-1/2 h-[40%] -translate-x-1/2 object-contain"
                />

                <div
                    class="absolute inset-x-0 bottom-[14%] flex flex-col items-center gap-2 px-6 text-center"
                >
                    <p
                        class="text-[28px] leading-tight text-white sm:text-[36px]"
                    >
                        {club.university ?? t('club.organization')}
                    </p>
                    <p
                        class="text-[18px] leading-snug text-white/80 sm:text-[22px]"
                    >
                        {club.name}
                    </p>
                </div>
            </div>

            <!-- Desktop hero - 1020 x 299 aspect, faithful to Figma node 46:3589 -->
            <div class="relative hidden aspect-[1020/299] w-full lg:block">
                <div
                    class="absolute inset-x-0 top-[8.03%] bottom-[7.69%] overflow-hidden rounded-[40px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                ></div>

                <img
                    src="/teamhub-icon.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[-2.34%] right-[0.69%] aspect-[447/559] w-[24.02%] object-cover opacity-[0.04]"
                />
                <img
                    src="/teamhub-icon.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[22.4%] right-[8.73%] aspect-[447/559] w-[12.94%] object-cover"
                />

                <img
                    src="/images/hero/stars.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-0 bottom-0 left-[1.18%] h-full w-[21.66%]"
                />

                <div
                    class="absolute top-[50%] right-[24.7%] flex w-[24.8%] -translate-y-1/2 flex-col items-start gap-1 text-start"
                >
                    <p class="w-full text-[40px] leading-[normal] text-white">
                        {club.university ?? t('club.organization')}
                    </p>
                    <p
                        class="w-full text-[24px] leading-[normal] text-white/80"
                    >
                        {club.name}
                    </p>
                </div>
            </div>
        </section>

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
        </section>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <DashboardCard class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-start">
                        <h2 class="text-lg font-medium text-black">
                            {t('app.projects')}
                        </h2>
                        <p class="text-sm text-[#7e7e7e]">
                            {workspaceStats.projects_count}
                            {t('app.projects')} • {workspaceStats.tasks_count}
                            {t('tasks.title')}
                        </p>
                    </div>
                    <Link
                        href={`/workspaces/${club.id}/committees`}
                        class="rounded-full bg-brand/10 px-4 py-2 text-sm font-medium text-brand transition-colors hover:bg-brand/20"
                    >
                        {t('app.show_more')}
                    </Link>
                </div>

                {#if workspaceProjects.length === 0}
                    <p class="text-sm text-[#7e7e7e]">
                        {t('committees.empty')}
                    </p>
                {:else}
                    <div class="grid gap-3 md:grid-cols-2">
                        {#each workspaceProjects as project (project.id)}
                            <Link
                                href={`/workspaces/${club.id}/projects/${project.id}/manage`}
                                class="rounded-[16px] border border-black/10 p-4 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-black">
                                        {project.name}
                                    </p>
                                    <p
                                        class="line-clamp-2 text-xs text-[#7e7e7e]"
                                    >
                                        {project.description ??
                                            t('clubs.default_description')}
                                    </p>
                                    <div
                                        class="flex flex-wrap gap-2 text-xs text-[#9a9a9a]"
                                    >
                                        <span
                                            >{project.members_count}
                                            {t('app.members')}</span
                                        >
                                        <span>•</span>
                                        <span
                                            >{project.tasks_count}
                                            {t('tasks.title')}</span
                                        >
                                        <span>•</span>
                                        <span
                                            >{project.overdue_tasks_count}
                                            {t('tasks.sections.overdue')}</span
                                        >
                                    </div>
                                </div>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>

            <DashboardCard class="flex flex-col gap-4">
                <div class="text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('app.updates')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">
                        {workspaceStats.overdue_tasks_count}
                        {t('tasks.sections.overdue')}
                    </p>
                </div>

                {#if recentActivity.length === 0}
                    <p class="text-sm text-[#7e7e7e]">{t('news.empty')}</p>
                {:else}
                    <div class="space-y-3">
                        {#each recentActivity as item (item.id)}
                            <Link
                                href={item.url}
                                class="block rounded-[14px] border border-black/10 p-3 text-start transition-colors hover:border-brand/30 hover:bg-brand/5"
                            >
                                <p class="text-sm font-medium text-black">
                                    {item.title}
                                </p>
                                <p class="text-xs text-[#7e7e7e]">
                                    {item.context}
                                </p>
                                <p class="mt-1 text-xs text-[#9a9a9a]">
                                    {item.time}
                                </p>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </DashboardCard>
        </section>

        {#if can(CAP.members)}
            <ManageMembersSection
                clubId={club.id}
                {members}
                {pendingApplications}
                {canManageRoles}
                {roleOptions}
            />
        {/if}

        {#if can(CAP.club)}
            <section
                aria-label={t('dashboard_supervisor.theme_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_supervisor.theme_customize')}
                />
                <DashboardCard class="sm:p-8">
                    <ClubThemeForm
                        {club}
                        logoUrl={club.logo_url}
                        onCancel={() => {}}
                    />
                </DashboardCard>
            </section>
        {/if}

        {#if can(CAP.reports)}
            <section
                aria-label={t('dashboard_supervisor.reports_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_supervisor.reports_section')}
                >
                    {#snippet action()}
                        <label
                            class="flex items-center gap-2 text-[12px] text-[#5f5f5f]"
                        >
                            <span
                                >{t(
                                    'dashboard_supervisor.report_language',
                                )}</span
                            >
                            <FilterSelect
                                class="min-h-0 w-auto gap-1.5 rounded-full border-brand/20 px-3 py-1.5 text-[12px] text-brand data-[size=default]:h-auto"
                                ariaLabel={t(
                                    'dashboard_supervisor.report_language',
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
                        title={t('dashboard_supervisor.report_members')}
                        description={t(
                            'dashboard_supervisor.report_members_desc',
                        )}
                        href={membersReportUrl}
                        buttonLabel={t('app.export_pdf')}
                    />
                </div>
            </section>
        {/if}
    </div>
</div>
