<script lang="ts">
    /**
     * Multi-image display shared by the Event and News detail pages. Shows a
     * large primary image with a thumbnail strip to switch between images.
     *
     * The primary image uses `object-contain` against a neutral backdrop so the
     * whole image fits inside its frame without being cropped or overflowing.
     * Falls back to a placeholder icon when there are no images.
     */
    import { Image03Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';

    type Props = {
        images?: string[];
        alt?: string;
        /** Tailwind classes for the primary image frame (e.g. aspect ratio). */
        class?: string;
    };

    let {
        images = [],
        alt = '',
        class: className = 'aspect-video',
    }: Props = $props();

    let activeIndex = $state(0);

    const hasImages = $derived(images.length > 0);

    // Clamp the (user-controlled) active index so it stays valid even if the
    // image list changes, without mutating state inside an effect.
    const safeIndex = $derived(
        hasImages ? Math.min(activeIndex, images.length - 1) : 0,
    );
</script>

<div class="flex flex-col gap-3">
    <div
        class="relative flex w-full items-center justify-center overflow-hidden rounded-[16px] bg-[#eef3f4] {className}"
    >
        {#if hasImages}
            <img
                src={images[safeIndex]}
                {alt}
                class="h-full w-full object-contain"
            />
        {:else}
            <HugeiconsIcon
                strokeWidth={2}
                icon={Image03Icon}
                class="size-10 text-brand/50"
            />
        {/if}
    </div>

    {#if images.length > 1}
        <div class="flex flex-wrap gap-2">
            {#each images as image, index (image)}
                <button
                    type="button"
                    onclick={() => (activeIndex = index)}
                    aria-label={`${alt} ${index + 1}`}
                    aria-current={index === safeIndex}
                    class="size-16 shrink-0 overflow-hidden rounded-[10px] border-2 transition-colors {index ===
                    safeIndex
                        ? 'border-brand'
                        : 'border-transparent hover:border-brand/40'}"
                >
                    <img
                        src={image}
                        alt=""
                        class="h-full w-full object-cover"
                    />
                </button>
            {/each}
        </div>
    {/if}
</div>
