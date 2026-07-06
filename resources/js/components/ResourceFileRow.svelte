<script lang="ts">
    /**
     * Horizontal download row used by the resources catalogue: a file icon and
     * the resource title / club badge / description on the start side, with a
     * download pill on the end side (matches the Figma "download" row).
     */
    import { Files01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { t } from '@/lib/i18n.svelte';

    type Props = {
        title: string;
        description?: string | null;
        workspace?: string | null;
        downloadUrl?: string | null;
    };

    let {
        title,
        description = '',
        workspace = null,
        downloadUrl = null,
    }: Props = $props();
</script>

<article
    class="flex items-center justify-between gap-4 rounded-[20px] bg-white px-6 py-4 shadow-[8px_8px_48px_rgba(0,0,0,0.06)] sm:px-12"
>
    <div class="flex min-w-0 flex-1 items-start gap-3">
        <span
            class="flex size-6 shrink-0 items-center justify-center text-brand"
        >
            <HugeiconsIcon strokeWidth={2} icon={Files01Icon} class="size-6" />
        </span>

        <div class="flex min-w-0 flex-col items-start gap-1.5 text-start">
            <div class="flex min-w-0 max-w-full items-center gap-2">
                <h3 class="truncate text-sm text-black">{title}</h3>
                {#if workspace}
                    <span
                        class="inline-flex shrink-0 items-center rounded-full bg-brand/25 px-2.5 py-0.5 text-[10px] text-brand"
                    >
                        {workspace}
                    </span>
                {/if}
            </div>
            {#if description}
                <p class="line-clamp-2 text-[12px] leading-5 text-[#5f5f5f]">
                    {description}
                </p>
            {/if}
        </div>
    </div>

    {#if downloadUrl}
        <a
            href={downloadUrl}
            download
            class="w-[100px] shrink-0 rounded-full bg-brand py-2 text-center text-xs text-white transition-colors hover:bg-brand-dark"
        >
            {t('resources.download')}
        </a>
    {:else}
        <span
            aria-disabled="true"
            class="w-[100px] shrink-0 rounded-full bg-[#7e7e7e]/20 py-2 text-center text-xs text-[#7e7e7e]"
        >
            {t('resources.file_unavailable')}
        </span>
    {/if}
</article>
