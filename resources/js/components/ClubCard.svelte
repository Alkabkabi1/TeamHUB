<script lang="ts">
    /**
     * Club preview card (image, category + member-count meta, name,
     * description, join CTA). Shared by the clubs catalog grid. The whole card
     * links to the club page; the join button sits above it as the primary
     * action, matching the Figma club-list card.
     */
    import { Image03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';
    import type { TagSummary } from '@/types';

    type Props = {
        name: string;
        tags?: TagSummary[];
        members: string;
        description?: string | null;
        href: string;
        joinHref: string;
        imageUrl?: string | null;
        isMember?: boolean;
    };

    let {
        name,
        tags = [],
        members,
        description = '',
        href,
        joinHref,
        imageUrl = null,
        isMember = false,
    }: Props = $props();

    // Keep the meta row to a single line; surface the first couple of tags.
    const shownTags = $derived(tags.slice(0, 2));
</script>

<article
    class="relative flex h-full flex-col gap-3 rounded-[20px] bg-white p-4 shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
>
    <Link {href} class="absolute inset-0 rounded-[20px]" aria-label={name}
    ></Link>

    <div
        class="flex h-[150px] shrink-0 items-center justify-center overflow-hidden rounded-[16px] bg-brand/25"
    >
        {#if imageUrl}
            <img
                src={imageUrl}
                alt=""
                class="h-full w-full object-contain p-4"
            />
        {:else}
            <HugeiconsIcon
                strokeWidth={2}
                icon={Image03Icon}
                class="size-7 text-white"
            />
        {/if}
    </div>

    <div
        class="flex items-center justify-between gap-2 text-[12px] text-[#7e7e7e]"
    >
        <div class="flex min-w-0 items-center gap-1">
            {#each shownTags as tag (tag.id)}
                <span
                    class="truncate rounded-full bg-brand/10 px-2 py-0.5 text-[11px] text-brand"
                >
                    {tag.name}
                </span>
            {/each}
        </div>
        <span class="shrink-0">{members}</span>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1 text-right">
        <p class="truncate text-sm font-medium text-black">{name}</p>
        <p class="line-clamp-2 text-xs leading-relaxed text-[#7e7e7e]">
            {description ?? ''}
        </p>
    </div>

    {#if !isMember}
        <Link
            href={joinHref}
            class="relative z-10 mt-auto flex min-h-9 items-center justify-center rounded-[50px] bg-brand text-xs font-medium text-white transition-colors hover:bg-brand-dark"
        >
            {t('clubs.join')}
        </Link>
    {/if}
</article>
