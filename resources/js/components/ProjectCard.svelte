<script lang="ts">
    /**
     * Project preview card (image, member-count meta, name, description and a
     * "view project" CTA). Shared by the project listing grid.
     */
    import { Image03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        name: string;
        description?: string | null;
        members: string;
        href: string;
        imageUrl?: string | null;
    };

    let {
        name,
        description = '',
        members,
        href,
        imageUrl = null,
    }: Props = $props();
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
            <img src={imageUrl} alt="" class="h-full w-full object-cover" />
        {:else}
            <HugeiconsIcon
                strokeWidth={2}
                icon={Image03Icon}
                class="size-7 text-white"
            />
        {/if}
    </div>

    <div class="flex items-center justify-end gap-2 text-[12px] text-[#7e7e7e]">
        <span class="shrink-0">{members}</span>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1 text-right">
        <p class="truncate text-sm font-medium text-black">{name}</p>
        <p class="line-clamp-2 text-xs leading-relaxed text-[#7e7e7e]">
            {description ?? ''}
        </p>
    </div>

    <Link
        {href}
        class="relative z-10 mt-auto flex min-h-9 items-center justify-center rounded-[50px] bg-brand text-xs font-medium text-white transition-colors hover:bg-brand-dark"
    >
        {t('project.view')}
    </Link>
</article>
