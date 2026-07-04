<script lang="ts">
    /**
     * The branded hero banner shown at the top of every page (matches the
     * Figma "banner" design). Each page supplies its own copy via title /
     * subtitle and may override the two decorative logos: `foregroundLogo`
     * (the solid mark) and `backgroundLogo` (the large, low-opacity watermark
     * sitting behind it). Both default to the TeamHUB icon.
     */
    type Props = {
        title: string;
        subtitle?: string;
        ariaLabel?: string;
        foregroundLogo?: string;
        backgroundLogo?: string;
    };

    let {
        title,
        subtitle,
        ariaLabel,
        foregroundLogo = '/teamhub-icon.svg',
        backgroundLogo = '/teamhub-icon.svg',
    }: Props = $props();
</script>

<section aria-label={ariaLabel} class="w-full select-none">
    <div
        class="relative h-[300px] w-full overflow-hidden rounded-[20px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] sm:h-[360px] sm:rounded-[28px] lg:hidden"
    >
        <img
            src="/images/hero/stars-mobile-left.svg"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute inset-y-0 left-0 h-full w-1/2"
        />
        <img
            src="/images/hero/stars-mobile-right.svg"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute inset-y-0 right-0 h-full w-1/2"
        />

        <img
            src={backgroundLogo}
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute top-[-1%] left-1/2 h-[66%] -translate-x-1/2 object-contain opacity-[0.05]"
        />
        <img
            src={foregroundLogo}
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute top-[9%] left-1/2 h-[40%] -translate-x-1/2 object-contain"
        />

        <div
            class="absolute inset-x-0 bottom-[12%] flex flex-col items-center gap-2 px-6 text-center"
        >
            <p class="text-[28px] leading-tight text-white sm:text-[36px]">
                {title}
            </p>
            {#if subtitle}
                <p
                    class="max-w-[266px] text-[14px] leading-snug text-[#cccccc] sm:max-w-[420px] sm:text-[16px]"
                >
                    {subtitle}
                </p>
            {/if}
        </div>
    </div>

    <div class="relative hidden aspect-[1020/299] w-full lg:block">
        <div
            class="absolute inset-x-0 top-[8.03%] bottom-[7.69%] overflow-hidden rounded-[40px] bg-brand shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)]"
        ></div>

        <img
            src={backgroundLogo}
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute top-[-2.34%] right-[0.69%] aspect-[447/559] w-[24.02%] object-contain opacity-[0.04]"
        />
        <img
            src={foregroundLogo}
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute top-[22.4%] right-[8.73%] aspect-[447/559] w-[12.94%] object-contain"
        />

        <img
            src="/images/hero/stars.svg"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute top-0 bottom-0 left-[1.18%] h-full w-[21.66%]"
        />

        <div
            class="absolute top-[calc(50%+7.5px)] right-[24.7%] flex w-[41.67%] -translate-y-1/2 flex-col items-end justify-center gap-1 text-right"
        >
            <p class="w-full text-[40px] leading-[normal] text-white">
                {title}
            </p>
            {#if subtitle}
                <p class="w-full text-[24px] leading-[normal] text-[#cccccc]">
                    {subtitle}
                </p>
            {/if}
        </div>
    </div>
</section>
