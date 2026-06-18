<script lang="ts">
    import { UserGroup03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventCard from '@/components/EventCard.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import { formatDate, formatNumber, t } from '@/lib/i18n.svelte';
    import { show as clubsShow } from '@/routes/clubs';
    import type { ClubListItem, EventSummary } from '@/types';

    type HomePost = {
        id: number;
        title: string;
        excerpt: string | null;
        published_at: string | null;
        club: string | null;
        image_url: string | null;
    };

    let {
        canRegister: _canRegister = true,
        clubs = [],
        events = [],
        posts = [],
    }: {
        canRegister?: boolean;
        clubs?: ClubListItem[];
        events?: EventSummary[];
        posts?: HomePost[];
    } = $props();

    function formatEventDate(iso: string): string {
        return formatDate(iso, {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    }
</script>

<AppHead title={t('welcome.title')} />

<div class="flex flex-col">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10 lg:px-12 lg:py-10"
    >
        <HeroBanner
            ariaLabel={t('welcome.uqu_section_aria')}
            title={t('welcome.uqu_title')}
            subtitle={t('welcome.uqu_subtitle')}
        />

        <!-- Most active clubs -->
        <section class="flex flex-col gap-5">
            <SectionHeader title={t('welcome.featured_clubs')} href="/clubs" />
            {#if clubs.length === 0}
                <EmptyState
                    class="rounded-[10px] py-6 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)]"
                    message={t('clubs.no_clubs')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each clubs as club (club.id)}
                        <article
                            class="relative flex h-[60px] items-center justify-between rounded-[10px] bg-white px-5 py-2.5 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_32px_0_rgba(0,0,0,0.12)]"
                        >
                            <Link
                                href={clubsShow(club).url}
                                class="absolute inset-0 rounded-[10px]"
                                aria-label={club.name}
                            ></Link>
                            <div
                                class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand/50 text-white shadow-[0_4px_12px_0_rgba(0,0,0,0.04)]"
                            >
                                {#if club.logo_url}
                                    <img
                                        src={club.logo_url}
                                        alt=""
                                        class="h-full w-full object-cover"
                                    />
                                {:else}
                                    <HugeiconsIcon
                                        strokeWidth={2}
                                        icon={UserGroup03Icon}
                                        class="size-4"
                                    />
                                {/if}
                            </div>
                            <div
                                class="flex min-w-0 flex-1 flex-col items-end px-3 text-start leading-none"
                            >
                                <p
                                    class="w-full truncate text-[12px] text-black"
                                >
                                    {club.name}
                                </p>
                                <p
                                    class="mt-1 w-full truncate text-[12px] text-[#7e7e7e]"
                                >
                                    {t('app.members_count', {
                                        count: formatNumber(club.members_count),
                                    })}
                                </p>
                            </div>
                            {#if !club.is_member}
                                <Link
                                    href={`/clubs/${club.id}/join`}
                                    class="relative z-10 flex shrink-0 cursor-pointer items-center justify-center rounded-full bg-brand/50 px-2.5 pt-1.5 pb-1 text-[12px] text-white transition-colors hover:bg-brand/70"
                                >
                                    {t('clubs.join')}
                                </Link>
                            {/if}
                        </article>
                    {/each}
                </div>
            {/if}
        </section>

        <!-- Featured upcoming events -->
        <section class="flex flex-col gap-5">
            <SectionHeader
                title={t('welcome.upcoming_events')}
                href="/events"
            />
            {#if events.length === 0}
                <EmptyState
                    class="shadow-[0_8px_24px_0_rgba(0,0,0,0.08)]"
                    message={t('events.no_events')}
                />
            {:else}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    {#each events as event (event.id)}
                        <EventCard
                            title={event.title}
                            metaStart={formatEventDate(event.starts_at)}
                            metaEnd={event.club.name}
                            description={event.description}
                            imageUrl={event.image_url}
                            href={`/events/${event.id}`}
                        />
                    {/each}
                </div>
            {/if}
        </section>

        <!-- Latest news & articles -->
        <section class="flex flex-col gap-5">
            <SectionHeader title={t('welcome.latest_news')} href="/news" />
            {#if posts.length === 0}
                <EmptyState
                    class="shadow-[0_8px_24px_0_rgba(0,0,0,0.08)]"
                    message={t('club.no_news')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each posts as post (post.id)}
                        <NewsCard
                            title={post.title}
                            excerpt={post.excerpt}
                            publishedAt={post.published_at}
                            club={post.club}
                            imageUrl={post.image_url}
                            href={`/news/${post.id}`}
                        />
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>
