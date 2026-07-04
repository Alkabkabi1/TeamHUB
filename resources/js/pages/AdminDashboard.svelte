<script lang="ts">
    import {
        Calendar03Icon,
        Certificate01Icon,
        CheckmarkCircle01Icon,
        News01Icon,
        StarIcon,
        UserGroup03Icon,
        UserMultiple02Icon,
        Clock01Icon,
        Calendar02Icon,
    } from '@hugeicons/core-free-icons';
    import type { IconSvgElement } from '@hugeicons/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';

    type AdminStats = {
        total_clubs: number;
        total_active_clubs: number;
        total_members: number;
        total_events: number;
        upcoming_events: number;
        total_volunteer_hours: number;
        pending_applications: number;
        certificates_issued: number;
        posts_count: number;
    };

    type ClubRow = {
        id: number;
        name: string;
        members_count: number;
        status: string;
    };

    type Stat = {
        label: string;
        value: string;
        icon: IconSvgElement;
    };

    let {
        stats,
        clubs = [],
    }: {
        stats: AdminStats;
        clubs?: ClubRow[];
    } = $props();

    const statCards: Stat[] = $derived([
        {
            label: t('admin.stats.total_clubs'),
            value: formatNumber(stats.total_clubs),
            icon: UserGroup03Icon,
        },
        {
            label: t('admin.stats.total_active_clubs'),
            value: formatNumber(stats.total_active_clubs),
            icon: CheckmarkCircle01Icon,
        },
        {
            label: t('admin.stats.total_members'),
            value: formatNumber(stats.total_members),
            icon: UserMultiple02Icon,
        },
        {
            label: t('admin.stats.total_events'),
            value: formatNumber(stats.total_events),
            icon: Calendar03Icon,
        },
        {
            label: t('admin.stats.upcoming_events'),
            value: formatNumber(stats.upcoming_events),
            icon: Calendar02Icon,
        },
        {
            label: t('admin.stats.total_volunteer_hours'),
            value: formatNumber(Math.round(stats.total_volunteer_hours)),
            icon: Clock01Icon,
        },
        {
            label: t('admin.stats.pending_applications'),
            value: formatNumber(stats.pending_applications),
            icon: StarIcon,
        },
        {
            label: t('admin.stats.certificates_issued'),
            value: formatNumber(stats.certificates_issued),
            icon: Certificate01Icon,
        },
        {
            label: t('admin.stats.posts_count'),
            value: formatNumber(stats.posts_count),
            icon: News01Icon,
        },
    ]);

    function statusLabel(status: string): string {
        if (status === 'active') {
            return t('admin.status_active');
        }

        if (status === 'inactive') {
            return t('admin.status_inactive');
        }

        if (status === 'founding') {
            return t('admin.status_founding');
        }

        return status;
    }
</script>

<AppHead title={t('admin.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <section class="w-full">
            <div
                class="relative h-[240px] w-full overflow-hidden rounded-[20px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:h-[280px] sm:rounded-[28px]"
            >
                <img
                    src="/images/hero/stars-mobile-left.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 left-0 h-full w-1/2 lg:hidden"
                />
                <img
                    src="/images/hero/stars-mobile-right.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-y-0 right-0 h-full w-1/2 lg:hidden"
                />
                <img
                    src="/images/hero/stars.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-0 bottom-0 left-[1.18%] hidden h-full w-[21.66%] lg:block"
                />
                <img
                    src="/teamhub-icon.svg"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute top-[-1%] left-1/2 h-[90%] -translate-x-1/2 object-contain opacity-[0.04]"
                />
                <div
                    class="absolute inset-x-0 bottom-[12%] flex flex-col items-center gap-2 px-6 text-center"
                >
                    <p
                        class="text-[28px] leading-tight text-white sm:text-[36px]"
                    >
                        {t('admin.title')}
                    </p>
                    <p
                        class="text-[14px] leading-snug text-[#cccccc] sm:text-[16px]"
                    >
                        {t('admin.subtitle')}
                    </p>
                </div>
            </div>
        </section>

        <section class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {#each statCards as stat (stat.label)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                        size="lg"
                    />
                {/each}
            </div>
        </section>

        <section class="flex flex-col gap-5">
            <h2 class="text-lg text-[#5f5f5f] sm:text-xl">
                {t('admin.clubs_section')}
            </h2>
            {#if clubs.length === 0}
                <EmptyState message={t('admin.no_clubs')} />
            {:else}
                <div
                    class="overflow-hidden rounded-[20px] bg-white shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b border-black/5 bg-[#f9f9f9]">
                                <th
                                    class="px-5 py-3 text-[12px] font-medium text-[#5f5f5f]"
                                >
                                    {t('admin.club_name')}
                                </th>
                                <th
                                    class="px-5 py-3 text-[12px] font-medium text-[#5f5f5f]"
                                >
                                    {t('admin.club_members')}
                                </th>
                                <th
                                    class="px-5 py-3 text-[12px] font-medium text-[#5f5f5f]"
                                >
                                    {t('admin.club_status')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each clubs as club (club.id)}
                                <tr
                                    class="border-b border-black/5 last:border-0 hover:bg-[#f9f9f9]"
                                >
                                    <td
                                        class="px-5 py-3 text-[12px] text-black"
                                    >
                                        {club.name}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-[12px] text-[#5f5f5f]"
                                    >
                                        {formatNumber(club.members_count)}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-[12px] text-[#5f5f5f]"
                                    >
                                        {statusLabel(club.status)}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </section>
    </div>
</div>
