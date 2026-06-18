<script lang="ts">
    import type { Snippet } from 'svelte';

    /**
     * A dashboard section header: a title (with optional sub-description) on the
     * start side and an optional action (button/link) snippet on the end side.
     * Mirrors the repeated "إدارة الأعضاء + إضافه عضو جديد" pattern in the Figma.
     */
    let {
        title,
        description,
        action,
    }: {
        title: string;
        description?: string;
        action?: Snippet;
    } = $props();
</script>

<div class="flex flex-wrap items-end justify-between gap-3">
    <div class="flex flex-col items-start gap-1">
        <h2 class="text-lg text-[#5f5f5f] sm:text-xl">{title}</h2>
        {#if description}
            <p class="text-sm text-[#7e7e7e]">{description}</p>
        {/if}
    </div>
    {#if action}
        {@render action()}
    {/if}
</div>
