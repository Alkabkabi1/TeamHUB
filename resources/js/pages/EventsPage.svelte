<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import CatalogFilterBar from '@/components/CatalogFilterBar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import EventCard from '@/components/EventCard.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import { formatDate as formatLocalizedDate, t } from '@/lib/i18n.svelte';
    import { events as eventsRoute } from '@/routes';
    import type { CatalogEvent, SelectOption } from '@/types';

    let {
        events = [],
        filters = { search: '', tag: '', sort: 'soonest' },
        filterOptions = { tags: [], sorts: [] },
        userRsvpIds = [],
    }: {
        events?: CatalogEvent[];
        filters?: {
            search: string;
            tag: string;
            sort: string;
        };
        filterOptions?: {
            tags: SelectOption[];
            sorts: SelectOption[];
        };
        userRsvpIds?: number[];
    } = $props();

    function formatEventDate(value: string): string {
        return formatLocalizedDate(value, {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    function isCapacityFull(event: CatalogEvent): boolean {
        return (
            event.capacity !== null &&
            event.registrations_count >= event.capacity
        );
    }

    function isUserRegistered(event: CatalogEvent): boolean {
        return userRsvpIds.includes(event.id);
    }

    function canRegister(event: CatalogEvent): boolean {
        return event.status === 'active' && !isCapacityFull(event);
    }

    function availabilityLabel(event: CatalogEvent): string {
        return canRegister(event) || isUserRegistered(event)
            ? t('events.availability_open')
            : t('events.availability_closed');
    }

    // Client-side "show more": reveal events in batches. Resets whenever a new
    // filtered result set arrives.
    const PAGE_SIZE = 12;
    let visibleCount = $state(PAGE_SIZE);
    const visibleEvents = $derived(events.slice(0, visibleCount));
    const hasMore = $derived(events.length > visibleCount);

    $effect(() => {
        // Reading `events` registers it as a dependency, so the visible count
        // resets to the first page whenever a new filtered result set arrives.
        if (events) {
            visibleCount = PAGE_SIZE;
        }
    });

    const isStudent = $derived(page.props.auth?.user?.role === 'student');

    function handleRsvp(event: CatalogEvent): void {
        if (isUserRegistered(event)) {
            router.delete(`/events/${event.id}/rsvp`, { preserveScroll: true });
        } else {
            router.post(
                `/events/${event.id}/rsvp`,
                {},
                { preserveScroll: true },
            );
        }
    }
</script>

<AppHead title={t('events.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={t('events.section_aria')}
            title={t('events.hero_title')}
            subtitle={t('events.hero_subtitle')}
        />

        <CatalogFilterBar
            action={eventsRoute().url}
            {filters}
            {filterOptions}
            searchPlaceholder={t('events.search_placeholder')}
            searchAria={t('events.search_aria')}
        />

        <section class="flex flex-col gap-5">
            <h1 class="text-start text-lg text-[#5f5f5f] sm:text-xl">
                {t('events.list_heading')}
            </h1>

            {#if events.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('events.no_events')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each visibleEvents as event (event.id)}
                        <EventCard
                            title={event.title}
                            metaStart={event.location ??
                                t('events.location_tbd')}
                            metaEnd={formatEventDate(event.starts_at)}
                            description={event.description ??
                                t('events.details_soon')}
                            imageUrl={event.image_url}
                            href={`/events/${event.id}`}
                        >
                            {#snippet actions()}
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    {#if isStudent}
                                        {#if isUserRegistered(event)}
                                            <button
                                                type="button"
                                                aria-label={t(
                                                    'events.rsvp_cancel',
                                                )}
                                                onclick={() =>
                                                    handleRsvp(event)}
                                                class="rounded-full bg-brand/20 inline-block w-[110px] px-3 py-1.5 text-center text-[12px] font-medium text-brand transition-colors hover:bg-brand/30"
                                            >
                                                {t('events.rsvp_registered')}
                                            </button>
                                        {:else if canRegister(event)}
                                            <button
                                                type="button"
                                                aria-label={t(
                                                    'events.register_now',
                                                )}
                                                onclick={() =>
                                                    handleRsvp(event)}
                                                class="rounded-full bg-brand inline-block w-[110px] px-3 py-1.5 text-center text-[12px] font-medium text-white transition-colors hover:bg-brand-dark"
                                            >
                                                {t('events.register_now')}
                                            </button>
                                        {:else}
                                            <span
                                                class="rounded-full bg-[#7e7e7e] inline-block w-[110px] px-3 py-1.5 text-center text-[12px] font-medium text-white"
                                            >
                                                {t('events.cannot_register')}
                                            </span>
                                        {/if}
                                    {:else}
                                        <Link
                                            href={`/events/${event.id}`}
                                            class="rounded-full bg-brand inline-block w-[110px] px-3 py-1.5 text-center text-[12px] font-medium text-white transition-colors hover:bg-brand-dark"
                                        >
                                            {t('events.details')}
                                        </Link>
                                    {/if}
                                    <span class="text-[12px] text-[#5f5f5f]">
                                        {availabilityLabel(event)}
                                    </span>
                                </div>
                            {/snippet}
                        </EventCard>
                    {/each}
                </div>

                {#if hasMore}
                    <div class="flex justify-center pt-3">
                        <button
                            type="button"
                            onclick={() => (visibleCount += PAGE_SIZE)}
                            class="rounded-full bg-brand/50 px-10 py-2.5 text-base font-medium text-white transition-colors hover:bg-brand/70"
                        >
                            {t('events.load_more')}
                        </button>
                    </div>
                {/if}
            {/if}
        </section>
    </div>
</div>
