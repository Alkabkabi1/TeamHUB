<script lang="ts">
    import type { Snippet } from 'svelte';
    import TeamHubLayout from '@/layouts/team-hub/TeamHubLayout.svelte';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();
</script>

<TeamHubLayout>
    {@render children?.()}
</TeamHubLayout>
