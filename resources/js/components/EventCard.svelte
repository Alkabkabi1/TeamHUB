<script lang="ts">
    /**
     * Event preview card: image, a two-label meta row, title, description and a
     * footer. The footer defaults to a "details" link; pass the `actions`
     * snippet to render custom footer content (e.g. the RSVP controls +
     * availability label on the events listing). Single shared card used by the
     * landing page, club page, student dashboard and events listing.
     *
     * Social actions from the Figma are intentionally omitted until there is a
     * real likes/comments backend.
     */
    import { Image03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        title: string;
        /** Leading meta label (e.g. date or location). */
        metaStart?: string;
        /** Trailing meta label (e.g. club name or date). */
        metaEnd?: string;
        description?: string | null;
        href?: string;
        detailsLabel?: string;
        imageUrl?: string | null;
        /** Custom footer; when omitted a "details" link is rendered. */
        actions?: Snippet;
    };

    let {
        title,
        metaStart = '',
        metaEnd = '',
        description = '',
        href = '/events',
        detailsLabel,
        imageUrl = null,
        actions,
    }: Props = $props();
</script>

<article
    class="flex h-full flex-col gap-3 rounded-[20px] bg-white p-5 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)] sm:p-6"
>
    <Link
        {href}
        aria-label={title}
        class="flex h-[150px] shrink-0 items-center justify-center overflow-hidden rounded-[16px] bg-brand/25"
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
    </Link>

    <div
        class="flex items-center justify-between gap-2 text-[12px] text-[#7e7e7e]"
    >
        <span class="truncate">{metaStart}</span>
        <span class="shrink-0 ps-2">{metaEnd}</span>
    </div>

    <div class="flex min-w-0 flex-col gap-1 text-start">
        <Link
            {href}
            class="block truncate text-sm font-medium text-black transition-colors hover:text-brand"
        >
            {title}
        </Link>
        <p class="line-clamp-2 text-xs leading-relaxed text-[#7e7e7e]">
            {description ?? ''}
        </p>
    </div>

    {#if actions}
        <div class="mt-auto pt-1">{@render actions()}</div>
    {:else}
        <div class="mt-auto flex justify-end pt-1">
            <Link
                {href}
                class="cursor-pointer rounded-[50px] bg-brand px-5 py-2 text-xs text-white transition-colors hover:bg-brand-dark"
            >
                {detailsLabel ?? t('workspace.details')}
            </Link>
        </div>
    {/if}
</article>
