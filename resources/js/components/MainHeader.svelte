<script lang="ts">
    import { Cancel01Icon, Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import MainSidebarContent from '@/components/MainSidebarContent.svelte';
    import MenuIcon from '@/components/MenuIcon.svelte';
    import {
        Sheet,
        SheetContent,
        SheetTitle,
        SheetTrigger,
    } from '@/components/ui/sheet';
    import { openGlobalSearch } from '@/lib/globalSearch.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { home } from '@/routes';

    let mobileOpen = $state(false);
    const direction = $derived((page.props.direction as string) ?? 'rtl');
</script>

<header
    dir={direction}
    class="sticky top-0 z-40 h-20 w-full rounded-b-[24px] bg-white shadow-[0_12px_32px_8px_rgba(126,126,126,0.18)] sm:h-24 sm:rounded-b-[32px] md:h-[119px] lg:hidden"
>
    <div class="flex h-full items-center justify-between px-4 sm:px-5 md:px-4">
        <Link
            href={home().url}
            class="flex cursor-pointer items-center transition-opacity hover:opacity-70"
            aria-label={t('app.home_aria')}
        >
            <AppLogoIcon
                class="h-8 w-auto shrink-0 fill-current text-black sm:h-9 md:h-[36px]"
            />
        </Link>

        <div class="flex items-center gap-1 sm:gap-2">
            <button
                type="button"
                onclick={openGlobalSearch}
                aria-label={t('app.search')}
                class="inline-flex size-12 cursor-pointer items-center justify-center rounded-full text-black transition-colors hover:bg-brand/10 sm:size-13 md:size-14"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Search01Icon}
                    class="size-7 md:size-8"
                />
            </button>

            <Sheet bind:open={mobileOpen}>
                <SheetTrigger
                    class="inline-flex size-12 cursor-pointer items-center justify-center rounded-full text-black transition-colors hover:bg-brand/10 hover:text-black sm:size-13 md:size-14"
                    aria-label={t('app.open_menu')}
                >
                    <MenuIcon class="size-9 md:size-10" />
                </SheetTrigger>
                <SheetContent
                    side={direction === 'rtl' ? 'left' : 'right'}
                    class="w-[280px] overflow-visible border-none bg-white p-0 text-black shadow-[-12px_0_32px_0_rgba(0,0,0,0.12)] rtl:shadow-[12px_0_32px_0_rgba(0,0,0,0.12)] sm:w-[320px]"
                    showCloseButton={false}
                >
                    <SheetTitle class="sr-only">{t('app.main_menu')}</SheetTitle
                    >
                    <button
                        type="button"
                        class="absolute top-4 -start-11 flex size-9 cursor-pointer items-center justify-center rounded-md bg-white text-black shadow-[0_4px_12px_0_rgba(0,0,0,0.15)] transition-opacity hover:opacity-80"
                        aria-label={t('app.close_menu')}
                        onclick={() => (mobileOpen = false)}
                    >
                        <HugeiconsIcon
                            strokeWidth={2}
                            icon={Cancel01Icon}
                            class="size-5"
                        />
                    </button>
                    <MainSidebarContent
                        onNavigate={() => (mobileOpen = false)}
                    />
                </SheetContent>
            </Sheet>
        </div>
    </div>
</header>
