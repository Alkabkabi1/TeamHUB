<script lang="ts">
    import {
        Add01Icon,
        Calendar02Icon,
        Cancel01Icon,
        Clock01Icon,
        Clock05Icon,
        Delete02Icon,
        Search01Icon,
        User03Icon,
        UserGroupIcon,
        UserSettings01Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import { Form, Link, page, router, useHttp } from '@inertiajs/svelte';
    import { storeManual as storeManualCertificate } from '@/actions/App/Http/Controllers/CertificateController';
    import {
        index as certificateTemplatesIndex,
        create as createCertificateTemplate,
        edit as editCertificateTemplate,
    } from '@/actions/App/Http/Controllers/CertificateTemplateController';
    import {
        search as searchMembers,
        store as storeMember,
        updateRoles as updateMemberRoles,
        destroy as destroyMember,
    } from '@/actions/App/Http/Controllers/ClubMemberController';
    import {
        members as reportMembers,
        volunteerHours as reportVolunteerHours,
        attendance as reportAttendance,
    } from '@/actions/App/Http/Controllers/ClubReportController';
    import { store as storeVolunteerHours } from '@/actions/App/Http/Controllers/ClubVolunteerHourController';
    import AppHead from '@/components/AppHead.svelte';
    import CertificateTemplateCard from '@/components/CertificateTemplateCard.svelte';
    import ClubThemeForm from '@/components/ClubThemeForm.svelte';
    import DashboardCard from '@/components/DashboardCard.svelte';
    import EventManageCard from '@/components/EventManageCard.svelte';
    import FilterSelect from '@/components/FilterSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import ReportCard from '@/components/ReportCard.svelte';
    import SectionHeading from '@/components/SectionHeading.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import WorkspaceManageShell from '@/components/WorkspaceManageShell.svelte';
    import { formatDate, formatNumber, t } from '@/lib/i18n.svelte';
    import type { ClubBranding } from '@/types';

    type Stat = {
        label: string;
        value: string;
        sub: string;
        icon: IconSvgElement;
    };

    type Member = {
        membershipId: number;
        userId: number;
        name: string;
        email: string;
        major: string;
        joinDate: string;
        volunteerHours: number;
        roles: string[];
        isManager: boolean;
        status: string;
    };

    type DashboardStats = {
        totalHours: number;
        pendingApplicationsCount: number;
        upcomingEventsCount: number;
        membersCount: number;
    };

    type WorkspaceStats = {
        projects_count: number;
        tasks_count: number;
        overdue_tasks_count: number;
    };

    type PendingMember = {
        id: number;
        name: string;
        details: string;
        time: string;
    };

    type ManagedEvent = {
        id: number;
        title: string;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        capacity: number | null;
        status: string;
        attendances_count: number;
        scannable: boolean;
    };

    type ReportLocale = 'ar' | 'en';

    type Report = {
        title: string;
        description: string;
        downloadUrl: string;
    };

    type PastEvent = {
        id: number;
        title: string;
        startsAt: string | null;
    };

    type Post = {
        id: number;
        title: string;
        published_at: string | null;
    };

    type EligibleAttendee = {
        attendanceId: number;
        eventId: number;
        eventTitle: string;
        userId: number;
        userName: string;
        userEmail: string;
        existingHours: number | null;
        certificateId: number | null;
        hasCertificate: boolean;
    };

    type CertificateTemplateField = {
        text: string;
        is_image: boolean;
        x: number;
        y: number;
        width: number;
        font_size: number;
        align: string;
        color: string;
        font_weight: string;
    };

    type CertificateTemplateItem = {
        id: number;
        name: string;
        status: string;
        is_default: boolean;
        image_url: string | null;
        width: number;
        height: number;
        fields_count: number;
        fields: CertificateTemplateField[];
    };

    type RoleOption = { value: string; label: string; isManager: boolean };

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

    type FoundUser = { id: number; name: string; email: string };

    type Props = {
        club: ManageClub;
        capabilities: string[];
        canManageRoles: boolean;
        roleOptions: RoleOption[];
        pastEvents: PastEvent[];
        eligibleAttendees: EligibleAttendee[];
        hasDefaultTemplate: boolean;
        certificateTemplates: CertificateTemplateItem[];
        stats: DashboardStats;
        workspaceStats: WorkspaceStats;
        workspaceProjects: WorkspaceProject[];
        recentActivity: RecentActivity[];
        members: Member[];
        pendingApplications: PendingMember[];
        managedEvents: ManagedEvent[];
        posts: Post[];
    };

    let {
        club,
        capabilities = [],
        canManageRoles = false,
        roleOptions = [],
        pastEvents = [],
        eligibleAttendees = [],
        hasDefaultTemplate = false,
        certificateTemplates = [],
        stats,
        workspaceStats = {
            projects_count: 0,
            tasks_count: 0,
            overdue_tasks_count: 0,
        },
        workspaceProjects = [],
        recentActivity = [],
        members = [],
        pendingApplications = [],
        managedEvents = [],
        posts = [],
    }: Props = $props();

    // Club capabilities (mirror App\Enums\ClubCapability values) used to gate
    // each management section to the user's role(s) within this club.
    const CAP = {
        club: 'manage-club',
        events: 'manage-events',
        news: 'manage-news',
        members: 'manage-members',
        hours: 'manage-volunteer-hours',
        certificates: 'issue-certificates',
        reports: 'view-reports',
        attendance: 'record-attendance',
    } as const;

    function can(capability: string): boolean {
        return capabilities.includes(capability);
    }

    const managerRoleOptions = $derived(
        roleOptions.filter((option) => option.isManager),
    );

    function managerRolesFor(member: Member): string[] {
        const managerValues = managerRoleOptions.map((option) => option.value);

        return member.roles.filter((role) => managerValues.includes(role));
    }

    let reportLocale = $state<ReportLocale>(
        page.props.locale === 'en' ? 'en' : 'ar',
    );

    let memberSearch = $state('');

    let selectedEventId = $state('');
    let selectedUserId = $state('');
    let hoursValue = $state('');

    // Manual certificate issuance (independent of the volunteer-hours form).
    let certUserId = $state('');
    let certEventId = $state('');
    let certTemplateId = $state('');

    // --- Add member (search existing users and add directly) ---------------
    const http = useHttp();
    let addOpen = $state(false);
    let userQuery = $state('');
    let userResults = $state<FoundUser[]>([]);
    let searching = $state(false);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function onUserQueryInput(): void {
        clearTimeout(searchTimer);
        const q = userQuery.trim();

        if (q.length < 2) {
            userResults = [];

            return;
        }

        searchTimer = setTimeout(async () => {
            searching = true;

            try {
                const result = (await http.submit(
                    searchMembers({ club: club.id }, { query: { q } }),
                )) as { users: FoundUser[] };
                userResults = result.users ?? [];
            } catch {
                userResults = [];
            } finally {
                searching = false;
            }
        }, 250);
    }

    function addMember(userId: number): void {
        router.post(
            storeMember.url(club.id),
            { user_id: userId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    addOpen = false;
                    userQuery = '';
                    userResults = [];
                },
            },
        );
    }

    function removeMember(member: Member): void {
        if (confirm(t('members.confirm_remove'))) {
            router.delete(
                destroyMember.url({
                    club: club.id,
                    membership: member.membershipId,
                }),
                { preserveScroll: true },
            );
        }
    }

    // --- Manage roles (club lead only) -------------------------------------
    let roleOpen = $state(false);
    let roleTarget = $state<Member | null>(null);
    let selectedRoles = $state<string[]>([]);

    function openRoleModal(member: Member): void {
        roleTarget = member;
        selectedRoles = managerRolesFor(member);
        roleOpen = true;
    }

    function toggleRole(value: string): void {
        selectedRoles = selectedRoles.includes(value)
            ? selectedRoles.filter((role) => role !== value)
            : [...selectedRoles, value];
    }

    function saveRoles(): void {
        if (roleTarget === null) {
            return;
        }

        router.put(
            updateMemberRoles.url({
                club: club.id,
                membership: roleTarget.membershipId,
            }),
            { roles: selectedRoles },
            {
                preserveScroll: true,
                onSuccess: () => {
                    roleOpen = false;
                    roleTarget = null;
                },
            },
        );
    }

    const attendeesForEvent = $derived(
        selectedEventId === ''
            ? []
            : eligibleAttendees.filter(
                  (attendee) => String(attendee.eventId) === selectedEventId,
              ),
    );

    const eventOptions = $derived(
        pastEvents.map((event) => ({
            value: String(event.id),
            label: event.title,
        })),
    );

    const attendeeOptions = $derived(
        attendeesForEvent.map((attendee) => ({
            value: String(attendee.userId),
            label: `${attendee.userName} (${attendee.userEmail})`,
        })),
    );

    // All club members are eligible recipients for a manually-issued
    // certificate, whether or not it is tied to an activity.
    const certStudentOptions = $derived(
        members.map((member) => ({
            value: String(member.userId),
            label: `${member.name} (${member.email})`,
        })),
    );

    // Volunteer-hours recipients: when an event is selected we restrict to its
    // eligible attendees; otherwise any approved member can receive general
    // (activity-less) hours.
    const hoursUserOptions = $derived(
        selectedEventId === '' ? certStudentOptions : attendeeOptions,
    );

    // Only active templates can render a certificate; offer those, defaulting
    // to the club's default template for convenience.
    const certTemplateOptions = $derived(
        certificateTemplates
            .filter((template) => template.status === 'active')
            .map((template) => ({
                value: String(template.id),
                label: template.is_default
                    ? `${template.name} (${t('certificate_templates.default_badge')})`
                    : template.name,
            })),
    );

    $effect(() => {
        if (certTemplateId === '' && certTemplateOptions.length > 0) {
            const defaultTemplate = certificateTemplates.find(
                (template) =>
                    template.is_default && template.status === 'active',
            );

            certTemplateId = defaultTemplate
                ? String(defaultTemplate.id)
                : certTemplateOptions[0].value;
        }
    });

    const reportLocaleOptions = $derived([
        { value: 'ar', label: t('app.locale_ar') },
        { value: 'en', label: t('app.locale_en') },
    ]);

    $effect(() => {
        void selectedEventId;
        selectedUserId = '';
    });

    $effect(() => {
        if (selectedUserId === '') {
            hoursValue = '';

            return;
        }

        const attendee = attendeesForEvent.find(
            (entry) => String(entry.userId) === selectedUserId,
        );

        hoursValue =
            attendee?.existingHours != null
                ? String(attendee.existingHours)
                : '';
    });

    const filteredMembers = $derived(
        memberSearch.trim() === ''
            ? members
            : members.filter((member) => {
                  const query = memberSearch.trim().toLowerCase();

                  return (
                      member.name.toLowerCase().includes(query) ||
                      member.email.toLowerCase().includes(query)
                  );
              }),
    );

    const statCards: Stat[] = $derived([
        {
            label: t('dashboard_supervisor.stats.hours'),
            value: formatNumber(stats.totalHours),
            sub: t('dashboard.this_semester'),
            icon: Clock01Icon,
        },
        {
            label: t('dashboard_supervisor.stats.applications'),
            value: formatNumber(stats.pendingApplicationsCount),
            sub: t('dashboard.pending_review'),
            icon: Clock05Icon,
        },
        {
            label: t('dashboard_supervisor.stats.events'),
            value: formatNumber(stats.upcomingEventsCount),
            sub: t('dashboard.in_progress_count'),
            icon: Calendar02Icon,
        },
        {
            label: t('dashboard_supervisor.stats.members'),
            value: formatNumber(stats.membersCount),
            sub: t('dashboard.members_this_month'),
            icon: UserGroupIcon,
        },
    ]);

    function managedEventRegistrationsLabel(event: ManagedEvent): string {
        if (event.capacity === null) {
            return t('dashboard_supervisor.event_registrations', {
                count: event.attendances_count,
            });
        }

        return t('dashboard_supervisor.event_registrations_of', {
            count: event.attendances_count,
            total: event.capacity,
        });
    }

    function eventDateLabel(event: ManagedEvent): string | null {
        if (event.starts_at === null) {
            return null;
        }

        return formatDate(event.starts_at, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    }

    function deleteEvent(event: ManagedEvent): void {
        if (confirm(t('dashboard_supervisor.event_delete_confirm'))) {
            router.delete(`/clubs/${club.id}/events/${event.id}`, {
                preserveScroll: true,
            });
        }
    }

    // Four report cards as in the Figma. CSV/Excel exports are not built yet, so
    // every card downloads its PDF for now (the comprehensive report reuses the
    // members export until a dedicated endpoint exists).
    const reports: Report[] = $derived([
        {
            title: t('dashboard_supervisor.report_stats'),
            description: t('dashboard_supervisor.report_stats_desc'),
            downloadUrl: reportVolunteerHours.url(club.id, {
                query: { locale: reportLocale },
            }),
        },
        {
            title: t('dashboard_supervisor.report_events'),
            description: t('dashboard_supervisor.report_events_desc'),
            downloadUrl: reportAttendance.url(club.id, {
                query: { locale: reportLocale },
            }),
        },
        {
            title: t('dashboard_supervisor.report_members'),
            description: t('dashboard_supervisor.report_members_desc'),
            downloadUrl: reportMembers.url(club.id, {
                query: { locale: reportLocale },
            }),
        },
        {
            title: t('dashboard_supervisor.report_comprehensive'),
            description: t('dashboard_supervisor.report_comprehensive_desc'),
            downloadUrl: reportMembers.url(club.id, {
                query: { locale: reportLocale },
            }),
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
                        href={`/clubs/${club.id}/committees`}
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
                                href={`/clubs/${club.id}/committees/${project.id}/manage`}
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
                                            >{project.overdue_tasks_count} overdue</span
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
                        {workspaceStats.overdue_tasks_count} overdue
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
            <section
                aria-label={t('members.section_title')}
                class="flex flex-col gap-5"
            >
                <SectionHeading title={t('members.section_title')}>
                    {#snippet action()}
                        <button
                            type="button"
                            onclick={() => (addOpen = true)}
                            class="flex cursor-pointer items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={Add01Icon}
                                class="size-4"
                            />
                            {t('members.add_member')}
                        </button>
                    {/snippet}
                </SectionHeading>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <DashboardCard class="flex flex-col gap-4">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <p class="text-start text-[15px] text-black">
                                {t('members.current_members', {
                                    count: members.length,
                                })}
                            </p>
                            <label
                                class="flex h-[30px] min-w-[160px] max-w-[220px] flex-1 items-center gap-2 rounded-full border border-black/20 bg-white px-4"
                            >
                                <input
                                    type="search"
                                    bind:value={memberSearch}
                                    placeholder={t(
                                        'members.search_placeholder',
                                    )}
                                    class="order-2 h-full min-w-0 flex-1 bg-transparent text-start text-[12px] text-[#5f5f5f] outline-none placeholder:text-[#7e7e7e]"
                                />
                                <HugeiconsIcon
                                    strokeWidth={2}
                                    icon={Search01Icon}
                                    class="order-1 size-3 shrink-0 text-[#7e7e7e]"
                                />
                            </label>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[640px] text-start">
                                <thead>
                                    <tr class="text-[12px] text-[#5f5f5f]">
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_member')}
                                        </th>
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_major')}
                                        </th>
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_join_date')}
                                        </th>
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_hours')}
                                        </th>
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_status')}
                                        </th>
                                        <th
                                            class="px-2 pb-3 text-start font-normal"
                                        >
                                            {t('members.col_actions')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each filteredMembers as member (member.membershipId)}
                                        <tr class="border-t border-black/5">
                                            <td class="px-2 py-3">
                                                <div
                                                    class="flex flex-col items-start leading-tight"
                                                >
                                                    <p
                                                        class="text-[12px] text-black"
                                                    >
                                                        {member.name}
                                                    </p>
                                                    <p
                                                        class="text-[12px] text-[#5f5f5f]"
                                                    >
                                                        {member.email}
                                                    </p>
                                                </div>
                                            </td>
                                            <td
                                                class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                            >
                                                {member.major}
                                            </td>
                                            <td
                                                class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                            >
                                                {member.joinDate}
                                            </td>
                                            <td
                                                class="px-2 py-3 text-[12px] whitespace-nowrap text-[#5f5f5f]"
                                            >
                                                {t('members.hours_unit', {
                                                    count: formatNumber(
                                                        member.volunteerHours,
                                                    ),
                                                })}
                                            </td>
                                            <td class="px-2 py-3">
                                                <StatusBadge
                                                    label={member.status}
                                                />
                                            </td>
                                            <td class="px-2 py-3">
                                                <div
                                                    class="flex items-center justify-start gap-2"
                                                >
                                                    {#if canManageRoles}
                                                        <button
                                                            type="button"
                                                            onclick={() =>
                                                                openRoleModal(
                                                                    member,
                                                                )}
                                                            title={t(
                                                                'members.manage_roles',
                                                            )}
                                                            class="flex cursor-pointer items-center gap-1 rounded-full bg-brand/10 px-3 py-1.5 text-[11px] text-brand transition-colors hover:bg-brand/20"
                                                        >
                                                            <HugeiconsIcon
                                                                strokeWidth={2}
                                                                icon={UserSettings01Icon}
                                                                class="size-3.5"
                                                            />
                                                            {t(
                                                                'members.manage_roles',
                                                            )}
                                                        </button>
                                                    {/if}
                                                    <button
                                                        type="button"
                                                        onclick={() =>
                                                            removeMember(
                                                                member,
                                                            )}
                                                        title={t(
                                                            'members.remove',
                                                        )}
                                                        class="flex cursor-pointer items-center gap-1 rounded-full bg-[#f13e3e]/10 px-3 py-1.5 text-[11px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                                    >
                                                        <HugeiconsIcon
                                                            strokeWidth={2}
                                                            icon={Delete02Icon}
                                                            class="size-3.5"
                                                        />
                                                        {t('members.remove')}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                    {#if filteredMembers.length === 0}
                                        <tr>
                                            <td
                                                colspan="6"
                                                class="px-2 py-6 text-center text-[12px] text-[#7e7e7e]"
                                            >
                                                {t('members.no_members')}
                                            </td>
                                        </tr>
                                    {/if}
                                </tbody>
                            </table>
                        </div>
                    </DashboardCard>

                    <DashboardCard class="flex flex-col gap-4">
                        <p class="text-start text-[15px] text-black">
                            {t('members.pending_title', {
                                count: pendingApplications.length,
                            })}
                        </p>
                        <div class="flex flex-col gap-4">
                            {#each pendingApplications as request (request.id)}
                                <div
                                    class="flex flex-wrap items-center gap-4 rounded-[12px] border border-black/20 bg-white p-4"
                                >
                                    <div
                                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand/25 text-brand"
                                    >
                                        <HugeiconsIcon
                                            strokeWidth={2}
                                            icon={User03Icon}
                                            class="size-5"
                                        />
                                    </div>
                                    <div
                                        class="flex min-w-0 flex-1 flex-col items-start text-start leading-tight"
                                    >
                                        <p
                                            class="text-[14px] font-medium text-black"
                                        >
                                            {request.name}
                                        </p>
                                        <p
                                            class="mt-1 text-[12px] text-[#5f5f5f]"
                                        >
                                            {request.details}
                                        </p>
                                        <p
                                            class="mt-1 text-[12px] text-brand/60"
                                        >
                                            {request.time}
                                        </p>
                                    </div>
                                    <div
                                        class="flex w-full items-center justify-end gap-2 sm:w-auto"
                                    >
                                        <button
                                            type="button"
                                            onclick={() =>
                                                router.post(
                                                    `/join-applications/${request.id}/approve`,
                                                    {},
                                                    { preserveScroll: true },
                                                )}
                                            class="cursor-pointer rounded-full bg-brand px-4 py-2 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                        >
                                            {t('members.approve')}
                                        </button>
                                        <button
                                            type="button"
                                            onclick={() =>
                                                router.post(
                                                    `/join-applications/${request.id}/reject`,
                                                    {},
                                                    { preserveScroll: true },
                                                )}
                                            class="cursor-pointer rounded-full bg-[#f13e3e] px-4 py-2 text-[12px] text-white transition-colors hover:bg-[#cc3434]"
                                        >
                                            {t('members.reject')}
                                        </button>
                                    </div>
                                </div>
                            {/each}
                            {#if pendingApplications.length === 0}
                                <p
                                    class="text-start text-[12px] text-[#7e7e7e]"
                                >
                                    {t('members.no_pending')}
                                </p>
                            {/if}
                        </div>
                    </DashboardCard>
                </div>
            </section>
        {/if}

        {#if can(CAP.events) || can(CAP.attendance)}
            <section
                aria-label={t('dashboard_supervisor.events_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_supervisor.events_section')}
                >
                    {#snippet action()}
                        {#if can(CAP.events)}
                            <Link
                                href={`/clubs/${club.id}/events/create`}
                                class="cursor-pointer rounded-full bg-brand px-5 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                            >
                                {t('dashboard_supervisor.create_event')}
                            </Link>
                        {/if}
                    {/snippet}
                </SectionHeading>
                {#if managedEvents.length === 0}
                    <DashboardCard>
                        <p class="text-start text-sm text-[#5f5f5f]">
                            {t('dashboard_supervisor.no_managed_events')}
                        </p>
                    </DashboardCard>
                {:else}
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        {#each managedEvents as event (event.id)}
                            <EventManageCard
                                title={event.title}
                                statusLabel={event.status}
                                dateLabel={eventDateLabel(event)}
                                registrationsLabel={managedEventRegistrationsLabel(
                                    event,
                                )}
                                editHref={can(CAP.events)
                                    ? `/clubs/${club.id}/events/${event.id}/edit`
                                    : null}
                                editLabel={t('dashboard_supervisor.event_edit')}
                                deleteLabel={can(CAP.events)
                                    ? t('dashboard_supervisor.event_delete')
                                    : null}
                                onDelete={() => deleteEvent(event)}
                                scanHref={can(CAP.attendance) && event.scannable
                                    ? `/clubs/${club.id}/events/${event.id}/scan`
                                    : null}
                                scanLabel={t('attendance.manage.scan_button')}
                            />
                        {/each}
                    </div>
                {/if}
            </section>
        {/if}

        {#if can(CAP.hours)}
            <section
                aria-label={t('dashboard_supervisor.hours_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_supervisor.hours_section')}
                    description={t('dashboard_supervisor.hours_section_desc')}
                />

                <Form
                    {...storeVolunteerHours.form(club)}
                    class="flex flex-col gap-6 rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:p-8"
                >
                    {#snippet children({ errors, processing })}
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <label
                                    for="hours-event"
                                    class="text-start text-[14px] text-[#7e7e7e]"
                                >
                                    {t(
                                        'dashboard_supervisor.linked_event_optional',
                                    )}
                                </label>
                                <FilterSelect
                                    id="hours-event"
                                    class="h-10 min-h-10 rounded-[10px] border-black/20 px-5 text-[12px] text-black"
                                    name="event_id"
                                    ariaLabel={t(
                                        'dashboard_supervisor.linked_event_optional',
                                    )}
                                    placeholder={t(
                                        'dashboard_supervisor.select_event',
                                    )}
                                    bind:value={selectedEventId}
                                    options={eventOptions}
                                />
                                <InputError message={errors.event_id} />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label
                                    for="hours-student"
                                    class="text-start text-[14px] text-[#7e7e7e]"
                                >
                                    {t('dashboard_supervisor.select_student')}
                                </label>
                                <FilterSelect
                                    id="hours-student"
                                    class="h-10 min-h-10 rounded-[10px] border-black/20 px-5 text-[12px] text-black"
                                    name="user_id"
                                    ariaLabel={t(
                                        'dashboard_supervisor.select_student',
                                    )}
                                    placeholder={t(
                                        'dashboard_supervisor.select_student',
                                    )}
                                    disabled={selectedEventId !== '' &&
                                        attendeesForEvent.length === 0}
                                    bind:value={selectedUserId}
                                    options={hoursUserOptions}
                                />
                                <InputError message={errors.user_id} />
                                {#if selectedEventId !== '' && attendeesForEvent.length === 0}
                                    <p
                                        class="text-start text-[12px] text-[#5f5f5f]"
                                    >
                                        {t(
                                            'dashboard_supervisor.no_eligible_attendees',
                                        )}
                                    </p>
                                {/if}
                            </div>

                            <div class="flex flex-col gap-2 lg:col-span-2">
                                <label
                                    for="hours-amount"
                                    class="text-start text-[14px] text-[#7e7e7e]"
                                >
                                    {t('dashboard_supervisor.hours_label')}
                                </label>
                                <input
                                    id="hours-amount"
                                    name="hours"
                                    type="number"
                                    min="0.25"
                                    max="24"
                                    step="0.25"
                                    bind:value={hoursValue}
                                    placeholder={t(
                                        'dashboard_supervisor.hours_placeholder',
                                    )}
                                    class="h-10 rounded-[10px] border border-black/20 bg-white px-5 text-start text-[12px] text-black outline-none placeholder:text-black/20 focus:border-brand"
                                />
                                <InputError message={errors.hours} />
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={processing ||
                                selectedUserId === '' ||
                                hoursValue === ''}
                            class="mx-auto w-full max-w-[500px] cursor-pointer rounded-full bg-brand px-10 py-3 text-[14px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {t('dashboard_supervisor.record_hours_button')}
                        </button>
                    {/snippet}
                </Form>
            </section>
        {/if}

        {#if can(CAP.certificates)}
            <section
                aria-label={t('dashboard_supervisor.certificates_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading
                    title={t('dashboard_supervisor.certificates_section')}
                >
                    {#snippet action()}
                        <Link
                            href={certificateTemplatesIndex(club.id).url}
                            class="cursor-pointer rounded-full bg-brand/15 px-5 py-2 text-[12px] text-brand transition-colors hover:bg-brand/25"
                        >
                            {t('dashboard_supervisor.design_templates')}
                        </Link>
                    {/snippet}
                </SectionHeading>
                {#if !hasDefaultTemplate}
                    <p
                        class="rounded-[20px] bg-[#fff8e6] p-5 text-start text-sm text-[#8a6d00] shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                    >
                        {t('dashboard_supervisor.no_default_template')}
                    </p>
                {:else}
                    <Form
                        {...storeManualCertificate.form(club.id)}
                        class="flex flex-col gap-6 rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:p-8"
                        onSuccess={() => {
                            certUserId = '';
                            certEventId = '';
                        }}
                    >
                        {#snippet children({ errors, processing })}
                            <p class="text-start text-[14px] text-black">
                                {t('dashboard_supervisor.create_certificate')}
                            </p>

                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <div class="flex flex-col gap-2 lg:col-span-2">
                                    <label
                                        for="cert-template"
                                        class="text-start text-[14px] text-[#7e7e7e]"
                                    >
                                        {t(
                                            'dashboard_supervisor.select_template',
                                        )}
                                    </label>
                                    <FilterSelect
                                        id="cert-template"
                                        class="h-10 min-h-10 rounded-[10px] border-black/20 px-5 text-[12px] text-black"
                                        name="template_id"
                                        ariaLabel={t(
                                            'dashboard_supervisor.select_template',
                                        )}
                                        placeholder={t(
                                            'dashboard_supervisor.select_template',
                                        )}
                                        bind:value={certTemplateId}
                                        options={certTemplateOptions}
                                    />
                                    <InputError message={errors.template_id} />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label
                                        for="cert-student"
                                        class="text-start text-[14px] text-[#7e7e7e]"
                                    >
                                        {t(
                                            'dashboard_supervisor.select_student',
                                        )}
                                    </label>
                                    <FilterSelect
                                        id="cert-student"
                                        class="h-10 min-h-10 rounded-[10px] border-black/20 px-5 text-[12px] text-black"
                                        name="user_id"
                                        ariaLabel={t(
                                            'dashboard_supervisor.select_student',
                                        )}
                                        placeholder={t(
                                            'dashboard_supervisor.select_student',
                                        )}
                                        bind:value={certUserId}
                                        options={certStudentOptions}
                                    />
                                    <InputError message={errors.user_id} />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label
                                        for="cert-event"
                                        class="text-start text-[14px] text-[#7e7e7e]"
                                    >
                                        {t(
                                            'dashboard_supervisor.linked_event_optional',
                                        )}
                                    </label>
                                    <FilterSelect
                                        id="cert-event"
                                        class="h-10 min-h-10 rounded-[10px] border-black/20 px-5 text-[12px] text-black"
                                        name="event_id"
                                        allLabel={t(
                                            'dashboard_supervisor.no_linked_event',
                                        )}
                                        ariaLabel={t(
                                            'dashboard_supervisor.linked_event',
                                        )}
                                        placeholder={t(
                                            'dashboard_supervisor.select_event',
                                        )}
                                        bind:value={certEventId}
                                        options={eventOptions}
                                    />
                                    <InputError message={errors.event_id} />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing ||
                                    certUserId === '' ||
                                    certTemplateId === ''}
                                class="mx-auto w-full max-w-[500px] cursor-pointer rounded-full bg-brand px-10 py-3 text-[14px] text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {t('dashboard_supervisor.create_cert_button')}
                            </button>
                        {/snippet}
                    </Form>
                {/if}
                {#if certificateTemplates.length === 0}
                    <DashboardCard
                        class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-start text-sm text-[#5f5f5f]">
                            {t('certificate_templates.empty')}
                        </p>
                        <Link
                            href={createCertificateTemplate(club.id).url}
                            class="cursor-pointer rounded-full bg-brand px-5 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                        >
                            {t('certificate_templates.new_template')}
                        </Link>
                    </DashboardCard>
                {:else}
                    <div
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        {#each certificateTemplates as template (template.id)}
                            <CertificateTemplateCard
                                {template}
                                href={editCertificateTemplate({
                                    club: club.id,
                                    template: template.id,
                                }).url}
                            />
                        {/each}
                    </div>
                {/if}
            </section>
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

        {#if can(CAP.news)}
            <section
                aria-label={t('dashboard_supervisor.news_section')}
                class="flex flex-col gap-5"
            >
                <SectionHeading title={t('dashboard_supervisor.news_section')}>
                    {#snippet action()}
                        <Link
                            href={`/clubs/${club.id}/news/create`}
                            class="cursor-pointer rounded-full bg-brand px-5 py-2.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                        >
                            {t('dashboard_supervisor.post_news')}
                        </Link>
                    {/snippet}
                </SectionHeading>
                {#if posts.length === 0}
                    <DashboardCard>
                        <p class="text-start text-sm text-[#5f5f5f]">
                            {t('dashboard_supervisor.no_posts')}
                        </p>
                    </DashboardCard>
                {:else}
                    <DashboardCard class="flex flex-col gap-4 sm:p-8">
                        {#each posts as post (post.id)}
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-black/10 p-4"
                            >
                                <p
                                    class="min-w-0 flex-1 text-start text-[14px] text-black"
                                >
                                    {post.title}
                                </p>
                                <div class="flex items-center gap-2">
                                    <Link
                                        href={`/clubs/${club.id}/news/${post.id}/edit`}
                                        class="cursor-pointer rounded-full bg-brand/10 px-5 py-2 text-[12px] text-brand transition-colors hover:bg-brand/20"
                                    >
                                        {t('dashboard_supervisor.post_edit')}
                                    </Link>
                                    <button
                                        type="button"
                                        onclick={() => {
                                            if (
                                                confirm(
                                                    t(
                                                        'dashboard_supervisor.post_delete_confirm',
                                                    ),
                                                )
                                            ) {
                                                router.delete(
                                                    `/news/${post.id}`,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                        class="cursor-pointer rounded-full bg-[#f13e3e]/10 px-5 py-2 text-[12px] text-[#f13e3e] transition-colors hover:bg-[#f13e3e]/20"
                                    >
                                        {t('dashboard_supervisor.post_delete')}
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
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {#each reports as report (report.title)}
                        <ReportCard
                            title={report.title}
                            description={report.description}
                            href={report.downloadUrl}
                            buttonLabel={t('app.export_pdf')}
                        />
                    {/each}
                </div>
            </section>
        {/if}
    </div>
</div>

<!-- Add member dialog: search registered users and add directly. -->
<Dialog bind:open={addOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{t('members.add_member')}</DialogTitle>
            <DialogDescription>
                {t('members.add_search_placeholder')}
            </DialogDescription>
        </DialogHeader>
        <div class="flex flex-col gap-3">
            <label
                class="flex h-10 items-center gap-2 rounded-[10px] border border-black/20 bg-white px-4"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Search01Icon}
                    class="size-4 shrink-0 text-[#7e7e7e]"
                />
                <input
                    type="search"
                    bind:value={userQuery}
                    oninput={onUserQueryInput}
                    placeholder={t('members.add_search_placeholder')}
                    class="h-full min-w-0 flex-1 bg-transparent text-start text-[13px] text-black outline-none placeholder:text-[#7e7e7e]"
                />
            </label>
            <div class="max-h-64 overflow-y-auto">
                {#if searching}
                    <p class="px-1 py-2 text-[12px] text-[#7e7e7e]">…</p>
                {:else if userResults.length === 0 && userQuery.trim().length >= 2}
                    <p class="px-1 py-2 text-[12px] text-[#7e7e7e]">
                        {t('members.no_results')}
                    </p>
                {:else}
                    <ul class="flex flex-col gap-1">
                        {#each userResults as found (found.id)}
                            <li
                                class="flex items-center justify-between gap-3 rounded-[10px] border border-black/10 px-3 py-2"
                            >
                                <div
                                    class="flex min-w-0 flex-col items-start leading-tight"
                                >
                                    <span class="text-[13px] text-black"
                                        >{found.name}</span
                                    >
                                    <span class="text-[11px] text-[#7e7e7e]"
                                        >{found.email}</span
                                    >
                                </div>
                                <button
                                    type="button"
                                    onclick={() => addMember(found.id)}
                                    class="shrink-0 cursor-pointer rounded-full bg-brand px-4 py-1.5 text-[12px] text-white transition-colors hover:bg-brand-dark"
                                >
                                    {t('members.add')}
                                </button>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </div>
        </div>
    </DialogContent>
</Dialog>

<!-- Manage roles dialog (club lead only). -->
<Dialog bind:open={roleOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{t('members.manage_roles')}</DialogTitle>
            <DialogDescription>{roleTarget?.name}</DialogDescription>
        </DialogHeader>
        <div class="flex flex-col gap-2">
            {#each managerRoleOptions as option (option.value)}
                <label
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-[10px] border border-black/10 px-3 py-2.5"
                >
                    <span class="text-[13px] text-black">{option.label}</span>
                    <input
                        type="checkbox"
                        checked={selectedRoles.includes(option.value)}
                        onchange={() => toggleRole(option.value)}
                        class="size-4 accent-[#006471]"
                    />
                </label>
            {/each}
        </div>
        <div class="mt-2 flex items-center justify-end gap-2">
            <button
                type="button"
                onclick={() => (roleOpen = false)}
                class="flex cursor-pointer items-center gap-1 rounded-full bg-black/5 px-5 py-2 text-[12px] text-[#5f5f5f] transition-colors hover:bg-black/10"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Cancel01Icon}
                    class="size-3.5"
                />
                {t('members.cancel')}
            </button>
            <button
                type="button"
                onclick={saveRoles}
                class="cursor-pointer rounded-full bg-brand px-5 py-2 text-[12px] text-white transition-colors hover:bg-brand-dark"
            >
                {t('members.save_roles')}
            </button>
        </div>
    </DialogContent>
</Dialog>
