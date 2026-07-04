<script lang="ts" module>
    // Error pages are full-bleed: they render their own chrome (logo, actions)
    // and must not depend on the authenticated AppLayout, since guests can hit
    // a 404/403 too. Opt out of the central layout resolver.
    export const layout = () => null;
</script>

<script lang="ts">
    import { ArrowLeft01Icon, Home01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import FloatingDock from '@/components/FloatingDock.svelte';
    import { buttonVariants } from '@/components/ui/button';
    import { t } from '@/lib/i18n.svelte';
    import { home } from '@/routes';

    let { status }: { status: number } = $props();
    type Direction = 'rtl' | 'ltr' | 'auto';

    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );

    // Fall back to a generic message when an unmapped status reaches this page.
    const known = [403, 404, 419, 429, 500, 503];
    const group = $derived(known.includes(status) ? String(status) : 'generic');

    const title = $derived(t(`errors.${group}.title`));
    const description = $derived(t(`errors.${group}.description`));

    function goBack(): void {
        if (typeof window !== 'undefined' && window.history.length > 1) {
            window.history.back();
        }
    }
</script>

<AppHead title={`${status} · ${title}`} />

<div
    dir={direction}
    class="relative flex min-h-svh w-full items-center justify-center overflow-hidden bg-white px-4 py-10 dark:bg-[#0b1120] sm:px-6 lg:px-10"
>
    <!-- Decorative scattered circles with light blur (matches the auth screens) -->
    <div
        aria-hidden="true"
        class="pointer-events-none absolute -left-32 top-20 hidden size-[460px] rounded-full bg-brand/15 blur-sm md:block lg:size-[520px]"
    ></div>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute -right-12 -top-10 hidden size-[210px] rounded-full bg-brand/30 blur-sm md:block"
    ></div>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute -right-20 bottom-24 hidden size-[280px] rounded-full bg-brand/25 blur-sm md:block"
    ></div>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute bottom-40 left-32 hidden size-[120px] rounded-full bg-brand/30 blur-sm md:block"
    ></div>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute left-1/3 bottom-12 hidden size-[80px] rounded-full bg-brand/20 blur-sm lg:block"
    ></div>

    <!-- Top-right TeamHUB logo -->
    <Link
        href={home().url}
        aria-label={t('app.home_aria')}
        class="absolute end-6 top-6 z-20 text-black dark:text-white md:end-10 md:top-8"
    >
        <AppLogoIcon class="h-9 w-auto fill-current" />
    </Link>

    <!-- Centered content -->
    <div
        class="relative z-10 flex w-full max-w-[520px] flex-col items-center gap-5 text-center"
    >
        <p
            class="bg-gradient-to-b from-brand to-brand/40 bg-clip-text text-[120px] font-bold leading-none text-transparent select-none sm:text-[160px]"
        >
            {status}
        </p>

        <h1 class="text-xl font-medium text-black dark:text-white sm:text-2xl">
            {title}
        </h1>

        <p
            class="max-w-md text-sm leading-relaxed text-[#7e7e7e] dark:text-[#cbd5e1] sm:text-base"
        >
            {description}
        </p>

        <div
            class="mt-2 flex flex-col items-center gap-3 sm:flex-row sm:justify-center"
        >
            <button
                type="button"
                onclick={goBack}
                class={buttonVariants({ variant: 'outline', size: 'lg' }) +
                    ' h-11 gap-2 px-6'}
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={ArrowLeft01Icon}
                    class="size-4 rtl:-scale-x-100"
                />
                {t('errors.go_back')}
            </button>
            <Link
                href={home().url}
                class={buttonVariants({ size: 'lg' }) +
                    ' h-11 gap-2 bg-brand px-6 text-white hover:bg-brand/90'}
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Home01Icon}
                    class="size-4"
                />
                {t('errors.go_home')}
            </Link>
        </div>
    </div>

    <FloatingDock />
</div>
