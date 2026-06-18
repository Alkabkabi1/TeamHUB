<script lang="ts">
    import {
        Calendar03Icon,
        Clock01Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ClubCalendar from '@/components/ClubCalendar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventCard from '@/components/EventCard.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import {
        join as committeeJoin,
        manage as committeeManage,
    } from '@/routes/committees';
    import type { ClubRef } from '@/types';

    type Committee = {
        id: number;
        name: string;
        description: string | null;
        theme: string | null;
        logo_url: string | null;
        image_url: string | null;
        status: string;
    };

    type Stats = {
        members_count: number;
        upcoming_events_count: number;
        volunteer_hours_sum: number;
    };

    type EventItem = {
        id: number;
        club: string;
        time: string;
        title: string;
        description: string;
        image_url: string | null;
    };

    type PostItem = {
        id: number;
        title: string;
        excerpt: string;
        published_at: string;
        image_url: string | null;
    };

    type CalendarEvent = { id: number; title: string; starts_at: string };

    let {
        club,
        committee,
        stats = {
            members_count: 0,
            upcoming_events_count: 0,
            volunteer_hours_sum: 0,
        },
        upcomingEvents = [],
        posts = [],
        calendarEvents = [],
        canManage = false,
        membershipStatus = null,
        canRequestToJoin = false,
    }: {
        club: ClubRef & { logo_url?: string | null };
        committee: Committee;
        stats?: Stats;
        upcomingEvents?: EventItem[];
        posts?: PostItem[];
        calendarEvents?: CalendarEvent[];
        canManage?: boolean;
        membershipStatus?: string | null;
        canRequestToJoin?: boolean;
    } = $props();

    const miniStats = $derived([
        {
            icon: Clock01Icon,
            label: t('club.stats.hours'),
            value: formatNumber(Math.round(stats.volunteer_hours_sum)),
            note: t('club.stats.hours_note'),
        },
        {
            icon: Calendar03Icon,
            label: t('club.stats.events'),
            value: formatNumber(stats.upcoming_events_count),
            note: t('club.stats.events_note'),
        },
        {
            icon: UserGroup03Icon,
            label: t('club.stats.members'),
            value: formatNumber(stats.members_count),
            note: t('club.stats.members_note'),
        },
    ]);

    let joining = $state(false);

    function requestToJoin(): void {
        router.post(
            committeeJoin([club.id, committee.id]).url,
            {},
            {
                preserveScroll: true,
                onStart: () => (joining = true),
                onFinish: () => (joining = false),
            },
        );
    }
</script>

<AppHead title={committee.name} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={committee.name}
            title={club.name}
            subtitle={committee.name}
            foregroundLogo={committee.logo_url ?? club.logo_url ?? undefined}
            backgroundLogo={committee.logo_url ?? club.logo_url ?? undefined}
        />

        <div class="flex items-center justify-between gap-4">
            <Link
                href={`/clubs/${club.id}/committees`}
                class="text-sm text-[#7e7e7e] transition-colors hover:text-brand"
            >
                {t('committees.back_to_list')}
            </Link>
            {#if canManage}
                <Link
                    href={committeeManage([club.id, committee.id]).url}
                    class="cursor-pointer rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('committees.manage')}
                </Link>
            {/if}
        </div>

        {#if committee.description}
            <p class="text-start text-sm leading-relaxed text-[#5f5f5f]">
                {committee.description}
            </p>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('club.overview')} />
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                {#each miniStats as stat (stat.label)}
                    <StatCard
                        icon={stat.icon}
                        label={stat.label}
                        value={stat.value}
                        sub={stat.note}
                    />
                {/each}
            </div>
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('committees.featured_events')} />
            {#if upcomingEvents.length === 0}
                <EmptyState message={t('club.no_upcoming_events')} />
            {:else}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    {#each upcomingEvents as event (event.id)}
                        <EventCard
                            title={event.title}
                            metaStart={event.time}
                            metaEnd={event.club}
                            description={event.description}
                            href={`/events/${event.id}`}
                            imageUrl={event.image_url}
                        />
                    {/each}
                </div>
            {/if}
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('committees.news')} />
            {#if posts.length === 0}
                <EmptyState message={t('club.no_news')} />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each posts as post (post.id)}
                        <NewsCard
                            title={post.title}
                            excerpt={post.excerpt}
                            publishedAt={post.published_at}
                            imageUrl={post.image_url}
                            href={`/news/${post.id}`}
                        />
                    {/each}
                </div>
            {/if}
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('committees.calendar')} />
            <ClubCalendar events={calendarEvents} />
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('committees.join')} />
            <div
                class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-8 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
            >
                {#if membershipStatus === 'approved'}
                    <p class="text-sm text-brand">
                        {t('committees.is_member')}
                    </p>
                {:else if membershipStatus === 'pending'}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('committees.request_pending')}
                    </p>
                {:else if membershipStatus === 'rejected'}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('committees.request_rejected_state')}
                    </p>
                {:else if canRequestToJoin}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('committees.join_desc')}
                    </p>
                    <button
                        type="button"
                        disabled={joining}
                        onclick={requestToJoin}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {t('committees.request_to_join')}
                    </button>
                {:else}
                    <p class="text-sm text-[#5f5f5f]">
                        {t('committees.join_requires_club')}
                    </p>
                    <Link
                        href={`/clubs/${club.id}/join`}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('clubs.join')}
                    </Link>
                {/if}
            </div>
        </section>
    </div>
</div>
