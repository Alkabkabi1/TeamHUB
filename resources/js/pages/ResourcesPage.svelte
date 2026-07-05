<script lang="ts">
    import { fade } from 'svelte/transition';
    import AppHead from '@/components/AppHead.svelte';
    import CatalogFilterBar from '@/components/CatalogFilterBar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import ResourceFileRow from '@/components/ResourceFileRow.svelte';
    import SectionHeader from '@/components/SectionHeader.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { home } from '@/routes';
    import type { SelectOption } from '@/types';

    type Download = {
        id: number;
        name: string;
        description: string;
        club: string;
        format: string;
        access: string;
        downloadUrl: string | null;
    };

    type MediaItem = {
        id: number;
        date: string;
        club: string;
        title: string;
        format: string;
        access: string;
        downloadUrl: string | null;
    };

    let {
        downloads = [],
        media = [],
        filters = { search: '', tag: '', sort: 'newest' },
        filterOptions = { tags: [], sorts: [] },
    }: {
        downloads?: Download[];
        media?: MediaItem[];
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

    const PAGE_SIZE = 8;
    let visibleCount = $state(PAGE_SIZE);
    const visibleMedia = $derived(media.slice(0, visibleCount));
    const hasMore = $derived(visibleCount < media.length);

    // Reset the progressive reveal whenever a new result set arrives (e.g.
    // after applying a filter).
    $effect(() => {
        void media;
        visibleCount = PAGE_SIZE;
    });
</script>

<AppHead title={t('resources.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={t('resources.section_aria')}
            title={t('resources.hero_title')}
            subtitle={t('resources.hero_subtitle')}
        />

        <CatalogFilterBar
            action={home().url}
            {filters}
            {filterOptions}
            searchPlaceholder={t('resources.search_placeholder')}
            searchAria={t('resources.search_aria')}
        />

        <section class="flex flex-col gap-5">
            <SectionHeader
                title={t('resources.downloads_section')}
                href={home().url}
            />

            {#if downloads.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('resources.no_resources')}
                />
            {:else}
                <div class="flex flex-col gap-4">
                    {#each downloads as item (item.id)}
                        <div in:fade={{ duration: 250 }}>
                            <ResourceFileRow
                                title={item.name}
                                description={item.description}
                                club={item.club}
                                downloadUrl={item.downloadUrl}
                            />
                        </div>
                    {/each}
                </div>
            {/if}
        </section>

        <section class="flex flex-col gap-5">
            <SectionHeader title={t('resources.media_gallery')} />

            {#if media.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('resources.no_media')}
                />
            {:else}
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {#each visibleMedia as item (item.id)}
                        <div in:fade={{ duration: 250 }}>
                            <NewsCard
                                title={item.title}
                                club={item.club}
                                publishedAt={item.date}
                                downloadUrl={item.downloadUrl}
                                actionLabel={t('resources.download_format', {
                                    format: item.format,
                                })}
                                unavailableLabel={t(
                                    'resources.file_unavailable',
                                )}
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
