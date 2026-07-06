<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppSidebar from '@/components/AppSidebar.svelte';
    import FloatingDock from '@/components/FloatingDock.svelte';
    import GlobalSearch from '@/components/GlobalSearch.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import { themeState } from '@/lib/theme.svelte';
    import '../../css/app-shell.css';

    let {
        title,
        children,
        activePath,
        showRightPanel = false,
        rightPanel,
        showGlobalSearch = true,
    }: {
        title?: string;
        children?: Snippet;
        activePath?: string;
        showRightPanel?: boolean;
        rightPanel?: Snippet;
        showGlobalSearch?: boolean;
    } = $props();

    const theme = themeState();
</script>

{#if title}
    <AppHead {title} />
{/if}

<div
    class="app-shell min-h-screen"
    class:dark={theme.resolvedAppearance() === 'dark'}
    dir="rtl"
>
    <div class="flex min-h-screen">
        <AppSidebar {activePath} />

        <div class="flex min-w-0 flex-1 flex-col">
            {@render children?.()}
        </div>

        {#if showRightPanel && rightPanel}
            <aside
                class="hidden w-72 shrink-0 space-y-4 border-s p-4 xl:block"
                style="background: var(--th-bg); border-color: var(--th-border)"
            >
                {@render rightPanel()}
            </aside>
        {/if}
    </div>
</div>

{#if showGlobalSearch}
    <GlobalSearch />
{/if}
<Toaster />
<FloatingDock />
