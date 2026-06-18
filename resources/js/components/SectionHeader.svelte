<script lang="ts">
    /**
     * Section heading with a trailing "view all" link and arrow, used above
     * the club/event listings on the landing and club pages.
     */
    import { ArrowLeft02Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        title: string;
        /** When omitted, the "view all" link is not rendered. */
        href?: string;
        viewAllLabel?: string;
    };

    let { title, href, viewAllLabel }: Props = $props();
</script>

<div class="flex items-end justify-between">
    <h2 class="text-lg text-[#5f5f5f] sm:text-xl">{title}</h2>
    {#if href}
        <a
            dir="ltr"
            {href}
            class="group flex cursor-pointer items-center gap-1.5 text-sm text-[#7e7e7e] transition-colors hover:text-brand"
        >
            <HugeiconsIcon
                strokeWidth={2}
                icon={ArrowLeft02Icon}
                class="size-4"
            />
            <span>{viewAllLabel ?? t('welcome.view_all')}</span>
        </a>
    {/if}
</div>
