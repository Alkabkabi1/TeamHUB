<script lang="ts">
    /**
     * News / article preview card. Two variants:
     *  - default: full vertical card (image, meta, title, excerpt, read-more
     *    action) used by the landing page and news feed grids.
     *  - compact: small horizontal row (thumbnail + title + meta), used in
     *    sidebars such as the article page "more from this club" list.
     */
    import { Image03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        title: string;
        excerpt?: string | null;
        publishedAt?: string | null;
        club?: string | null;
        imageUrl?: string | null;
        href?: string;
        actionLabel?: string;
        compact?: boolean;
        /**
         * When provided (even as `null`), the card renders a download action
         * instead of a read-more link: a download anchor when the URL is
         * present, or a disabled "unavailable" state when it is `null`.
         */
        downloadUrl?: string | null;
        unavailableLabel?: string;
    };

    let {
        title,
        excerpt = '',
        publishedAt = '',
        club = null,
        imageUrl = null,
        href,
        actionLabel,
        compact = false,
        downloadUrl,
        unavailableLabel,
    }: Props = $props();
</script>

{#snippet compactInner()}
    <div
        class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand/25"
    >
        {#if imageUrl}
            <img src={imageUrl} alt="" class="h-full w-full object-cover" />
        {:else}
            <HugeiconsIcon
                strokeWidth={2}
                icon={Image03Icon}
                class="size-5 text-white"
            />
        {/if}
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1 text-start">
        <p class="line-clamp-2 text-[13px] font-medium text-black">
            {title}
        </p>
        <div
            class="flex items-center justify-between gap-2 text-[11px] text-[#7e7e7e]"
        >
            <span>{publishedAt ?? ''}</span>
            {#if club}
                <span class="truncate ps-2">{club}</span>
            {/if}
        </div>
    </div>
{/snippet}

{#if compact}
    {@const compactClass =
        'flex items-center gap-3 rounded-[16px] bg-white p-3 shadow-[4px_4px_24px_4px_rgba(0,0,0,0.06)] transition-all hover:-translate-y-0.5 hover:shadow-[6px_6px_32px_6px_rgba(0,0,0,0.08)]'}
    {#if href}
        <Link {href} class={compactClass}>
            {@render compactInner()}
        </Link>
    {:else}
        <div class={compactClass}>
            {@render compactInner()}
        </div>
    {/if}
{:else}
    <article
        class="flex h-full flex-col rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
    >
        <div
            class="flex aspect-[16/10] shrink-0 items-center justify-center overflow-hidden rounded-[16px] bg-brand/25 sm:aspect-square"
        >
            {#if imageUrl}
                <img src={imageUrl} alt="" class="h-full w-full object-cover" />
            {:else}
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Image03Icon}
                    class="size-7 text-white"
                />
            {/if}
        </div>

        <div
            class="mt-3 flex items-center justify-between text-[12px] text-[#7e7e7e]"
        >
            <span>{publishedAt ?? ''}</span>
            {#if club}
                <span class="truncate ps-2">{club}</span>
            {/if}
        </div>

        <div class="mt-2 flex min-w-0 flex-col gap-1 text-start">
            <p class="truncate text-sm font-medium text-black">{title}</p>
            <p class="line-clamp-3 text-xs leading-relaxed text-[#7e7e7e]">
                {excerpt ?? ''}
            </p>
        </div>

        {#if downloadUrl !== undefined}
            {#if downloadUrl}
                <a
                    href={downloadUrl}
                    download
                    class="mt-4 block rounded-[50px] bg-brand py-2.5 text-center text-xs text-white transition-colors hover:bg-brand-dark"
                >
                    {actionLabel ?? t('club.read_more')}
                </a>
            {:else}
                <span
                    aria-disabled="true"
                    class="mt-4 block rounded-[50px] bg-[#7e7e7e]/20 py-2.5 text-center text-xs text-[#7e7e7e]"
                >
                    {unavailableLabel ?? actionLabel ?? t('club.read_more')}
                </span>
            {/if}
        {:else if href}
            <Link
                {href}
                class="mt-4 block cursor-pointer rounded-[50px] bg-brand py-2.5 text-center text-xs text-white transition-colors hover:bg-brand-dark"
            >
                {actionLabel ?? t('club.read_more')}
            </Link>
        {:else}
            <span
                class="mt-4 block rounded-[50px] bg-brand py-2.5 text-center text-xs text-white"
            >
                {actionLabel ?? t('club.read_more')}
            </span>
        {/if}
    </article>
{/if}
