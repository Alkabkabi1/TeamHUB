<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import CatalogFilterBar from '@/components/CatalogFilterBar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import HeroBanner from '@/components/HeroBanner.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { index as newsIndex, show as newsShow } from '@/routes/news';
    import type { SelectOption } from '@/types';

    type NewsItem = {
        id: number;
        title: string;
        excerpt: string | null;
        published_at: string | null;
        club: string | null;
        image_url: string | null;
    };

    let {
        posts = [],
        filters = { search: '', tag: '', sort: 'newest' },
        filterOptions = { tags: [], sorts: [] },
    }: {
        posts?: NewsItem[];
        filters?: { search: string; tag: string; sort: string };
        filterOptions?: { tags: SelectOption[]; sorts: SelectOption[] };
    } = $props();
</script>

<AppHead title={t('news.title')} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <HeroBanner
            ariaLabel={t('news.title')}
            title={t('news.hero_title')}
            subtitle={t('news.hero_subtitle')}
        />

        <CatalogFilterBar
            action={newsIndex().url}
            {filters}
            {filterOptions}
            searchPlaceholder={t('news.search_placeholder')}
            searchAria={t('news.search_aria')}
        />

        <section class="flex flex-col gap-5">
            <h1 class="text-start text-lg text-[#5f5f5f] sm:text-xl">
                {t('news.title')}
            </h1>

            {#if posts.length === 0}
                <EmptyState
                    class="shadow-[8px_8px_48px_rgba(0,0,0,0.06)]"
                    message={t('news.empty')}
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
                            href={newsShow(post.id).url}
                        />
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>
