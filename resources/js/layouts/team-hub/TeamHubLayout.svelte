<script lang="ts">
    import TeamHubSidebar from '@/components/team-hub/TeamHubSidebar.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import type { Snippet } from 'svelte';
    import '../../../css/team-hub.css';

    let {
        title = 'Team Hub',
        children,
        activePath,
        showRightPanel = false,
        rightPanel,
    }: {
        title?: string;
        children?: Snippet;
        activePath?: string;
        showRightPanel?: boolean;
        rightPanel?: Snippet;
    } = $props();

    let dark = $state(false);
</script>

<AppHead {title} />

<div class="team-hub min-h-screen" class:dark dir="rtl">
    <div class="flex min-h-screen">
        <TeamHubSidebar bind:dark {activePath} />

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
