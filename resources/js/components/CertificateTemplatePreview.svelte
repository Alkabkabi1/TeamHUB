<script lang="ts">
    import { Image01Icon, StarIcon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { t } from '@/lib/i18n.svelte';

    export type CertificateTemplateField = {
        text: string;
        is_image: boolean;
        x: number;
        y: number;
        width: number;
        font_size: number;
        align: 'left' | 'center' | 'right' | string;
        color: string;
        font_weight: string;
    };

    /**
     * Faithful preview of a certificate template: the background image with every
     * placeholder/variable field overlaid at its design position. Coordinates are
     * 0–1 fractions and `font_size` is a fraction of template height, so positions
     * use `left/top/width` % and text is sized with container-query `cqh` units
     * (the box is a size container, aspect-ratio'd to the template). Shared by the
     * dashboard template card and the certificate-templates index.
     */
    let {
        imageUrl = null,
        name,
        width,
        height,
        isDefault = false,
        status,
        fields = [],
    }: {
        imageUrl?: string | null;
        name: string;
        width?: number;
        height?: number;
        isDefault?: boolean;
        status: string;
        fields?: CertificateTemplateField[];
    } = $props();

    const aspectRatio = $derived(
        width && height ? `${width}/${height}` : '1.414/1',
    );
</script>

<div
    class="relative w-full overflow-hidden bg-brand/5 [container-type:size]"
    style="aspect-ratio: {aspectRatio};"
>
    {#if imageUrl}
        <img
            src={imageUrl}
            alt={name}
            class="absolute inset-0 h-full w-full object-fill"
        />
    {/if}

    <!-- Variable fields overlaid at their design positions. -->
    {#each fields as field, index (index)}
        <div
            class="absolute overflow-hidden"
            style="left: {field.x * 100}%; top: {field.y *
                100}%; width: {field.width * 100}%; text-align: {field.align};"
        >
            {#if field.is_image}
                <span
                    class="inline-flex items-center gap-1 rounded border border-dashed border-brand/60 bg-white/60 px-1 text-brand"
                    style="font-size: max({field.font_size * 100}cqh, 7px);"
                >
                    <HugeiconsIcon
                        strokeWidth={2}
                        icon={Image01Icon}
                        class="size-[1em]"
                    />
                    {field.text}
                </span>
            {:else}
                <span
                    class="inline-block max-w-full truncate rounded-[2px] bg-white/45 align-top leading-none"
                    style="font-size: max({field.font_size *
                        100}cqh, 6px); color: {field.color}; font-weight: {field.font_weight};"
                >
                    {field.text}
                </span>
            {/if}
        </div>
    {/each}

    <div class="absolute end-2 top-2 flex flex-wrap items-center gap-1.5">
        {#if isDefault}
            <span
                class="inline-flex items-center gap-1 rounded-full bg-brand px-2.5 py-1 text-[11px] text-white"
            >
                <HugeiconsIcon strokeWidth={2} icon={StarIcon} class="size-3" />
                {t('certificate_templates.default_badge')}
            </span>
        {/if}
        <span
            class="rounded-full px-2.5 py-1 text-[11px] {status === 'active'
                ? 'bg-emerald-100 text-emerald-700'
                : 'bg-black/10 text-[#5f5f5f]'}"
        >
            {status === 'active'
                ? t('certificate_templates.active_badge')
                : t('certificate_templates.draft_badge')}
        </span>
    </div>
</div>
