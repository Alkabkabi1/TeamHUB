<script lang="ts">
    import {
        ArrowRight01Icon,
        Calendar03Icon,
        UserGroup03Icon,
    } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ImageGallery from '@/components/ImageGallery.svelte';
    import NewsCard from '@/components/NewsCard.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { show as clubShow } from '@/routes/clubs';
    import { index as newsIndex, show as newsShow } from '@/routes/news';

    type ClubRef = { id: number; name: string };

    type Article = {
        id: number;
        title: string;
        body: string;
        images: string[];
        published_at: string | null;
        club: ClubRef | null;
        author: string | null;
    };

    type RelatedItem = {
        id: number;
        title: string;
        excerpt: string | null;
        published_at: string | null;
        club: string | null;
        image_url: string | null;
    };

    let {
        post,
        related = [],
    }: {
        post: Article;
        related?: RelatedItem[];
    } = $props();

    const hasRelated = $derived(related.length > 0);

    const gridClass = $derived(
        hasRelated
            ? 'grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8'
            : 'grid grid-cols-1 gap-6',
    );

    const articleClass = $derived(
        [
            'flex flex-col gap-6 rounded-[20px] bg-white p-6 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:p-8',
            hasRelated ? 'lg:col-span-2' : 'mx-auto w-full max-w-3xl',
        ].join(' '),
    );
</script>

<AppHead title={post.title} />

<div class="flex flex-1 flex-col bg-[#fdfdfd]">
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-12"
    >
        <Link
            href={newsIndex().url}
            class="flex items-center gap-1.5 self-start text-[13px] text-[#7e7e7e] transition-colors hover:text-brand"
        >
            <HugeiconsIcon
                strokeWidth={2}
                icon={ArrowRight01Icon}
                class="size-4 rotate-180 rtl:rotate-0"
            />
            <span>{t('news.back_to_news')}</span>
        </Link>

        <div class={gridClass}>
            <article class={articleClass}>
                <ImageGallery images={post.images} alt={post.title} />

                <div
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-[#7e7e7e]"
                >
                    {#if post.club}
                        <Link
                            href={clubShow(post.club.id).url}
                            class="flex items-center gap-1.5 transition-colors hover:text-brand"
                        >
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={UserGroup03Icon}
                                class="size-4"
                            />
                            <span>{post.club.name}</span>
                        </Link>
                    {/if}
                    {#if post.published_at}
                        <span class="flex items-center gap-1.5">
                            <HugeiconsIcon
                                strokeWidth={2}
                                icon={Calendar03Icon}
                                class="size-4"
                            />
                            <span>{post.published_at}</span>
                        </span>
                    {/if}
                    {#if post.author}
                        <span>{t('news.by_author', { name: post.author })}</span
                        >
                    {/if}
                </div>

                <h1 class="text-start text-xl font-bold text-black sm:text-2xl">
                    {post.title}
                </h1>

                <div
                    class="whitespace-pre-line text-start text-sm leading-7 text-[#5f5f5f]"
                >
                    {post.body}
                </div>
            </article>

            {#if hasRelated}
                <aside class="flex flex-col gap-4 lg:col-span-1">
                    <h2 class="text-start text-lg text-[#5f5f5f] sm:text-xl">
                        {t('news.related')}
                    </h2>
                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1"
                    >
                        {#each related as item (item.id)}
                            <NewsCard
                                compact
                                title={item.title}
                                publishedAt={item.published_at}
                                club={item.club}
                                imageUrl={item.image_url}
                                href={newsShow(item.id).url}
                            />
                        {/each}
                    </div>
                </aside>
            {/if}
        </div>
    </div>
</div>
