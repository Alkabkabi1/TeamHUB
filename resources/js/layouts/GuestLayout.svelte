<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import FloatingDock from '@/components/FloatingDock.svelte';
    import GlobalSearch from '@/components/GlobalSearch.svelte';
    import MainFooter from '@/components/MainFooter.svelte';
    import MainHeader from '@/components/MainHeader.svelte';
    import MainSidebar from '@/components/MainSidebar.svelte';
    import MainTopbar from '@/components/MainTopbar.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import { t } from '@/lib/i18n.svelte';

    type Direction = 'rtl' | 'ltr' | 'auto';
    const direction = $derived(
        (page.props.direction as Direction | undefined) ?? 'rtl',
    );

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    let liveAnnouncement = $state('');

    $effect(() => {
        const cleanup = router.on('navigate', () => {
            const title =
                (typeof document !== 'undefined' ? document.title : '') ||
                (page.props.name as string) ||
                '';

            const prefix = t('a11y.navigated_to');
            liveAnnouncement = '';

            if (typeof window !== 'undefined') {
                window.setTimeout(() => {
                    liveAnnouncement = prefix.replace(':title', title);
                }, 50);
            }
        });

        return cleanup;
    });
</script>

<div
    dir={direction}
    class="flex min-h-screen flex-col bg-[#fdfdfd] text-foreground dark:bg-[#0b1120] dark:text-white"
>
    <div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
        {liveAnnouncement}
    </div>

    <MainHeader />

    <div class="flex grow lg:min-h-screen">
        <main
            id="main-content"
            class="flex min-w-0 flex-1 flex-col overflow-x-clip"
        >
            <MainTopbar />
            {@render children?.()}
            <MainFooter />
        </main>

        <MainSidebar />
    </div>

    <GlobalSearch />
    <Toaster />
    <FloatingDock />
</div>
