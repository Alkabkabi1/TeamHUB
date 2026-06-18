<script lang="ts">
    /**
     * Export-report mini card (Figma node 56:1024): a title, a short description
     * and a full-width export button that downloads the report.
     */
    let {
        title,
        description,
        href,
        buttonLabel,
    }: {
        title: string;
        description?: string;
        href: string;
        buttonLabel: string;
    } = $props();
</script>

<article
    class="flex min-h-[120px] flex-col justify-between gap-2 rounded-[10px] bg-white px-5 py-4 shadow-[0_8px_24px_0_rgba(0,0,0,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_14px_32px_0_rgba(0,0,0,0.12)]"
>
    <div class="flex flex-col items-start text-start leading-tight">
        <p class="text-[12px] text-black">{title}</p>
        {#if description}
            <p class="mt-1 text-[12px] text-[#7e7e7e]">{description}</p>
        {/if}
    </div>
    <a
        {href}
        class="w-full cursor-pointer rounded-full bg-brand py-2 text-center text-[12px] text-white transition-colors hover:bg-brand-dark"
    >
        {buttonLabel}
    </a>
</article>
