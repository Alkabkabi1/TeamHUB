<script lang="ts">
    import { ArrowLeft01Icon } from '@hugeicons/core-free-icons';
    import { HugeiconsIcon } from '@hugeicons/svelte';
    import { Link } from '@inertiajs/svelte';

    /**
     * Standard "go back" link for sub-pages (create/edit forms, editors).
     * The arrow mirrors automatically in RTL. Matches the certificates
     * templates page's back-to-manage affordance.
     */
    let {
        href,
        label,
    }: {
        href: string;
        label: string;
    } = $props();
</script>

<Link
    {href}
    class="inline-flex w-fit items-center gap-1.5 text-[13px] text-[#7e7e7e] transition-colors hover:text-brand"
>
    <HugeiconsIcon
        strokeWidth={2}
        icon={ArrowLeft01Icon}
        class="size-4 rtl:rotate-180"
    />
    {label}
</Link>
