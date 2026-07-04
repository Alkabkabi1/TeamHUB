<script lang="ts">
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import CatalogFilterBar from '@/components/CatalogFilterBar.svelte';
    import ClubCard from '@/components/ClubCard.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import { formatNumber, t } from '@/lib/i18n.svelte';
    import { clubs as clubsRoute } from '@/routes';
    import { show as clubsShow } from '@/routes/clubs';
    import { create as clubsJoinCreate } from '@/routes/clubs/join';
    import type { ClubListItem, SelectOption } from '@/types';

    let {
        clubs = [],
        stats = { clubs: 0, members: 0, projects: 0, open_tasks: 0 },
        filters = { search: '', tag: '', sort: 'members' },
        filterOptions = { tags: [], sorts: [] },
    }: {
        clubs?: ClubListItem[];
        stats?: {
            clubs: number;
            members: number;
            projects: number;
            open_tasks: number;
        };
        filters?: {
            search: string;
            tag: string;
            sort: string;
        };
        filterOptions?: {
            tags: SelectOption[];
            sorts: SelectOption[];
        };
    } = $props();

    const statCards = $derived([
        { label: t('clubs.stats.clubs'), value: formatNumber(stats.clubs) },
        { label: t('clubs.stats.members'), value: formatNumber(stats.members) },
        {
            label: t('dashboard_student.stats.projects'),
            value: formatNumber(stats.projects),
        },
        {
            label: t('dashboard_student.stats.open_tasks'),
            value: formatNumber(stats.open_tasks),
        },
    ]);

    const PAGE_SIZE = 8;
    let visibleCount = $state(PAGE_SIZE);
    const visibleClubs = $derived(clubs.slice(0, visibleCount));
    const hasMore = $derived(visibleCount < clubs.length);

    // Reset the progressive reveal whenever a new result set arrives (e.g.
    // after applying a filter).
    $effect(() => {
        void clubs;
        visibleCount = PAGE_SIZE;
    });
</script>

<AppHead title={t('clubs.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={t('clubs.hero_title')}
            title={t('clubs.hero_title')}
            subtitle={t('clubs.hero_subtitle')}
        />

        <CatalogFilterBar
            action={clubsRoute().url}
            {filters}
            {filterOptions}
            searchPlaceholder={t('clubs.search_placeholder')}
            searchAria={t('clubs.search_aria')}
        />

        <section
            aria-label={t('clubs.section_aria')}
            class="grid grid-cols-2 gap-4 lg:grid-cols-4"
        >
            {#each statCards as stat (stat.label)}
                <article
                    class="flex h-[60px] flex-col justify-center rounded-[10px] bg-white px-4 text-center shadow-[8px_8px_24px_rgba(0,0,0,0.08)]"
                >
                    <p class="text-2xl font-semibold text-brand/50">
                        {stat.value}
                    </p>
                    <p class="mt-1 text-[12px] text-black">{stat.label}</p>
                </article>
            {/each}
        </section>

        <section class="flex flex-col gap-5">
            <h1 class="text-start text-lg text-[#5f5f5f] sm:text-xl">
                {t('clubs.title')}
            </h1>

            {#if clubs.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('clubs.no_clubs')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each visibleClubs as club (club.id)}
                        <div in:fade={{ duration: 250 }}>
                            <ClubCard
                                name={club.name}
                                tags={club.tags ?? []}
                                members={t('app.members_count', {
                                    count: formatNumber(club.members_count),
                                })}
                                description={club.college ??
                                    t('clubs.default_description')}
                                href={clubsShow(club).url}
                                joinHref={clubsJoinCreate(club).url}
                                imageUrl={club.logo_url}
                                isMember={club.is_member}
                            />
                        </div>
                    {/each}
                </div>

                {#if hasMore}
                    <div class="mt-2 flex justify-center">
                        <button
                            type="button"
                            onclick={() => (visibleCount += PAGE_SIZE)}
                            class="min-h-11 rounded-full bg-brand/50 px-10 text-base font-medium text-white transition-colors hover:bg-brand/70"
                        >
                            {t('app.show_more')}
                        </button>
                    </div>
                {/if}
            {/if}
        </section>
    </div>
</div>
