<script lang="ts">
    import { Search01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link, page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { openGlobalSearch } from '@/lib/globalSearch.svelte';
    import { t } from '@/lib/i18n.svelte';
    import { themeState } from '@/lib/theme.svelte';
    import { home } from '@/routes';

    type Direction = 'rtl' | 'ltr' | 'auto';
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );
    const theme = themeState();
</script>

<div
    dir={direction}
    class="relative z-10 mx-auto hidden w-full max-w-7xl items-center gap-6 px-4 pt-10 sm:px-6 lg:flex lg:px-12"
>
    <Link
        href={home().url}
        aria-label={t('app.home_aria')}
        class="shrink-0 cursor-pointer transition-opacity hover:opacity-70"
    >
        <AppLogoIcon class="block h-[37px] w-auto fill-current text-black" />
    </Link>

    <div class="flex flex-1 items-center gap-3">
        <button
            type="button"
            onclick={openGlobalSearch}
            aria-label={t('app.search')}
            aria-keyshortcuts="Meta+K Control+K"
            class="group flex h-[60px] flex-1 cursor-pointer items-center gap-3 rounded-[50px] bg-white px-6 text-start shadow-[8px_8px_24px_0_rgba(0,0,0,0.08)] transition-shadow hover:shadow-[8px_8px_32px_0_rgba(0,100,113,0.18)] focus-visible:shadow-[8px_8px_32px_0_rgba(0,100,113,0.18)] focus-visible:outline-none dark:bg-[#15191f] dark:shadow-[8px_8px_24px_0_rgba(0,0,0,0.3)]"
        >
            <span
                class="flex shrink-0 items-center justify-center rounded-full p-1 text-[#7e7e7e] transition-colors group-hover:text-brand dark:text-[#a1a1aa]"
            >
                <HugeiconsIcon
                    strokeWidth={2}
                    icon={Search01Icon}
                    class="size-6"
                />
            </span>
            <span
                class="flex-1 truncate text-sm text-[#7e7e7e] dark:text-[#a1a1aa]"
            >
                {t('app.search_placeholder')}
            </span>
        </button>

        <button
            type="button"
            onclick={() => theme.toggleAppearance()}
            class="shrink-0 rounded-full bg-white px-4 py-3 text-sm font-medium text-[#5f5f5f] shadow-[8px_8px_24px_0_rgba(0,0,0,0.08)] transition-colors hover:text-brand dark:bg-[#15191f] dark:text-[#f5f5f5] dark:shadow-[8px_8px_24px_0_rgba(0,0,0,0.3)]"
            aria-label={t('settings.nav_appearance')}
        >
            {theme.resolvedAppearance() === 'dark'
                ? t('settings.dark')
                : t('settings.light')}
        </button>
    </div>
</div>
