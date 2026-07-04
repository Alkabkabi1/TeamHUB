<script lang="ts">
    import {
        Calendar03Icon,
        Clock01Icon,
        PlusSignIcon,
        UserGroup03Icon,
        UserAdd01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventManageCard from '@/components/EventManageCard.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import ProjectManageShell from '@/components/ProjectManageShell.svelte';
    import ReportCard from '@/components/ReportCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import {
        create as eventCreate,
        edit as eventEdit,
        destroy as eventDestroy,
    } from '@/routes/committees/events';
    import {
        search as memberSearch,
        store as memberStore,
        roles as memberRoles,
        destroy as memberDestroy,
    } from '@/routes/committees/members';
    import {
        approve as requestApprove,
        reject as requestReject,
    } from '@/routes/committees/memberships';
    import {
        create as newsCreate,
        edit as newsEdit,
    } from '@/routes/committees/news';
    import {
        members as membersReport,
        volunteerHours as hoursReport,
        attendance as attendanceReport,
    } from '@/routes/committees/reports';
    import { destroy as postDestroy } from '@/routes/news';
    import type { ClubRef } from '@/types';

    type RoleOption = { value: string; label: string; isManager: boolean };
    type Member = {
        membershipId: number;
        userId: number;
        name: string;
        email: string;
        joinDate: string;
        volunteerHours: number;
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
    type ManagedEvent = {
        id: number;
        title: string;
        starts_at: string | null;
        location: string | null;
        capacity: number | null;
        status: string;
        attendances_count: number;
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

    let {
        club,
        committee,
        capabilities = [],
        canManageRoles = false,
        roleOptions = [],
        stats = {
            totalHours: 0,
            pendingApplicationsCount: 0,
            upcomingEventsCount: 0,
            membersCount: 0,
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
        managedEvents = [],
        posts = [],
    }: {
        club: ClubRef & { logo_url?: string | null };
        committee: {
            id: number;
            name: string;
            logo_url: string | null;
            status: string;
        };
        capabilities?: string[];
        canManageRoles?: boolean;
        roleOptions?: RoleOption[];
        stats?: {
            totalHours: number;
            pendingApplicationsCount: number;
            upcomingEventsCount: number;
            membersCount: number;
        };
        taskStats?: TaskStats;
        overviewMembers?: Member[];
        recentUpdates?: PostItem[];
        recentActivities?: ActivityItem[];
        members?: Member[];
        pendingApplications?: PendingRequest[];
        managedEvents?: ManagedEvent[];
        posts?: PostItem[];
    } = $props();

    const ids = $derived<[number, number]>([club.id, committee.id]);
    function can(capability: string): boolean {
        return capabilities.includes(capability);
    }

    const statCards = $derived([
        {
            icon: UserGroup03Icon,
            label: t('committees.dashboard.members'),
            value: formatNumber(stats.membersCount),
        },
        {
            icon: Clock01Icon,
            label: t('committees.dashboard.hours'),
            value: formatNumber(Math.round(stats.totalHours)),
        },
        {
            icon: Calendar03Icon,
            label: t('committees.dashboard.upcoming_events'),
            value: formatNumber(stats.upcomingEventsCount),
        },
        {
            icon: UserAdd01Icon,
            label: t('committees.dashboard.pending_requests'),
            value: formatNumber(stats.pendingApplicationsCount),
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

    // --- Join request review ---
    function approveRequest(id: number): void {
        router.post(
            requestApprove([club.id, committee.id, id]).url,
            {},
            { preserveScroll: true },
        );
    }
    function rejectRequest(id: number): void {
        router.post(
            requestReject([club.id, committee.id, id]).url,
            {},
            { preserveScroll: true },
        );
    }

    // --- Member search + add ---
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

    // --- Role editing ---
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
            memberRoles([club.id, committee.id, membershipId]).url,
            { roles: draftRoles },
            { preserveScroll: true, onSuccess: () => (editingId = null) },
        );
    }
    function removeMember(membershipId: number): void {
        router.delete(
            memberDestroy([club.id, committee.id, membershipId]).url,
            { preserveScroll: true },
        );
    }

    // --- Events / news delete ---
    function deleteEvent(id: number): void {
        router.delete(eventDestroy([club.id, committee.id, id]).url, {
            preserveScroll: true,
        });
    }
    function deletePost(id: number): void {
        router.delete(postDestroy(id).url, { preserveScroll: true });
    }
</script>

<AppHead title={`${committee.name} — ${t('committees.manage')}`} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <ProjectManageShell active="overview" {club} {committee} />

        <HeroBanner
            ariaLabel={committee.name}
            title={club.name}
            subtitle={committee.name}
            foregroundLogo={committee.logo_url ?? club.logo_url ?? undefined}
            backgroundLogo={committee.logo_url ?? club.logo_url ?? undefined}
        />

        <!-- Stats -->
        <section class="flex flex-col gap-5">
            <SectionHeader title={t('committees.dashboard.overview')} />
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {#each statCards as stat (stat.label)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
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
                    href={`/clubs/${club.id}/committees/${committee.id}/tasks`}
                    class="rounded-full bg-brand px-5 py-2 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('tasks.title')}
                </Link>
            </DashboardCard>
        </section>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <DashboardCard class="flex flex-col gap-4">
                <div class="text-start">
                    <h2 class="text-lg font-medium text-black">
                        {t('app.overview')}
                    </h2>
                    <p class="text-sm text-[#7e7e7e]">{t('app.project')}</p>
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
                    <p class="text-sm text-[#7e7e7e]">{committee.name}</p>
                </div>

                {#if recentUpdates.length === 0}
                    <p class="text-sm text-[#7e7e7e]">{t('news.empty')}</p>
                {:else}
                    <div class="space-y-3">
                        {#each recentUpdates as update (update.id)}
                            <Link
                                href={`/clubs/${club.id}/committees/${committee.id}/updates`}
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
                        {t('committees.dashboard.recent_activity')}
                    </p>
                    {#if recentActivities.length === 0}
                        <p class="text-sm text-[#7e7e7e]">
                            {t('committees.dashboard.no_recent_activity')}
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
                            {t('committees.dashboard.members')}
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

        {#if can('manage-committee-members')}
            <!-- Member management -->
            <section class="flex flex-col gap-5">
                <SectionHeader
                    title={t('committees.dashboard.members_management')}
                />

                <!-- Pending join requests -->
                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('committees.dashboard.pending_requests')}
                    </h3>
                    {#if pendingApplications.length === 0}
                        <EmptyState
                            message={t('committees.dashboard.no_requests')}
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
                                            {t('committees.dashboard.approve')}
                                        </button>
                                        <button
                                            type="button"
                                            onclick={() =>
                                                rejectRequest(req.id)}
                                            class="rounded-full bg-[#f13e3e]/10 px-5 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                        >
                                            {t('committees.dashboard.reject')}
                                        </button>
                                    </div>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </DashboardCard>

                <!-- Add member -->
                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('committees.dashboard.add_member')}
                    </h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            type="search"
                            bind:value={term}
                            oninput={runSearch}
                            placeholder={t(
                                'committees.dashboard.search_members',
                            )}
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
                                        {t('committees.dashboard.add')}
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </DashboardCard>

                <!-- Members table -->
                <DashboardCard class="flex flex-col gap-4">
                    <h3 class="text-start text-[14px] text-black">
                        {t('committees.dashboard.current_members')}
                    </h3>
                    {#if members.length === 0}
                        <EmptyState
                            message={t('committees.dashboard.no_members')}
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
                                                    {t(
                                                        `committee_roles.${role}`,
                                                    )}
                                                </span>
                                            {/each}
                                            <span
                                                class="text-[12px] text-[#7e7e7e]"
                                            >
                                                {t(
                                                    'committees.dashboard.hours_short',
                                                    {
                                                        count: formatNumber(
                                                            Math.round(
                                                                member.volunteerHours,
                                                            ),
                                                        ),
                                                    },
                                                )}
                                            </span>
                                            {#if canManageRoles}
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        startEditRoles(member)}
                                                    class="rounded-full bg-brand/15 px-4 py-1.5 text-[12px] text-brand transition-colors hover:bg-brand/30"
                                                >
                                                    {t(
                                                        'committees.dashboard.edit_roles',
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
                                                {t(
                                                    'committees.dashboard.remove',
                                                )}
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
                                                        'committees.dashboard.save',
                                                    )}
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick={() =>
                                                        (editingId = null)}
                                                    class="rounded-full bg-brand/15 px-5 py-1.5 text-[12px] text-brand"
                                                >
                                                    {t(
                                                        'committees.form.cancel',
                                                    )}
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

        {#if can('manage-committee-events')}
            <!-- Event management -->
            <section class="flex flex-col gap-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg text-[#5f5f5f] sm:text-xl">
                        {t('committees.dashboard.events_management')}
                    </h2>
                    <Link
                        href={eventCreate(ids).url}
                        class="flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={PlusSignIcon}
                            class="size-4"
                        />
                        {t('committees.dashboard.add_event')}
                    </Link>
                </div>
                {#if managedEvents.length === 0}
                    <EmptyState message={t('committees.dashboard.no_events')} />
                {:else}
                    <div
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        {#each managedEvents as event (event.id)}
                            <EventManageCard
                                title={event.title}
                                statusLabel={t(
                                    `events.status_labels.${event.status}`,
                                )}
                                dateLabel={dateLabel(event.starts_at)}
                                registrationsLabel={t(
                                    'committees.dashboard.registrations',
                                    {
                                        count: formatNumber(
                                            event.attendances_count,
                                        ),
                                    },
                                )}
                                editHref={eventEdit([
                                    club.id,
                                    committee.id,
                                    event.id,
                                ]).url}
                                editLabel={t('committees.dashboard.edit')}
                                deleteLabel={t('committees.dashboard.delete')}
                                onDelete={() => deleteEvent(event.id)}
                            />
                        {/each}
                    </div>
                {/if}
            </section>
        {/if}

        {#if can('manage-committee-news')}
            <!-- News management -->
            <section class="flex flex-col gap-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg text-[#5f5f5f] sm:text-xl">
                        {t('committees.dashboard.news_management')}
                    </h2>
                    <Link
                        href={newsCreate(ids).url}
                        class="flex items-center gap-1.5 rounded-full bg-brand px-5 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={PlusSignIcon}
                            class="size-4"
                        />
                        {t('committees.dashboard.add_news')}
                    </Link>
                </div>
                {#if posts.length === 0}
                    <EmptyState message={t('committees.dashboard.no_news')} />
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
                                            club.id,
                                            committee.id,
                                            post.id,
                                        ]).url}
                                        class="rounded-full bg-brand/15 px-5 py-1.5 text-[12px] text-brand transition-colors hover:bg-brand/30"
                                    >
                                        {t('committees.dashboard.edit')}
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => deletePost(post.id)}
                                        class="rounded-full bg-[#f13e3e]/10 px-5 py-1.5 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                    >
                                        {t('committees.dashboard.delete')}
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </DashboardCard>
                {/if}
            </section>
        {/if}

        {#if can('view-committee-reports')}
            <!-- Reports -->
            <section class="flex flex-col gap-5">
                <SectionHeader title={t('committees.dashboard.export')} />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <ReportCard
                        title={t('committees.dashboard.report_members')}
                        href={membersReport(ids).url}
                        buttonLabel={t('committees.dashboard.export_button')}
                    />
                    <ReportCard
                        title={t('committees.dashboard.report_hours')}
                        href={hoursReport(ids).url}
                        buttonLabel={t('committees.dashboard.export_button')}
                    />
                    <ReportCard
                        title={t('committees.dashboard.report_attendance')}
                        href={attendanceReport(ids).url}
                        buttonLabel={t('committees.dashboard.export_button')}
                    />
                </div>
            </section>
        {/if}
    </div>
</div>
