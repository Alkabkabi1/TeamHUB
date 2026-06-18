<script lang="ts">
    import { ArrowLeft01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import FloatingDock from '@/components/FloatingDock.svelte';
    import SparkleIcon from '@/components/SparkleIcon.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { home } from '@/routes';

    const direction = $derived((page.props.direction as string) ?? 'rtl');

    let {
        title,
        backHref = home().url,
        wide = false,
        children,
    }: {
        title: string;
        backHref?: string;
        /** Use the wider card variant (e.g. multi-column forms). Allows the page to scroll when the content is taller than the viewport. */
        wide?: boolean;
        children: Snippet;
    } = $props();

    /**
     * Decorative blurred circles, positioned to match the Figma design.
     * Centers are expressed as a percentage of the viewport so they scale,
     * and `-translate-x/y-1/2` re-centers each circle on that point.
     * Brand colour is #006471 (rgb 0 100 113); larger circles sit at 25%
     * opacity, smaller accent circles at 50%.
     *
     * @type {Array<{left: string; top: string; size: number; color: string; blur: number}>}
     */
    const decorCircles = [
        {
            left: '27.4%',
            top: '34.5%',
            size: 529,
            color: 'rgba(0,100,113,0.25)',
            blur: 12,
        },
        {
            left: '94.8%',
            top: '7.3%',
            size: 200,
            color: 'rgba(0,100,113,0.25)',
            blur: 8,
        },
        {
            left: '78.3%',
            top: '76.3%',
            size: 250,
            color: 'rgba(0,100,113,0.5)',
            blur: 8,
        },
        {
            left: '17.1%',
            top: '70.7%',
            size: 100,
            color: 'rgba(0,100,113,0.5)',
            blur: 5,
        },
        {
            left: '70.1%',
            top: '10.1%',
            size: 100,
            color: 'rgba(0,100,113,0.5)',
            blur: 5,
        },
    ];
</script>

<div
    dir={direction}
    class="relative flex w-full items-center justify-center bg-white px-4 sm:px-6 lg:px-10 {wide
        ? 'min-h-svh overflow-x-clip py-10'
        : 'min-h-svh overflow-x-clip py-6'}"
>
    <!-- Decorative scattered circles with soft blur (positions/colours match Figma) -->
    {#each decorCircles as circle (circle.left + circle.top)}
        <div
            aria-hidden="true"
            class="pointer-events-none absolute hidden -translate-x-1/2 -translate-y-1/2 rounded-full md:block"
            style="left:{circle.left}; top:{circle.top}; width:{circle.size}px; height:{circle.size}px; background:{circle.color}; filter:blur({circle.blur}px);"
        ></div>
    {/each}

    <!-- Back arrow — physically top-left, matching Figma -->
    <Link
        href={backHref}
        aria-label={t('auth.back_aria')}
        class="absolute left-6 top-6 z-20 inline-flex size-10 items-center justify-center rounded-full text-black/80 transition-colors hover:bg-black/5 md:left-10 md:top-8"
    >
        <HugeiconsIcon strokeWidth={2} icon={ArrowLeft01Icon} class="size-6" />
    </Link>

    <!-- Ruwad logo — physically top-right, matching Figma -->
    <Link
        href={home().url}
        aria-label={t('auth.logo_aria')}
        class="absolute right-6 top-6 z-20 text-black md:right-10 md:top-8"
    >
        <AppLogoIcon class="h-9 w-auto fill-current" />
    </Link>

    <!-- Centered card (matches Figma proportions) -->
    <div
        class="relative z-10 flex w-full flex-col items-center gap-4 rounded-[20px] bg-white shadow-[8px_8px_48px_0_rgba(0,0,0,0.08)] {wide
            ? 'max-w-[1155px] p-6 sm:p-10'
            : 'max-w-[460px] p-6 sm:p-7'}"
    >
        <!-- Sparkle avatar — sits fully inside the card; sparkle is a faint (~10%) white tint -->
        <div
            class="flex size-[80px] items-center justify-center rounded-full bg-brand text-white"
        >
            <SparkleIcon class="size-12" fillOpacity={0.12} />
        </div>

        <h1 class="text-base font-medium text-black">{title}</h1>

        {@render children()}
    </div>

    <FloatingDock />
</div>
