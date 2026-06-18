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

    const direction = $derived((page.props.direction as string) ?? 'rtl');

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    // Aria-live announcement text
    let liveAnnouncement = $state('');

    // Announce page title on navigation
    $effect(() => {
        const cleanup = router.on('navigate', () => {
            const title =
                (typeof document !== 'undefined' ? document.title : '') ||
                (page.props.name as string) ||
                '';

            const prefix = t('a11y.navigated_to');
            liveAnnouncement = '';

            // Defer so the region has a chance to clear before the new text is read
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
    class="flex min-h-screen flex-col bg-[#fdfdfd] text-foreground"
>
    <!-- Visually-hidden polite live region for page navigation announcements -->
    <div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
        {liveAnnouncement}
    </div>

    <MainHeader />

    <!--
        `grow` (flex-grow:1 with flex-basis:auto) + `lg:min-h-screen` lets this
        row size to its content height while still filling the viewport on short
        pages. Using `flex-1` (flex-basis:0) here capped the row at ~100vh, so
        the stretched sidebar stopped short and content overflowed beneath it.

        The footer lives INSIDE <main> (not as a sibling of the row) so it only
        spans the content column — never under the sidebar — and so the row
        stretches the full document height, keeping the sticky sidebar pinned to
        the viewport all the way down instead of scrolling off at the bottom.
    -->
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
