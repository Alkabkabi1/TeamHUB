<script lang="ts">
    import {
        Calendar03Icon,
        Clock01Icon,
        UserGroup03Icon,
        UserStar01Icon,
    } from '@hugeicons/core-free-icons';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ClubCalendar from '@/components/ClubCalendar.svelte';
    import CommitteeCard from '@/components/CommitteeCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventCard from '@/components/EventCard.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import StatCard from '@/components/StatCard.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import type { Club } from '@/types';

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

    type CalendarEvent = {
        id: number;
        title: string;
        starts_at: string;
    };

    type CommitteeItem = {
        id: number;
        name: string;
        description: string;
        image_url: string | null;
        members_count: number;
    };

    let {
        club,
        stats = {
            members_count: 0,
            upcoming_events_count: 0,
            volunteer_hours_sum: 0,
        },
        upcomingEvents = [],
        posts = [],
        calendarEvents = [],
        committees = [],
        canManage = false,
        isMember = false,
    }: {
        club: Club;
        stats?: Stats;
        upcomingEvents?: EventItem[];
        posts?: PostItem[];
        calendarEvents?: CalendarEvent[];
        committees?: CommitteeItem[];
        canManage?: boolean;
        isMember?: boolean;
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
        {
            icon: UserStar01Icon,
            label: t('club.stats.category'),
            value: club.category ?? '—',
            note: club.college ?? t('club.stats.student_club'),
        },
    ]);
</script>

<AppHead title={club.name} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={club.name}
            title={t('club.uqu')}
            subtitle={club.name}
            foregroundLogo={club.logo_url ?? undefined}
            backgroundLogo={club.logo_url ?? undefined}
        />

        {#if canManage}
            <div class="flex justify-end">
                <Link
                    href={`/clubs/${club.id}/manage`}
                    class="cursor-pointer rounded-full bg-brand px-6 py-2.5 text-[13px] text-white transition-colors hover:bg-brand-dark"
                >
                    {t('club.manage')}
                </Link>
            </div>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('club.overview')} />
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
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
            <SectionHeader title={t('club.featured_events')} href="/events" />
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
            <SectionHeader title={t('club.news')} href="/news" />
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

        {#if committees.length > 0}
            <section class="flex flex-col gap-5">
                <SectionHeader
                    title={t('committees.title')}
                    href={`/clubs/${club.id}/committees`}
                />
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each committees as committee (committee.id)}
                        <CommitteeCard
                            name={committee.name}
                            description={committee.description}
                            members={t('app.members_count', {
                                count: formatNumber(committee.members_count),
                            })}
                            href={`/clubs/${club.id}/committees/${committee.id}`}
                            imageUrl={committee.image_url}
                        />
                    {/each}
                </div>
            </section>
        {/if}

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('club.calendar')} />
            <ClubCalendar events={calendarEvents} />
        </section>

        {#if !isMember}
            <section class="flex flex-col gap-5">
                <SectionHeader title={t('club.join_club')} />
                <div
                    class="flex flex-col items-center gap-4 rounded-[20px] bg-white p-8 text-center shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
                >
                    <p class="text-sm text-[#5f5f5f]">
                        {t('club.join_club_desc')}
                    </p>
                    <Link
                        href={`/clubs/${club.id}/join`}
                        class="cursor-pointer rounded-[50px] bg-brand px-8 py-3 text-sm text-white transition-colors hover:bg-brand-dark"
                    >
                        {t('clubs.join')}
                    </Link>
                </div>
            </section>
        {/if}
    </div>
</div>
